<?php
 
use Illuminate\Database\Seeder;
use App\Models\Branch;

class UserTableSeeder extends Seeder  
{
    public function run()
    {

        //DB::table('user')->delete();
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

        $branches = Branch::all();

        $users = [];
        foreach ($branches as $branch) {

            array_push($users, [
                'username' => strtolower($branch->code).'-cashier',
                'name' => $branch->code.' Cashier',
                'branchid'=> $branch->id,
                'email' => strtolower($branch->code).'-cashier@giligansrestaurant.com',
                'password' => bcrypt('giligans'),
                'admin'=>5,
                'id' => $branch->id
            ]);
            $this->command->info(strtolower($branch->code).'-cashier');
        }
        DB::table('user')->insert($users);

        $this->command->info('User table seeded!');
       
    }
}