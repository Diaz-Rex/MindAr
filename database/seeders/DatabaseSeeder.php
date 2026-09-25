<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\AuthorityModel;
use App\Models\UrlModel;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $data = User::firstOrCreate(
            ['email' => 'superadmin@gomecogroup.com'],
            [
                'name' => 'Super Admin',
                'identification' => '5sfg4s4f8wb1x',
                'password' => 'GMC)^$2024',
                'active' => 1,
            ]
        );

        $links = [
            '/',
            'mindar',
            'playground',
            'dashboard',
            'sign-in',
            'sign-out',
            'profile',
            'superadmin',
        ];

        foreach ($links as $linkName) {
            $link = UrlModel::firstOrCreate(['linkName' => $linkName]);

            AuthorityModel::firstOrCreate([
                'linkName_id' => $link->id,
                'user_id' => $data->id,
            ]);
        }
    }
}
