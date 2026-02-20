<?php

namespace App\Console;

use App\Console\Commands\CheckOverduePayments;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run overdue payment check daily at 6 AM
        $schedule->command(CheckOverduePayments::class)
            ->dailyAt('06:00')
            ->name('check-overdue-payments')
            ->description('Check for overdue payments and generate reminders');
        
        // Also run at noon
        $schedule->command(CheckOverduePayments::class)
            ->dailyAt('12:00')
            ->name('check-overdue-payments-noon')
            ->description('Check for overdue payments and generate reminders (noon check)');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
