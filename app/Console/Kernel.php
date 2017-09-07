<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\Inspire::class,
        \App\Console\Commands\ProcessBackup::class,
        \App\Console\Commands\BackupProcess::class,
        \App\Console\Commands\LoadBackup::class,
        \App\Console\Commands\ProcessBackupFiledate::class,
        \App\Console\Commands\Backlog\OpenCloseDate::class,
        \App\Console\Commands\Backlog\DailySales::class,
        \App\Console\Commands\Backlog\Purchase::class,
        \App\Console\Commands\Backlog\SalesMatcher::class,
        \App\Console\Commands\Backlog\SalesProcessMatcher::class,
        \App\Console\Commands\Backlog\MonthDaily::class,
        \App\Console\Commands\Import\Product::class,
        \App\Console\Commands\Import\Ap::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //$schedule->command('inspire')->hourly();
        //$schedule->command('queue:work')->cron('* * * * * *');
        $schedule->command('queue:work --tries=5')->everyMinute();
    }
}
