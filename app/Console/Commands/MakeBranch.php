<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use App\Models\Branch;
use App\User;

class MakeBranch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:branch {brcode : Branch Code} 
                                {--opendate= : YYYY-MM-DD}
                                {--address= : Addresss}
                                {--name= : Branch Name}
                                {--force=false : Force}';


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
      $branch = [];
      $brcode = strtoupper($this->argument('brcode'));
      $create_branch = true;
      $br = Branch::where('code', strtoupper($brcode))->first();
      
      if ($br) {
        $this->error('Branch code already used!');
        if ($this->option('force')=='fasle')
          exit;
        $create_branch = false;
        $this->error('Skipping branch record creation!');
      }

      if (strlen($brcode)!==3) {
        $this->error('Invalid branch code length!');
        exit;
      }

      if ($this->option('opendate') && is_iso_date($this->option('opendate')))
        $branch['opendate'] = $this->option('opendate');
      
      if ($this->option('name'))
        $branch['descriptor'] = strtoupper($this->option('name'));
      
      if ($this->option('address'))
        $branch['address'] = $this->option('address');

      $c = strtolower($brcode);
      $branch['code'] = $brcode;
      $branch['mancost'] = 650;
      $branch['email'] = 'giligans.'.$c.'@gmail.com';

      if ($create_branch)
        $br = Branch::create($branch);

      $cashier = new User;
      $cashier->username = $c.'-cashier';
      $cashier->name = $brcode.' Cashier';
      $cashier->email = $branch['email'];
      $cashier->admin = 5;
      $cashier->branchid = $br->id;
      $cashier->password = bcrypt('giligans');
      $cashier->id = Branch::get_uid();
      $cashier->save();

      $manager = new User;
      $manager->setConnection('mysql-tk');
      $manager->getTable(); // products
      $manager->setTable('users');
      $manager->username = $c.'-manager';
      $manager->name = $brcode.' Manager';
      $manager->email = $branch['email'];
      $manager->branchid = $br->id;
      $manager->password = bcrypt('giligans');
      $manager->id = Branch::get_uid();
      $manager->save();




      $this->comment(json_encode($cashier));
      $this->comment(dd($branch));
    }
}
