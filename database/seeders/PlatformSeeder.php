<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlatformSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $platforms = [
            [
                'name' => 'tiktok',
                'display_name' => 'TikTok',
                'name_ar' => 'تيك توك',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'snapchat',
                'display_name' => 'Snapchat',
                'name_ar' => 'سناب شات',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'youtube',
                'display_name' => 'YouTube',
                'name_ar' => 'يوتيوب',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'x',
                'display_name' => 'X (Twitter)',
                'name_ar' => 'إكس',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'instagram',
                'display_name' => 'Instagram',
                'name_ar' => 'إنستجرام',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'store',
                'display_name' => 'Store',
                'name_ar' => 'متجر',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('platforms')->insert($platforms);
    }
}
