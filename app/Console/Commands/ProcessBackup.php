<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProcessBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:process 
                            {--branch= : branch code}
                            {--from= : YYYY-MM}
                            {--to= : YYYY-MM}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract and process backup';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $branch = is_null($this->option('branch')) 
          ? $this->ask('Enter branch code')
          : $this->option('branch');

        $from = is_null($this->option('from')) 
          ? $this->ask('Enter year-month start [YYYY-MM]')
          : $this->option('from');

        $to = is_null($this->option('to')) 
          ? $this->ask('Enter year-month end [YYYY-MM]')
          : $this->option('to');

        
        //$this->comment('from '.$from.'-01');
        if (!is_iso_date($from.'-01')) {
          $this->comment('--from is invalid date format');
          exit;
        }

        if (!is_iso_date($to.'-01')) {
          $this->comment('--to is invalid date format');
          exit;
        }

        $from = \Carbon\Carbon::parse($from.'-01');
        $to = \Carbon\Carbon::parse($to.'-01');

        if ($from->gt($to)) {
          $this->comment('invalid date range: --from is greater than --to');
          exit;
        }


        $this->comment($from->gt($to));
        

        $this->comment($branch.' '.$from->format('Y-m-d'));
        //$this->comment('process: '.$this->option('branch').' from: '.$this->option('from'));
    }
}
