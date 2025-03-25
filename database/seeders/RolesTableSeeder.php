<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Date;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        $role = DB::table('roles')->where('id', 1)->first();


        if (!$role) {
            $data = [
                'name' => 'Admin',
                'enabled' => true,
                'created_by' => null, // SET temporarily as NULL, once Admin user is created, this will be set.
                'created_at' => now(), // Assuming you want to use the current timestamp
                'updated_at' => now(), // Same here
            ];
            DB::table('roles')->insert($data);
        }

        Schema::enableForeignKeyConstraints();
    }
}
