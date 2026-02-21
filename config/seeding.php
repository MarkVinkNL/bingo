<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Seeded super admin credentials
    |--------------------------------------------------------------------------
    |
    | Email and password for the super admin user created by db:seed.
    | Used to log in to the Filament panel at /admin.
    |
    */
    'superadmin_email' => env('SEED_SUPERADMIN_EMAIL', 'admin@example.com'),
    'superadmin_password' => env('SEED_SUPERADMIN_PASSWORD', 'password'),
];
