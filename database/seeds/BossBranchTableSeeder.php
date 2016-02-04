<?php
 
use Illuminate\Database\Seeder;

class BossBranchTableSeeder extends Seeder  
{
    public function run()
    {

        DB::table('bossbranch')->delete();
        //DB::connection('hr')->table('company')->delete();

        DB::table('bossbranch')->insert(array(
        //DB::connection('hr')->table('company')->insert(array(
            array(
                'bossid' => '29E4E2FA672C11E596ECDA40B3C0AA12',
                'branchid' => 'F8056E535D0B11E5ADBC00FF59FBB323',
                'id'=> 'CB29ABA4BDBF11E5A3FA00FF59FBB323'
            ),
            array(
                'bossid' => '29E4E2FA672C11E596ECDA40B3C0AA12',
                'branchid' => '0CA14DE678A711E587FA00FF59FBB323',
                'id'=> '0586F5FABDC011E5A3FA00FF59FBB323'
            ),
            array(
                'bossid' => '29E4E2FA672C11E596ECDA40B3C0AA12',
                'branchid' => '0BEED86278A711E587FA00FF59FBB323',
                'id'=> '086BEF95BDC011E5A3FA00FF59FBB323'
            )
        ));

        $this->command->info('BossBranch table seeded!');
       
    }
}