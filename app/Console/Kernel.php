<?php

namespace App\Console;

use App\Console\Commands\Backlog\Charges;
use App\Console\Commands\Backlog\DailySales;
use App\Console\Commands\Backlog\FoodSales;
use App\Console\Commands\Backlog\Ice;
use App\Console\Commands\Backlog\MonthDaily;
use App\Console\Commands\Backlog\MonthlyChangeItem;
use App\Console\Commands\Backlog\MonthlySales;
use App\Console\Commands\Backlog\OpenCloseDate;
use App\Console\Commands\Backlog\Purchase;
use App\Console\Commands\Backlog\RecomputeVat;
use App\Console\Commands\Backlog\SalesMatcher;
use App\Console\Commands\Backlog\SalesProcessMatcher;
use App\Console\Commands\BackupProcess;
use App\Console\Commands\Checker\PosVersion;
use App\Console\Commands\Cron\BacklogChangeItem;
use App\Console\Commands\Cron\BacklogCharges;
use App\Console\Commands\Cron\BacklogCos;
use App\Console\Commands\Cron\BacklogMonth;
use App\Console\Commands\EndOfDay;
use App\Console\Commands\Import\Ap;
use App\Console\Commands\Import\Paymast;
use App\Console\Commands\Import\Product;
use App\Console\Commands\Inspire;
use App\Console\Commands\LoadBackup;
use App\Console\Commands\MakeBranch;
use App\Console\Commands\ProcessBackup;
use App\Console\Commands\ProcessBackupFiledate;
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
        Inspire::class,
        ProcessBackup::class,
        BackupProcess::class,
        LoadBackup::class,
        ProcessBackupFiledate::class,
        OpenCloseDate::class,
        DailySales::class,
        MonthlySales::class,
        Purchase::class,
        SalesMatcher::class,
        SalesProcessMatcher::class,
        MonthDaily::class,
        Product::class,
        Ap::class,
        \App\Console\Commands\Import\BacklogMonth::class,
        BacklogMonth::class,
        BacklogCos::class,
        EndOfDay::class,
        MakeBranch::class,
        FoodSales::class,
        Ice::class,
        PosVersion::class,
        //\App\Console\Commands\Fixer\Invdtl::class,
        //\App\Console\Commands\Reports\Reading::class,
        
        RecomputeVat::class,
        Charges::class,
        \App\Console\Commands\Import\BacklogCharges::class,
        BacklogCharges::class,

        MonthlyChangeItem::class,
        \App\Console\Commands\Import\BacklogChangeItem::class,
        BacklogChangeItem::class,
        
        \App\Console\Commands\Checker\KitchenLog::class,
        \App\Console\Commands\Import\Kitlog::class,
        \App\Console\Commands\Backlog\Kitlog::class,
        \App\Console\Commands\Cron\Kitlog::class,
        
        \App\Console\Commands\Import\CashAudit::class,
        \App\Console\Commands\Backlog\CashAudit::class,
        \App\Console\Commands\Cron\CashAudit::class,
        
        Paymast::class,

        \App\Console\Commands\Import\Cv::class,
        \App\Console\Commands\Cron\Cv::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //$schedule->command('inspire')->hourly();
        //$schedule->command('queue:work')->cron('* * * * * *');
        $schedule->command('queue:work --tries=5')->everyMinute();
    }
}
