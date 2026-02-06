<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plan::updateOrCreate(
            ['slug' => 'teams'],
            [
                'name' => 'Teams',
                'price' => 89.90,
                'currency' => 'BRL',
                'description' => 'Ideal para pequenas empresas e times.',
                'features' => [
                    'Até 5 usuários',
                    'Gerenciamento de contratos',
                    'Automações básicas',
                    'Suporte por email',
                ],
                'max_users' => 5,
                'is_active' => true,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'business'],
            [
                'name' => 'Business',
                'price' => null, // Contact us
                'currency' => 'BRL',
                'description' => 'Para grandes empresas que precisam de mais.',
                'features' => [
                    'Usuários ilimitados',
                    'Automações avançadas',
                    'API dedicada',
                    'Gerente de conta',
                    'SSO',
                ],
                'max_users' => null,
                'is_active' => true,
            ]
        );
    }
}
