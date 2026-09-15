<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['name' => 'Omar Ali', 'email' => 'omar@example.test', 'company' => 'Northstar Labs'],
            ['name' => 'Sara Nabil', 'email' => 'sara@example.test', 'company' => 'Acme Retail'],
            ['name' => 'Youssef Adel', 'email' => 'youssef@example.test', 'company' => 'Delta Systems'],
        ];

        foreach ($customers as $customer) {
            $user = User::factory()->create([
                'name' => $customer['name'],
                'email' => $customer['email'],
            ]);

            Customer::factory()->create([
                'user_id' => $user->id,
                'company_name' => $customer['company'],
            ]);
        }
    }
}
