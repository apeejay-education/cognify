<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Counselor;
use App\Models\Lead;
use App\Models\User;

class DemoSeeder extends Seeder
{
    public function run()
    {
        Counselor::create(['name'=>'Asha Rao','email'=>'asha@example.com','phone'=>'+911234567890']);
        Counselor::create(['name'=>'Ravi Kumar','email'=>'ravi@example.com','phone'=>'+919876543210']);

        // create some leads
        Lead::create(['name'=>'Test Lead 1','email'=>'lead1@example.com','phone'=>'+911111111111','course_interest'=>'Math','score'=>10,'status'=>'new']);
        Lead::create(['name'=>'Test Lead 2','email'=>'lead2@example.com','phone'=>'+911222222222','course_interest'=>'Physics','score'=>20,'status'=>'new']);

        // create admin user
        User::factory()->create(['name'=>'Admin Tester','email'=>'admin@example.com','password'=>bcrypt('secret')]);
    }
}
