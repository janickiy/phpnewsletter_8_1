<?php

namespace App\Services;

use App\Enums\ProcessStatus;
use App\DTO\Create\ReadySentCreateData;
use App\Helpers\SettingsHelper;
use App\Models\Subscribers;
use App\Models\Templates;
use App\Models\Category;
use App\Models\Logs;
use App\Models\Project;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Repositories\{
    ReadySentRepository,
    SubscriberRepository,
    ProcessRepository,
};
use App\Helpers\SendEmailHelper;
use App\Helpers\StringHelper;
use Illuminate\Http\Request;
use Auth;
use DateTime;

class SendMailService
{

    /**
     * @param ReadySentRepository $readySentRepository
     * @param SubscriberRepository $subscribersRepository
     * @param ProcessRepository $processRepository
     * @param MailingDelayService $mailingDelayService
     * @param EmailLinkService $emailLinkService
     */
    public function __construct(
        private ReadySentRepository  $readySentRepository,
        private SubscriberRepository $subscribersRepository,
        private ProcessRepository    $processRepository,
        private MailingDelayService  $mailingDelayService,
        private EmailLinkService     $emailLinkService,
    )
    {
    }

    /**
     * Validate, compose, and send one test message with attachments from an existing template.
     *
     * @param Request $request
     * @return array
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function sendTest(Request $request): array
    {
        $subject = $request->input('name');
        $body = $request->input('body');
        $prior = $request->input('prior');
        $email = $request->input('email');
        $templateId = (int) $request->input('id');
        $project = ProjectAccess::authorizeProject($request->integer('project_id'), 'manage');
        abort_unless((bool) $project->status, 422, __('frontend.str.projects.inactive_mailing'));
        if ($templateId > 0) {
            abort_unless(ProjectAccess::scope(Templates::query(), 'manage')->whereKey($templateId)->where('project_id', $project->id)->exists(), 404);
        }

        $errors = [];

        if (empty($subject)) $errors[] = __('validation.empty_name');
        if (empty($body)) $errors[] = __('validation.empty_template');
        if (empty($email)) $errors[] = __('validation.empty_email');
        if (!empty($email) && StringHelper::isEmail($email) === false) $errors[] = __('validation.wrong_email');

        if (count($errors) === 0) {
            $attachmentTemplateId = $templateId > 0 && Templates::query()->whereKey($templateId)->exists()
                ? $templateId
                : null;

            $sendEmail = $this->createSendEmailHelper();
            $sendEmail->body = $body;
            $sendEmail->subject = $subject;
            $sendEmail->prior = $prior;
            $sendEmail->email = $email;
            $sendEmail->token = StringHelper::token();
            $sendEmail->templateId = $attachmentTemplateId ?? 0;
            $sendEmail->tracking = false;
            $result = $sendEmail->sendEmail($attachmentTemplateId);

            // Test emails are not tied to real subscribers/templates/schedules/logs,
            // so we must not write fake foreign keys like 0 into ready_sent.
            return [
                'result' => (bool) ($result['result'] ?? false),
                'msg' => !empty($result['error']) ? __('frontend.msg.email_wasnt_sent') : __('frontend.msg.email_sent'),
            ];
        }

        return [
            'result' => false,
            'msg' => implode(',', $errors),
        ];
    }

    /**
     * Create an isolated helper for each outgoing message.
     */
    protected function createSendEmailHelper(): SendEmailHelper
    {
        return new SendEmailHelper();
    }


    /**
     * Send selected templates to eligible category subscribers and record each delivery attempt.
     *
     * @param Request $request
     * @return array
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function sendOut(Request $request): array
    {
        [$templates, $categoryIds, $logId] = $this->mailingSelection($request);

        $this->processRepository->updateByUserId(Auth::id(), ProcessStatus::Start->value);

        $mailCount = 0;
        $attemptCount = 0;

        $order = 'subscribers.id';
        $limit = (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1 ? (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER') : null;

        switch (SettingsHelper::getInstance()->getValueForKey('INTERVAL_TYPE')) {
            case "minute":
                $interval = "(subscribers.timeSent IS NULL OR subscribers.timeSent < NOW() - INTERVAL '" . (int)SettingsHelper::getInstance()->getValueForKey('INTERVAL_NUMBER') . "' MINUTE)";
                break;
            case "hour":
                $interval = "(subscribers.timeSent IS NULL OR subscribers.timeSent < NOW() - INTERVAL '" . (int)SettingsHelper::getInstance()->getValueForKey('INTERVAL_NUMBER') . "' HOUR)";
                break;
            case "day":
                $interval = "(subscribers.timeSent IS NULL OR subscribers.timeSent < NOW() - INTERVAL '" . (int)SettingsHelper::getInstance()->getValueForKey('INTERVAL_NUMBER') . "' DAY)";
                break;
            default:
                $interval = null;
        }


        foreach ($templates ?? [] as $template) {

            $subscribers = $this->subscribersRepository->getSubscribers($logId, $template->id, $categoryIds, $order, $limit, $interval);

            $subscriberUpdates = [];

            foreach ($subscribers ?? [] as $subscriber) {
                if ($this->processRepository->getProcess(Auth::id()) === 'stop' || $this->processRepository->getProcess(Auth::id()) === 'pause') {
                    return [
                        'result' => true,
                        'completed' => true,
                    ];
                }

                $this->mailingDelayService->waitBetween($attemptCount);

                if (!Project::query()->includingDefault()->whereKey($template->project_id)->where('status', true)->exists()) {
                    break;
                }

                $sendEmail = $this->createSendEmailHelper();
                $sendEmail->body = $template->body;
                $sendEmail->subject = $template->name;
                $sendEmail->prior = $template->prior;
                $sendEmail->email = $subscriber->email;
                $sendEmail->token = $subscriber->token;
                $sendEmail->subscriberId = $subscriber->id;
                $sendEmail->name = $subscriber->name;
                $sendEmail->templateId = $template->id;
                $result = $sendEmail->sendEmail($template->id);
                $attemptCount++;

                if ($result['result'] === true) {
                    $this->readySentRepository->add(new ReadySentCreateData(
                        subscriberId: $subscriber->id,
                        templateId: $template->id,
                        success: 1,
                        scheduleId: null,
                        logId: $logId,
                        email: $subscriber->email,
                        template: $template->name,
                        errorMsg: null,
                        readMail: null,
                        projectId: (int) $template->project_id,
                    ));

                    $mailCount++;
                    $subscriberUpdates[$subscriber->id] = now()->format('Y-m-d H:i:s');
                } else {
                    $this->readySentRepository->add(new ReadySentCreateData(
                        subscriberId: $subscriber->id,
                        templateId: $template->id,
                        success: 0,
                        scheduleId: null,
                        logId: $logId,
                        email: $subscriber->email,
                        template: $template->name,
                        errorMsg: $result['error'],
                        readMail: null,
                        projectId: (int) $template->project_id,
                    ));
                }

                if ((int)SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1 && (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER') === $mailCount) {
                    $this->processRepository->updateByUserId(Auth::id(), ProcessStatus::Stop->value);
                    $this->resultSend($subscriberUpdates);
                    return [
                        'result' => true,
                        'completed' => true,
                    ];
                }
            }

            $this->resultSend($subscriberUpdates);
        }

        if ((int)SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1 && (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER') === $mailCount) {
            $this->processRepository->updateByUserId(Auth::id(), ProcessStatus::Stop->value);

            return [
                'result' => true,
                'completed' => true,
            ];
        }

        $this->processRepository->updateByUserId(Auth::id(), ProcessStatus::Stop->value);

        return [
            'result' => true,
            'completed' => true,
        ];
    }

    /**
     * Calculate manual-mailing totals, progress, failures, and estimated time remaining.
     *
     * @param Request $request
     * @return array
     */
    public function countSend(Request $request): array
    {
        [$templates, $categoryId, $logId] = $this->mailingSelection($request);

        $limit = (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1 ? (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER') : null;

        switch (SettingsHelper::getInstance()->getValueForKey('INTERVAL_TYPE')) {
            case "minute":
                $interval = "(subscribers.timeSent IS NULL OR subscribers.timeSent < NOW() - INTERVAL '" . (int)SettingsHelper::getInstance()->getValueForKey('INTERVAL_NUMBER') . "' MINUTE)";
                break;
            case "hour":
                $interval = "(subscribers.timeSent IS NULL OR subscribers.timeSent < NOW() - INTERVAL '" . (int)SettingsHelper::getInstance()->getValueForKey('INTERVAL_NUMBER') . "' HOUR)";
                break;
            case "day":
                $interval = "(subscribers.timeSent IS NULL OR subscribers.timeSent < NOW() - INTERVAL '" . (int)SettingsHelper::getInstance()->getValueForKey('INTERVAL_NUMBER') . "' DAY)";
                break;
            default:
                $interval = null;
        }

        $total = $templates->sum(fn ($template) => $this->subscribersRepository->countSubscriptions($categoryId, $limit, $interval, (int) $template->project_id));
        if ($limit !== null) {
            $total = min($total, $limit);
        }
        $success = $this->readySentRepository->countStatus($logId, 1);
        $unsuccess = $this->readySentRepository->countStatus($logId, 0);

        $sleepSetting = (int) SettingsHelper::getInstance()->getValueForKey('SLEEP');
        $sleep = $sleepSetting === 0 ? 0.5 : $sleepSetting;
        $timeSec = max(0, intval(($total - ($success + $unsuccess)) * $sleep));

        $datetime = new DateTime();
        $datetime->setTime(0, 0, $timeSec);

        return [
            'result' => true,
            'status' => 1,
            'total' => $total,
            'success' => $success,
            'unsuccessful' => $unsuccess,
            'time' => $datetime->format('H:i:s'),
            'leftsend' => $total > 0 ? min(100, round(($success + $unsuccess) / $total * 100, 2)) : 0,
        ];
    }

    /**
     * Validate every selected identifier before a manual mailing can read or send data.
     *
     * @return array{Collection, array, int}
     */
    private function mailingSelection(Request $request): array
    {
        abort_unless(Auth::check(), 403);
        $data = $request->validate([
            'templateId' => ['required', 'array', 'min:1'],
            'templateId.*' => ['required', 'integer', 'distinct'],
            'categoryId' => ['required', 'array', 'min:1'],
            'categoryId.*' => ['required', 'integer', 'distinct', Rule::in(ProjectAccess::scope(Category::query(), 'manage')->pluck('id')->all())],
            'logId' => ['required', 'integer'],
        ]);

        $templates = ProjectAccess::scope(Templates::query(), 'manage')->with('project')->whereIn('id', $data['templateId'])->get();
        abort_unless($templates->count() === count($data['templateId']), 404);
        abort_if($templates->contains(fn ($template) => !(bool) $template->project->status), 422, __('frontend.str.projects.inactive_mailing'));

        $log = Logs::query()->findOrFail($data['logId']);
        abort_unless(Auth::user()->role === User::ROLE_ADMIN || (int) $log->user_id === (int) Auth::id(), 404);

        return [$templates, array_map('intval', $data['categoryId']), (int) $log->id];
    }

    /**
     * Send subscription-confirmation and administrator-notification messages required for a new subscriber.
     *
     * @param Subscribers $subscriber
     * @return void
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function sendFrontendSubscriberEmails(Subscribers $subscriber): void
    {
        $settings = SettingsHelper::getInstance();

        $requireConfirmation = (int) $settings->getValueForKey('REQUIRE_SUB_CONFIRMATION') === 1;
        $notifyNewSubscriber = (int) $settings->getValueForKey('NEW_SUBSCRIBER_NOTIFY') === 1;

        if ($requireConfirmation) {
            $sendMail = new SendEmailHelper();

            $confirmUrl = route('frontend.subscribe', [
                'subscriber' => $subscriber->id,
                'token' => $subscriber->token,
            ]);
            $unsubscribeUrl = route('frontend.unsubscribe', [
                'subscriber' => $subscriber->id,
                'token' => $subscriber->token,
            ]);
            $isHtml = $settings->getValueForKey('CONTENT_TYPE') === 'html';

            $message = str_replace(
                ["\r\n", "\r", "\n"],
                '<br>',
                $settings->getValueForKey('TEXT_CONFIRMATION')
            );

            $message = str_replace(
                ['%CONFIRM%', '%UNSUB%'],
                [
                    $this->emailLinkService->render($confirmUrl, $isHtml),
                    $this->emailLinkService->render($unsubscribeUrl, $isHtml),
                ],
                $message
            );

            $sendMail->subject = $settings->getValueForKey('SUBJECT_TEXT_CONFIRM');
            $sendMail->body = $message;
            $sendMail->email = $subscriber->email;
            $sendMail->token = $subscriber->token;
            $sendMail->subscriberId = $subscriber->id;
            $sendMail->name = $subscriber->name;
            $sendMail->prior = 0;
            $sendMail->unsub = false;
            $sendMail->tracking = false;
            $sendMail->sendEmail();
        }

        if ($notifyNewSubscriber) {
            $sendMail = new SendEmailHelper();

            $subject = str_replace(
                '%SITE%',
                request()->getHost(),
                __('frontend.str.notification_newuser')
            );

            $message = __('frontend.str.notification_newuser') .
                "\nName: {$subscriber->name} \nE-mail: {$subscriber->email}\n";

            $message = str_replace('%SITE%', request()->getHost(), $message);

            $sendMail->subject = $subject;
            $sendMail->body = $message;
            $sendMail->email = $settings->getValueForKey('EMAIL');
            $sendMail->name = $settings->getValueForKey('FROM');
            $sendMail->prior = 0;
            $sendMail->tracking = false;
            $sendMail->unsub = false;
            $sendMail->sendEmail();
        }
    }

    /**
     * Persist the latest successful delivery time for each processed subscriber.
     *
     * @param array $subscriberUpdates
     * @return void
     */
    private function resultSend(array $subscriberUpdates): void
    {
        if (!empty($subscriberUpdates)) {
            $ids = array_keys($subscriberUpdates);

            $caseSql  = "CASE id ";
            $bindings = [];

            foreach ($subscriberUpdates as $id => $ts) {
                $caseSql .= "WHEN ? THEN ? ";
                $bindings[] = (int)$id;
                $bindings[] = $ts;
            }
            $caseSql .= "END";

            $inSql = implode(',', array_fill(0, count($ids), '?'));
            $bindings = array_merge($bindings, $ids);

            DB::statement(
                "UPDATE " . Subscribers::getTableName() . " SET timeSent = {$caseSql} WHERE id IN ({$inSql})",
                $bindings
            );
        }
    }
}
