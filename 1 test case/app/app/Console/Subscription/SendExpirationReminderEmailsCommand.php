<?php

declare(strict_types=1);

namespace app\Console\Subscription;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SendExpirationReminderEmailsCommand extends Command implements Isolatable
{
    private const BUTCH_SIZE = 100;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:subscription:send-expiration-reminder-emails {--expires-in=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends subscription expiration reminder emails (AT LEAST ONCE: there may be duplicates)';

    /**
     * Execute the console command.
     */
    public function handle(\SubscriptionCoordinator $subscriptionCoordinator): void
    {
        $expiresIn = (int) $this->option('expires-in');

        $subscriptionCoordinator->sendExpirationReminderEmails($expiresIn);
    }
}
