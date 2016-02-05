<?php
 
use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder  
{
    public function run()
    {

        DB::table('user')->delete();
        //DB::connection('hr')->table('company')->delete();

        DB::table('user')->insert(array(
        //DB::connection('hr')->table('company')->insert(array(
            array(
                'username' => 'admin',
                'name' => 'Giligans Admin',
                'email' => 'admin@giligansrestaurant.com',
                'password' => bcrypt('giligans'),
                'admin' => 1,
                'branchid' => '0C17FE2D78A711E587FA00FF59FBB323',
                'id'=> '29E4E2FA672C11E596ECDA40B3C0AA12'
            ),
            array(
                'username' => 'cashier',
                'name' => 'Giligans Cashier',
                'email' => 'cashier@giligansrestaurant.com',
                'password' => bcrypt('giligans'),
                'admin' => 5,
                'branchid' => '0C17FE2D78A711E587FA00FF59FBB323',
                'id'=> '3060F4F3BE6011E5A3FA00FF59FBB323'
            )
        ));

        $this->command->info('User table seeded!');
       
    }
}