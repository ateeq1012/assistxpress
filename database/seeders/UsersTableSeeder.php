<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = DB::table('users')->where('id', 1)->first();
        if (!$adminUser) {
            Schema::disableForeignKeyConstraints();

            DB::table('users')->updateOrInsert(
                ['email' => 'admin@example.com'], // Check for this email
                [
                    'name' => 'Admin',
                    'email_verified_at' => Carbon::now(),
                    'password' => Hash::make('1|*LQQZr79w('), // Hash the password
                    'remember_token' => Str::random(10),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'token' => Str::random(60),
                    'role_id' => 1,
                    'created_by' => 1, // Assuming the first user is the creator
                    'enabled' => true,
                ]
            );

            DB::table('roles')->where('id', 1)->update([
                'created_by' => 1
            ]);

            Schema::table('roles', function (Blueprint $table) {
                $table->integer('created_by')->change();
            });

            Schema::enableForeignKeyConstraints();
        }
    }
}
