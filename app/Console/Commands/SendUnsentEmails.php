<?php

namespace App\Console\Commands;


use App\Helpers\SendEmailHelper;
use App\Helpers\SettingsHelper;
use App\Models\ReadySent;
use App\Models\Subscribers;
use App\Repositories\ScheduleRepository;
use App\Repositories\SubscriberRepository;
use App\Services\MailingDelayService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;

class SendUnsentEmails extends Command implements Isolatable
{
    protected $signature = 'emails:unsent';

    protected $description = 'Send unsent emails to subscribers';

    /**
     * Initialize the retry command with schedule, subscriber, and delay services.
     */
    public function __construct(
        private readonly ScheduleRepository $scheduleRepository,
        private readonly SubscriberRepository $subscribersRepository,
        private readonly MailingDelayService $mailingDelayService,
    ) {
        parent::__construct();
    }

    /**
     * Retry failed scheduled deliveries for eligible subscribers and update their records.
     */
    public function handle(): int
    {
        @set_time_limit(0);

        $this->line('start of mailing ...');

        $mailCountNo = 0;
        $mailCount = 0;
        $attemptCount = 0;

        $schedule = $this->scheduleRepository->getScheduleEvent();

        foreach ($schedule ?? [] as $row) {
            if (!$row->template) {
                continue;
            }

            $order = 'subscribers.id';

            $limit = (int) SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1
                ? (int) SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER')
                : null;

            $interval = $this->resolveInterval(
                (string) SettingsHelper::getInstance()->getValueForKey('INTERVAL_TYPE'),
                (int) SettingsHelper::getInstance()->getValueForKey('INTERVAL_NUMBER')
            );

            $subscribers = $this->subscribersRepository->getSubscribersUnSent(
                $row->id,
                $order,
                $limit,
                $interval
            );

            $sentSubscriberIds = [];
            $subscriberUpdates = [];
            $progressBar = $this->createMailingProgressBar(count($subscribers ?? []));

            foreach ($subscribers ?? [] as $subscriber) {
                $this->mailingDelayService->waitBetween($attemptCount);

                $result = $this->sendToSubscriber($row, $subscriber);
                $attemptCount++;

                if ($result['result'] === true) {
                    $subscriberUpdates[$subscriber->id] = now()->format('Y-m-d H:i:s');
                    $sentSubscriberIds[] = $subscriber->id;
                    $mailCount++;
                    $status = 'success';
                } else {
                    $mailCountNo++;
                    $status = 'failed';
                }

                $this->advanceMailingProgressBar($progressBar, $subscriber->email, $status);

                if (
                    (int) SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1
                    && $mailCount >= (int) SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER')
                ) {
                    $this->resultSend($row->id, $sentSubscriberIds, $subscriberUpdates);
                    break;
                }
            }

            $this->finishMailingProgressBar($progressBar);
            $this->resultSend($row->id, $sentSubscriberIds, $subscriberUpdates);

            if (
                (int) SettingsHelper::getInstance()->getValueForKey('LIMIT_SEND') === 1
                && $mailCount >= (int) SettingsHelper::getInstance()->getValueForKey('LIMIT_NUMBER')
            ) {
                break;
            }
        }

        $this->line('sent: ' . $mailCount);
        $this->line('no sent: ' . $mailCountNo);

        return self::SUCCESS;
    }

    /**
     * Build and resend the scheduled template to one subscriber.
     *
     * @param object $schedule
     * @param object $subscriber
     * @return array
     * @throws \PHPMailer\PHPMailer\Exception
     */
    private function sendToSubscriber(object $schedule, object $subscriber): array
    {
        $sendMail = new SendEmailHelper();
        $sendMail->body = $schedule->template->body;
        $sendMail->subject = $schedule->template->name;
        $sendMail->prior = $schedule->template->prior;
        $sendMail->email = $subscriber->email;
        $sendMail->token = $subscriber->token;
        $sendMail->subscriberId = $subscriber->id;
        $sendMail->name = $subscriber->name;
        $sendMail->templateId = $schedule->template->id;

        return $sendMail->sendEmail();
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
     * Create a console progress bar for the current retry batch.
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
     * Report one retry attempt in the progress bar or as a console line.
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
     * Complete the retry progress display when a progress bar is active.
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
     * Persist subscriber delivery times and mark successfully retried log entries as sent.
     *
     * @param int $scheduleId
     * @param array $sentSubscriberIds
     * @param array $subscriberUpdates
     * @return void
     */
    private function resultSend(int $scheduleId, array $sentSubscriberIds, array $subscriberUpdates): void
    {
        if ($subscriberUpdates !== []) {
            $ids = array_keys($subscriberUpdates);
            $caseSql = 'CASE id ';
            $bindings = [];

            foreach ($subscriberUpdates as $id => $ts) {
                $caseSql .= 'WHEN ? THEN ? ';
                $bindings[] = (int) $id;
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

        if ($sentSubscriberIds !== []) {
            ReadySent::query()
                ->where('schedule_id', $scheduleId)
                ->whereIn('subscriber_id', array_unique($sentSubscriberIds))
                ->where('success', 0)
                ->update([
                    'success' => 1,
                    'errorMsg' => null,
                    'updated_at' => now(),
                ]);
        }
    }
}
