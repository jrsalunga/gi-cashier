<?php namespace App\Console\Commands\Masterfile;

use Carbon\Carbon;
use App\Models\Branch;
use Illuminate\Console\Command;
use App\Events\Notifier;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use App\Helpers\BackupExtractor;
use App\Services\ComponentImporter;


class Component extends Command
{

	protected $signature = 'masterfile:component {brcode : Branch Code} {date : YYYY-MM-DD} {--check=false : Run checker only}';
  protected $description = 'update component table';
  protected $filepath = NULL;
  protected $extractor;  
  protected $date;
  protected $file = 'COMPONEN.DBF';

  public function __construct(BackupExtractor $extractor, ComponentImporter $importer) {
    parent::__construct();
    
    $this->extractor = $extractor;
    $this->importer = $importer;
  }

  public function handle() {

    $date = $this->argument('date');
    if (!is_iso_date($date)) {
      $this->error('Invalid date. Please use YYYY-MM-DD format.');
      exit;
    }

    $date = Carbon::parse($date);
    if ($date->gte(c())) {
      $this->error('Invalid backup date. Too advance to backup.');
      exit;
    } 
    $this->date = $date;  

   

    $br = Branch::where('code', strtoupper($this->argument('brcode')))->first();
    if (is_null($br)) {
      $this->error('Invalid Branch Code.');
      exit;
    }


    if ($this->extractor->has_backup($br->code, $this->date)) {
    

      $this->line('extracting... ');
      if ($this->extract($br->code, $this->date, true)==1) {
        $this->line('extracted sucessfully');

        $this->line('filepath: '. $this->filepath);
    
        $res = $this->importer->import($br->id, $this->date, $this->extractor->getExtractedPath(), $this);

        $this->clean();   
      } // end: extract

    } else {
      $this->line($br->code.' '.$this->date->format('Y-m-d').' no backup');
    } // end: has_backup





    $this->line('******************');
    $this->line('on');
    $this->line('******************');



  }

  public function extract($brcode, $date, $show=true) {

    $this->extractor->extract($brcode, $date->format('Y-m-d'), 'admate');
    
    if (file_exists($this->extractor->getExtractedPath().DS.$this->file)) {
      $this->info($brcode.' - '.$this->file);
    } else {
      if ($show)
        if ($this->extractor->has_backup($brcode, $date)==0)
          $this->question($brcode);
        else
          $this->error($brcode);
      return 0;
    }
    return 1;    
  }

  public function clean() {
    return $this->extractor->clean();
  }
  
  private function notify($msg) {
    if(app()->environment()=='production')
      event(new Notifier('Masterfile\Component: '.$msg));
    else
      $this->error($msg);
  }

  
}