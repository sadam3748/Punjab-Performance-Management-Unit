<?php

namespace Database\Seeders;

class UserSeeder extends PpmfDummyUserSeeder
{
    public function run(): void
    {
        (new AdminUserSeeder)->run();
        parent::run();
    }
}
