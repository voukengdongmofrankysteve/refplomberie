<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'client@refplomberie.cm'],
            [
                'name' => 'Jean Mbarga',
                'password' => Hash::make('password'),
                'role' => UserRole::Customer,
                'phone' => '+237 690 11 22 33',
                'address' => 'Bastos, Yaoundé',
                'email_verified_at' => now(),
            ],
        );

        $this->call([
            StoreSettingSeeder::class,
            AdminUserSeeder::class,
            CatalogSeeder::class,
            ShopCatalogSeeder::class,
            TechnicianSeeder::class,
            StorySeeder::class,
            FaqSeeder::class,
            TestimonialSeeder::class,
        ]);
    }
}
