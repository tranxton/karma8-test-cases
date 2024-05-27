<?php

namespace App\Console;

use app\Console\Subscription\SendExpirationReminderEmailsCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command(SendExpirationReminderEmailsCommand::class, ['--expires-in=1'])->dailyAt('00:00');
        $schedule->command(SendExpirationReminderEmailsCommand::class, ['--expires-in=3'])->dailyAt('01:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
