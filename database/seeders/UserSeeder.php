<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        $password = env('DEFAULT_ADMIN_PASSWORD', 'manoj@123');

        $user = User::firstOrCreate(
            ['email' => 'admin@office.com'],
            [
                'name' => 'SuperAdmin',
                'last_name' => 'SuperAdmin',
                'user_name' => 'suadmin',
                'email_verified_at' => null,
                'password' => Hash::make($password),
                'cc_email' => 'admin@office.com',
                'branch_id' => 1,
                'remember_token' => Str::random(60),
                'token' => Str::random(60),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // If Spatie's HasRoles trait is used on User, assign admin role (if exists)
        if (method_exists($user, 'assignRole')) {
            // prefer existing 'admin' role; if missing, it will throw — so check first
            if (class_exists(\Spatie\Permission\Models\Role::class)) {
                $adminRole = \Spatie\Permission\Models\Role::where('name', 'admin')->first();
                if ($adminRole) {
                    $user->assignRole($adminRole->name);
                }
            } else {
                // fallback if you use a custom Role model with assignRole available
                try {
                    $user->assignRole('admin');
                } catch (\Throwable $e) {
                    // silently ignore if role assignment not available
                }
            }
        }
    }
}
