<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Akun Admin Awal
    |--------------------------------------------------------------------------
    |
    | Dipakai oleh AdminSeeder. Jangan pernah mengisi password default di
    | repositori. Isi nilai ini di .env sebelum menjalankan seeder produksi.
    |
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrator Sekolah'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Akun Demo
    |--------------------------------------------------------------------------
    |
    | Hanya dipakai DemoSeeder. Tidak ada fallback ke "password" agar akun
    | demo tidak pernah memakai kredensial yang mudah ditebak.
    |
    */

    'demo_password' => env('DEMO_SEED_PASSWORD'),

];
