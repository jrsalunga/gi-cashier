<?php namespace App\Console\Commands;

use Dflydev\ApacheMimeTypes\PhpRepository;
use Illuminate\Console\Command;
use App\Repositories\DateRange;
use App\Repositories\StorageRepository;

class ProcessBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:process 
                            {--branch= : branch code}
                            {--from= : YYYY-MM-DD}
                            {--to= : YYYY-MM-DD}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract and process backup';

    protected $dr;
    protected $pos;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(DateRange $dr, PhpRepository $mimeDetect)
    {
        parent::__construct();
        $this->dr = $dr;
        $this->pos = new StorageRepository($mimeDetect, 'pos.'.app()->environment());
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
          ? $this->ask('Enter year-month start [YYYY-MM-DD]')
          : $this->option('from');

        $to = is_null($this->option('to')) 
          ? $this->ask('Enter year-month end [YYYY-MM-DD]')
          : $this->option('to');

        
        //$this->comment('from '.$from.'-01');
        if (!is_iso_date($from)) {
          $this->comment('--from is invalid date format');
          exit;
        }

        if (!is_iso_date($to)) {
          $this->comment('--to is invalid date format');
          exit;
        }

        $from = \Carbon\Carbon::parse($from);
        $to = \Carbon\Carbon::parse($to);

        if ($from->gt($to)) {
          $this->comment('invalid date range: --from is greater than --to');
          exit;
        }

        $this->dr->fr = $from;
        $this->dr->to = $to;


        foreach ($this->dr->dateInterval() as $key => $date) {

          $path = $branch.DS.$date->format('Y').DS.$date->format('m').DS.'GC'.$date->format('mdy').'.ZIP';
          if ($this->pos->exists($path))
            $this->comment('exist');
          else
            $this->comment('not exist');
        }


        

        //$this->comment($branch.' '.$from->format('Y-m-d'));
        //$this->comment('process: '.$this->option('branch').' from: '.$this->option('from'));
    }
}
