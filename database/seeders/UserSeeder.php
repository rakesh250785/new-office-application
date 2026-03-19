<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {

        $users = [

            ['name' => 'Chetana', 'email' => 'info@chromatographyworld.com', 'password' => 'info27286@9', 'branch' => 'Mumbai'],
            ['name' => 'Dhiren Sir', 'email' => 'dhiren@chromatographyworld.com', 'password' => 'cBQz63I2', 'branch' => 'Mumbai'],
            ['name' => 'Sunita', 'email' => 'sunita@chromatographyworld.com', 'password' => 'zmRCSc', 'branch' => 'Mumbai'],
            ['name' => 'Sona', 'email' => 'sona@chromatographyworld.com', 'password' => 'RpmTC8QB', 'branch' => 'Mumbai'],
            ['name' => 'Swati', 'email' => 'sales@chromatographyworld.com', 'password' => 'swati@753', 'branch' => 'Mumbai'],
            ['name' => 'Honey', 'email' => 'speed@chromatographyworld.com', 'password' => 'speed4949', 'branch' => 'Mumbai'],
            ['name' => 'Vandana', 'email' => 'mail@chromatographyworld.com', 'password' => 'B8UcrQ5D', 'branch' => 'Mumbai'],
            ['name' => 'Priti', 'email' => 'orders@chromatographyworld.com', 'password' => 'qRHdi6Px', 'branch' => 'Mumbai'],
            ['name' => 'Lalita', 'email' => 'admin@chromatographyworld.com', 'password' => 'jDr28E1T', 'branch' => 'Mumbai'],
            ['name' => 'Dinesh', 'email' => 'dinesh@chromatographyworld.com', 'password' => 'dinesh5863', 'branch' => 'Mumbai'],
            ['name' => 'Priya', 'email' => 'priya@chromatographyworld.com', 'password' => 'jVMY7uU9', 'branch' => 'Mumbai'],
            ['name' => 'Gm Enquiry', 'email' => 'gm-support@chromatographyworld.com', 'password' => 'gmsupport@456', 'branch' => 'Mumbai'],
            ['name' => 'Abhishek', 'email' => 'abhishek@chromatographyworld.com', 'password' => 'b9th8QQF', 'branch' => 'Mumbai'],

            ['name' => 'Ravinder Bisht', 'email' => 'chandigarh@chromatographyworld.com', 'password' => 'ApvH3rxh', 'branch' => 'Chandigarh'],
            ['name' => 'Ramnik Dhall', 'email' => 'chd@chromatographyworld.com', 'password' => 'ramnikchd', 'branch' => 'Chandigarh'],

            ['name' => 'CW Ahmedabad', 'email' => 'ahmedabad@chromatographyworld.com', 'password' => 'ahmedabad144', 'branch' => 'Ahmedabad'],
            ['name' => 'Mitesh', 'email' => 'mitesh.p@chromatographyworld.com', 'password' => 'bHsfE8uj', 'branch' => 'Ahmedabad'],
            ['name' => 'Ahmedabad Sales', 'email' => 'gm-ahmedabad@chromatographyworld.com', 'password' => 'SVv8Gh', 'branch' => 'Ahmedabad'],

            ['name' => 'Ananya', 'email' => 'pune@chromatographyworld.com', 'password' => 'd2SxrAc5', 'branch' => 'Pune'],

            ['name' => 'Ashish Dayma', 'email' => 'ashish@lcgcindia.com', 'password' => 'arRLG65H', 'branch' => 'Indore'],
            ['name' => 'Virendra Nagore', 'email' => 'indore@chromatographyworld.com', 'password' => 'BkHpr4jb', 'branch' => 'Indore'],

            ['name' => 'CW Bangalore', 'email' => 'usha@chromatographyworld.com', 'password' => 'qmjEECkA', 'branch' => 'Bangalore'],

            ['name' => 'Rekha', 'email' => 'rekha@chromatographyworld.com', 'password' => 'rekha972', 'branch' => 'Delhi'],

            ['name' => 'Naresh', 'email' => 'hyd@chromatographyworld.com', 'password' => 'naresh8565', 'branch' => 'Hyderabad'],
            ['name' => 'Swapnali', 'email' => 'hydadmin@chromatographyworld.com', 'password' => 'swapnali1234', 'branch' => 'Hyderabad'],
            ['name' => 'Suraj Pandey', 'email' => 'hydsales@chromatographyworld.com', 'password' => 'hydsales123456', 'branch' => 'Hyderabad'],

            ['name' => 'Priya', 'email' => 'chennai@chromatographyworld.com', 'password' => 'L7qjGuUw', 'branch' => 'Chennai'],

            ['name' => 'Ketan', 'email' => 'ketan@lcgcindia.com', 'password' => 'ketan8539', 'branch' => 'Surat'],
            ['name' => 'Naitik', 'email' => 'naitik@lcgcindia.com', 'password' => 'naitik5956', 'branch' => 'Surat'],
            ['name' => 'Priti', 'email' => 'surat@lcgcindia.com', 'password' => 'aw4eGtQx', 'branch' => 'Surat'],
            ['name' => 'Disha', 'email' => 'surat@chromatographyworld.com', 'password' => 'surat12345@9', 'branch' => 'Surat'],

            ['name' => 'Gm Enquiry', 'email' => 'gm-enquiry@chromatographyworld.com', 'password' => 'gmenquiry@456', 'branch' => 'Mumbai'],

            ['name' => 'Uday', 'email' => 'Kolkata@chromatographyworld.com', 'password' => 'kol44545', 'branch' => 'Kolkata'],

            ['name' => 'Sumit', 'email' => 'marketing@chromatographyworld.com', 'password' => 'sumit1513', 'branch' => 'Goa'],
            ['name' => 'Ananya', 'email' => 'goapune@chromatographyworld.com', 'password' => 'goa09@#$', 'branch' => 'Goa'],

            ['name' => 'Sonal', 'email' => 'mumbai@chromatographyworld.com', 'password' => '56EV6e', 'branch' => 'Mumbai'],
            ['name' => 'Prathamesh', 'email' => 'prathamesh@chromatographyworld.com', 'password' => '56EV77', 'branch' => 'Mumbai'],
            ['name' => 'Harsh', 'email' => 'postatus@chromatographyworld.com', 'password' => 'admin09', 'branch' => 'Mumbai'],
            ['name' => 'Follow Up', 'email' => 'gm-followups@chromatographyworld.com', 'password' => 'followups5985', 'branch' => 'Mumbai'],
            ['name' => 'Kirti', 'email' => 'gm-services1@chromatographyworld.com', 'password' => '1356@pradnya', 'branch' => 'Mumbai'],
            ['name' => 'Chetan', 'email' => 'promotion@chromatographyworld.com', 'password' => 'promotion1234', 'branch' => 'Mumbai'],
            ['name' => 'Mansi', 'email' => 'application@chromatographyworld.com', 'password' => 'Mansi2525@9', 'branch' => 'Mumbai'],
            ['name' => 'Dhiren Sir', 'email' => 'haria@chromatographyworld.com', 'password' => 'haria12356', 'branch' => 'Mumbai'],
            ['name' => 'Seema', 'email' => 'accounts@chromatographyworld.com', 'password' => 'accounts123456', 'branch' => 'Mumbai'],
            ['name' => 'Makrand', 'email' => 'gm-mum@chromatographyworld.com', 'password' => 'makrand123456', 'branch' => 'Mumbai'],
            ['name' => 'Nikhil', 'email' => 'gm-marketing@chromatographyworld.com', 'password' => 'nikhilmarketing', 'branch' => 'Mumbai'],
            ['name' => 'Prashant Dandekar', 'email' => 'HOsales@chromatographyworld.com', 'password' => 'hosales1254521', 'branch' => 'Mumbai'],
            ['name' => 'Renu Yadav', 'email' => 'gm-procurement@chromatographyworld.com', 'password' => 'renu4564', 'branch' => 'Mumbai'],
            ['name' => 'Rahul Shukla', 'email' => 'gm-mumbai@chromatographyworld.com', 'password' => 'rahul7536', 'branch' => 'Mumbai'],

            ['name' => 'Somnarayan Giri', 'email' => 'somnarayan@chromatographyworld.com', 'password' => 'somnarayan123', 'branch' => 'Ahmedabad'],

            ['name' => 'Jit Deuri', 'email' => 'NE@chromatographyworld.com', 'password' => 'nechromatography', 'branch' => 'North East'],
            ['name' => 'Shubham', 'email' => 'enquiry@chromatographyworld.com', 'password' => 'shubham', 'branch' => 'North East'],

            ['name' => 'Shubhan Ranout', 'email' => 'uk-hp@chromatographyworld.com', 'password' => 'ukhp-cw', 'branch' => 'Chandigarh'],

            ['name' => 'Nidhi', 'email' => 'ideas@chromatographyworld.com', 'password' => 'ideas84339', 'branch' => 'Mumbai'],

        ];

        foreach ($users as $user) {

            $branch = DB::table('branches')
                ->where('name', $user['branch'])
                ->first();

            $roleId = null;

            if ($user['email'] == 'info@chromatographyworld.com' || $user['email'] == 'dhiren@chromatographyworld.com') {
                $roleId = 1;
            }

            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'last_name' => '',  
                    'user_name' => $user['email'],  
                    'email' => $user['email'],
                    'cc_email' => $user['email'],
                    'password' => Hash::make($user['password']),
                    'branch_id' => $branch ? $branch->id : 1,
                    'role_id' => $roleId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
