<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        $db_admins = User::all()->pluck('email')->toArray();
        // $super_admin = Role::where('name', 'super-admin')->first();
        if (! in_array('admin@office.com', $db_admins)) {
            $super_admin = User::create([
                'name' => 'SuperAdmin',
                'last_name' => 'SuperAdmin',
                'user_name' => 'suadmin',
                'email' => 'admin@office.com',
                'email_verified_at' => null,
                'password' => Hash::make('manoj@123'),
                'cc_email' => 'admin@office.com',
                'branch_id' => 1,
                'remember_token' => 'nGSxaRYRMFm02Sqcaj8h3cc2deHdxFIn2O4cF57ce4L6Q76c0pJeeifN9TOO',
                'token' => 'nGSxaRYRMFm02Sqcaj8h3cc2deHdxFIn2O4cF57ce4L6Q76c0pJeeifN9TOO',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            // $super_admin->attachRole($super_admin);
        }
    }
}
