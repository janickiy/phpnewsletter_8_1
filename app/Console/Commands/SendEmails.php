<?php

namespace App\Console\Commands;


use App\DTO\Create\ReadySentCreateData;
use App\Helpers\SendEmailHelper;
use App\Helpers\SettingsHelper;
use App\Models\Logs;
use App\Models\Subscribers;
use App\Repositories\ReadySentRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\SubscriberRepository;
use App\Services\MailingDelayService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;

class SendEmails extends Command implements Isolatable
{
    protected $signature = 'emails:send';

    protected $description = 'Send emails to subscribers';

    /**
     * Initialize the scheduled-mail command with its persistence and delay dependencies.
     */
    public function __construct(
        private readonly ScheduleRepository   $scheduleRepository,
        private readonly SubscriberRepository $subscribersRepository,
        private readonly ReadySentRepository  $readySentRepository,
        private readonly MailingDelayService  $mailingDelayService,
    )
    {
        parent::__construct();
    }

    /**
     * Send all currently due scheduled mailings to eligible subscribers and record the results.
     */
    public function handle(): int
    {
        @set_time_limit(0);

        $mailCountNo = 0;
        $mailCount = 0;
        $attemptCount = 0;

        $this->line('start of mailing ...');

        $log = Logs::query()->create([
            'time' => now(),
        ]);

        $schedule = $this->scheduleRepository->getScheduleEvent();

        foreach ($schedule ?? [] as $row) {
            if (!$row->template) {
                continue;
            }

            $order = (int)SettingsHelper::getInstance()->getValueForKey('RANDOM_SEND') === 1
                ? 'RAND()'
                : 'subscribers.id';

            $limit = (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1
                ? (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER')
                : null;

            $interval = $this->resolveInterval(
                (string)SettingsHelper::getInstance()->getValueForKey('INTERVAL_TYPE'),
                (int)SettingsHelper::getInstance()->getValueForKey('INTERVAL_NUMBER')
            );

            $subscribers = $this->subscribersRepository->getSubscribersNotReadySent(
                $row->id,
                $order,
                $limit,
                $interval
            );

            $subscriberUpdates = [];
            $progressBar = $this->createMailingProgressBar(count($subscribers ?? []));

            foreach ($subscribers ?? [] as $subscriber) {
                $this->mailingDelayService->waitBetween($attemptCount);

                $result = $this->sendToSubscriber($row, $subscriber);
                $attemptCount++;

                $this->readySentRepository->add(new ReadySentCreateData(
                    subscriberId: $subscriber->id,
                    templateId: $row->template_id,
                    success: $result['result'] === true ? 1 : 0,
                    scheduleId: $row->id,
                    logId: $log->id,
                    email: $subscriber->email,
                    template: $row->template->name,
                    errorMsg: $result['error'] ?? null,
                    readMail: null,
                ));

                if ($result['result'] === true) {
                    $subscriberUpdates[$subscriber->id] = now()->format('Y-m-d H:i:s');
                    $mailCount++;
                    $status = 'success';
                } else {
                    $mailCountNo++;
                    $status = 'failed';
                }

                $this->advanceMailingProgressBar($progressBar, $subscriber->email, $status);

                if (
                    (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1
                    && $mailCount >= (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER')
                ) {
                    $this->resultSend($subscriberUpdates);
                    break;
                }
            }

            $this->finishMailingProgressBar($progressBar);
            $this->resultSend($subscriberUpdates);

            if (
                (int) SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1
                && $mailCount >= (int)SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER')
            ) {
                break;
            }
        }

        $this->line('sent: ' . $mailCount);
        $this->line('no sent: ' . $mailCountNo);

        return self::SUCCESS;
    }

    /**
     * Build and send the scheduled template to one subscriber.
     *
     * @param object $schedule
     * @param object $subscriber
     * @return array
     * @throws \PHPMailer\PHPMailer\Exception
     */
    private function sendToSubscriber(object $schedule, object $subscriber): array
    {
        $sendEmail = new SendEmailHelper();
        $sendEmail->body = $schedule->template->body;
        $sendEmail->subject = $schedule->template->name;
        $sendEmail->prior = $schedule->template->prior;
        $sendEmail->email = $subscriber->email;
        $sendEmail->token = $subscriber->token;
        $sendEmail->subscriberId = $subscriber->id;
        $sendEmail->name = $subscriber->name;
        $sendEmail->templateId = $schedule->template->id;

        return $sendEmail->sendEmail();
    }

    /**
     * Convert the configured delivery interval into a SQL eligibility condition.
     *
     * @param string $intervalType
     * @param int $intervalNumber
     * @return string|null
     */
    private function resolveInterval(string $intervalType, int $intervalNumber): ?string
    {
        if ($intervalNumber <= 0) {
            return null;
        }

        return match ($intervalType) {
            'minute' => "(subscribers.timeSent IS NULL OR subscribers.timeSent < NOW() - INTERVAL '{$intervalNumber}' MINUTE)",
            'hour' => "(subscribers.timeSent IS NULL OR subscribers.timeSent < NOW() - INTERVAL '{$intervalNumber}' HOUR)",
            'day' => "(subscribers.timeSent IS NULL OR subscribers.timeSent < NOW() - INTERVAL '{$intervalNumber}' DAY)",
            default => null,
        };
    }

    /**
     * Create a console progress bar for the current mailing batch.
     *
     * @param int $max
     * @return ProgressBar|null
     */
    private function createMailingProgressBar(int $max): ?ProgressBar
    {
        if ($max <= 0) {
            return null;
        }

        ProgressBar::setFormatDefinition(
            'mailing',
            ' %current%/%max% [%bar%] %percent:3s%% | %message%'
        );

        $progressBar = $this->output->createProgressBar($max);
        $progressBar->setFormat('mailing');
        $progressBar->setMessage('waiting...');
        $progressBar->start();

        return $progressBar;
    }

    /**
     * Report one delivery attempt in the progress bar or as a console line.
     *
     * @param ProgressBar|null $progressBar
     * @param string $email
     * @param string $status
     * @return void
     */
    private function advanceMailingProgressBar(?ProgressBar $progressBar, string $email, string $status): void
    {
        if (!$progressBar) {
            $this->line($email . ' - ' . $status);
            return;
        }

        $progressBar->setMessage($email . ' - ' . $status);
        $progressBar->advance();
    }

    /**
     * Complete the mailing progress display when a progress bar is active.
     *
     * @param ProgressBar|null $progressBar
     * @return void
     */
    private function finishMailingProgressBar(?ProgressBar $progressBar): void
    {
        if (!$progressBar) {
            return;
        }

        $progressBar->finish();
        $this->newLine(2);
    }

    /**
     * Persist the latest successful delivery time for each processed subscriber.
     *
     * @param array $subscriberUpdates
     * @return void
     */
    private function resultSend(array $subscriberUpdates): void
    {
        if ($subscriberUpdates === []) {
            return;
        }

        $ids = array_keys($subscriberUpdates);
        $caseSql = 'CASE id ';
        $bindings = [];

        foreach ($subscriberUpdates as $id => $ts) {
            $caseSql .= 'WHEN ? THEN ? ';
            $bindings[] = (int)$id;
            $bindings[] = $ts;
        }

        $caseSql .= 'END';

        $inSql = implode(',', array_fill(0, count($ids), '?'));
        $bindings = array_merge($bindings, array_map('intval', $ids));

        DB::statement(
            'UPDATE ' . Subscribers::getTableName() . " SET timeSent = {$caseSql} WHERE id IN ({$inSql})",
            $bindings
        );
    }
}
