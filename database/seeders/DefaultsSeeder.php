<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Carbon\Carbon;
class DefaultsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Create default roles
        $role = DB::table('roles')->where('id', 1)->first();
        if (!$role) {
            Schema::disableForeignKeyConstraints();
            $data = [
                'name' => 'Admin',
                'enabled' => true,
                'created_by' => null, // SET temporarily as NULL, once Admin user is created, this will be set.
                'created_at' => now(), // Assuming you want to use the current timestamp
                'updated_at' => now(), // Same here
            ];
            DB::table('roles')->insert($data);
            Schema::enableForeignKeyConstraints();
        }


        $adminUser = DB::table('users')->where('id', 1)->first();
        if (!$adminUser) {
            Schema::disableForeignKeyConstraints();

            DB::table('users')->updateOrInsert(
                ['email' => 'admin@innexiv.com'], // Check for this email
                [
                    'name' => 'System',
                    'email_verified_at' => Carbon::now(),
                    'password' => Hash::make('1|*LQLZr79w('), // Hash the password
                    'remember_token' => Str::random(10),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'token' => Str::random(60),
                    'role_id' => 1,
                    'created_by' => 1, // Assuming the first user is the creator
                    'enabled' => true,
                ]
            );
            DB::table('users')->updateOrInsert(
                ['email' => 'admin@innexiv.com'], // Check for this email
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




        // Check if there are any statuses in the table
        $statuses = DB::table('statuses')->first();
        // Check if there are any service priorities in the correct table
        $service_priorities = DB::table('service_priorities')->first();

        // Insert default statuses if the table is empty
        if (!$statuses) {
            $statusData = [
                [ 'name' => 'Todo',            'color' => '#a3a1a1', 'type' => 1, 'order' => 1, 'created_by' => 1, 'created_at' => now() ], // Light Gray
                [ 'name' => 'In Progress',     'color' => '#6ea0d7', 'type' => 2, 'order' => 2, 'created_by' => 1, 'created_at' => now() ], // Soft Blue
                [ 'name' => 'Hold',            'color' => '#f0c674', 'type' => 2, 'order' => 3, 'created_by' => 1, 'created_at' => now() ], // Muted Yellow
                [ 'name' => 'Resolved',        'color' => '#8ccfa8', 'type' => 3, 'order' => 4, 'created_by' => 1, 'created_at' => now() ], // Soft Green
                [ 'name' => 'Closed',          'color' => '#b08ea6', 'type' => 4, 'order' => 5, 'created_by' => 1, 'created_at' => now() ], // Light Mauve
            ];
            DB::table('statuses')->insert($statusData);
        }

        // Insert default service priorities if the table is empty
        if (!$service_priorities) {
            $priorityData = [
                [ 'name' => 'Normal',          'color' => '#65c97b', 'order' => 1, 'created_by' => 1, 'created_at' => now() ],
                [ 'name' => 'Minor',           'color' => '#00acff', 'order' => 2, 'created_by' => 1, 'created_at' => now() ],
                [ 'name' => 'Major',           'color' => '#ff8d1c', 'order' => 3, 'created_by' => 1, 'created_at' => now() ],
                [ 'name' => 'Critical',        'color' => '#ff6a6a', 'order' => 4, 'created_by' => 1, 'created_at' => now() ],
            ];
            DB::table('service_priorities')->insert($priorityData);
        }

        Schema::enableForeignKeyConstraints();
    }
}
