<?php

namespace Database\Seeders;

use App\Models\Technician;
use Illuminate\Database\Seeder;

class TechnicianSeeder extends Seeder
{
    public function run(): void
    {
        $technicians = [
            [
                'name' => 'Paul Nkemdirim',
                'specialty' => 'Plomberie générale',
                'experience' => '8 ans',
                'rating' => 4.9,
                'jobs_count' => 312,
                'photo' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=200&auto=format&fit=crop&facepad=3',
                'is_available' => true,
            ],
            [
                'name' => 'François Biya',
                'specialty' => 'Chauffe-eau & Sanitaire',
                'experience' => '12 ans',
                'rating' => 5.0,
                'jobs_count' => 540,
                'photo' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?q=80&w=200&auto=format&fit=crop&facepad=3',
                'is_available' => true,
            ],
            [
                'name' => 'Samuel Eto',
                'specialty' => 'Installation & Dépannage',
                'experience' => '6 ans',
                'rating' => 4.8,
                'jobs_count' => 198,
                'photo' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=200&auto=format&fit=crop&facepad=3',
                'is_available' => false,
            ],
        ];

        foreach ($technicians as $technician) {
            Technician::updateOrCreate(['name' => $technician['name']], $technician);
        }
    }
}
