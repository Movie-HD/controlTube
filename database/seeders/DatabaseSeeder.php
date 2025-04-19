<?php

namespace Database\Seeders;

use App\Models\AccountStatus;
use App\Models\Resolution;
use App\Models\User;
use App\Models\YoutubePage;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        # Seed para estados de cuenta
        $accountStatuses = [
            'Nuevo',
            'Activo',
            'Suspendido',
            'Inactivo',
            'Pendiente de verificación',
        ];

        foreach ($accountStatuses as $status) {
            AccountStatus::firstOrCreate(['name' => $status]);
        }

        # Seed para resoluciones de pantalla
        $resolutions = [
            '1280 x 720',
            '1280 x 800',
            '1280 x 1024',
            '1366 x 768',
            '1600 x 900',
            '1920 x 1080',
        ];

        foreach ($resolutions as $resolution) {
            Resolution::firstOrCreate(['name' => $resolution]);
        }

        # Seed para páginas de YouTube
        $youtubePages = [
            ['name' => 'YouTube', 'url' => 'https://www.youtube.com'],
            ['name' => 'Gmail', 'url' => 'https://studio.youtube.com'],
            ['name' => 'Facebook', 'url' => 'https://music.youtube.com'],
            ['name' => 'Tiktok', 'url' => 'https://www.youtubekids.com'],
        ];

        foreach ($youtubePages as $page) {
            YoutubePage::firstOrCreate($page);
        }
    }
}
