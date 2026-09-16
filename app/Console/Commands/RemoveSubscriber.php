<?php

namespace App\Console\Commands;


use App\Helpers\SettingsHelper;
use App\Models\Subscribers;
use App\Models\Subscriptions;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;

class RemoveSubscriber extends Command implements Isolatable
{
    protected $signature = 'emails:remove-unconfirmed-subscriber';

    protected $description = 'Removing subscribers who have not confirmed their subscription';

    /**
     * Initialize the command that removes unconfirmed subscribers.
     */
    public function __construct(

    ) {
        parent::__construct();
    }

    /**
     * Remove subscriptions and subscriber records whose confirmation period has expired.
     */
    public function handle(): int
    {
        $this->line('start of removing ...');

        $count = 0;

        if (SettingsHelper::getInstance()->getValueForKey('REMOVE_SUBSCRIBER')) {
            $interval = "created_at < NOW() - INTERVAL '" . (int)SettingsHelper::getInstance()->getValueForKey('DAYS_FOR_REMOVE_SUBSCRIBER') . "' DAY";
            $subscribers = Subscribers::query()->active()->whereRaw($interval);

            $count = $subscribers->count();

            if ($count > 0) {
                foreach ($subscribers->get() ?? [] as $subscriber) {
                    Subscriptions::where('subscriber_id',$subscriber->id)->delete();
                }

                $subscribers->delete();
            }
        }

        $this->line('removed: ' . $count);

        return self::SUCCESS;
    }

}
