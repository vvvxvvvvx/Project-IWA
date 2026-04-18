<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Applicatie instellingen
    |--------------------------------------------------------------------------
    */

    'app_name' => 'IWA Weather Dashboard',

    'base_url' => getenv('APP_BASE_URL') ?: 'http://127.0.0.1:8080',

    'storage_path' => __DIR__ . '/../storage/data',

    'session_name' => 'iwa_dashboard_session',

    /*
    |--------------------------------------------------------------------------
    | Default admin account
    |--------------------------------------------------------------------------
    */

    'default_user_email' => 'admin@example.com',
    'default_user_password' => 'admin123',

    /*
    |--------------------------------------------------------------------------
    | Database configuratie (PDO)
    |--------------------------------------------------------------------------
    |
    | Deze applicatie gebruikt PDO om met MySQL te verbinden.
    | Zorg dat je database bestaat in MySQL Workbench:
    |
    | CREATE DATABASE iwa_dashboard;
    |
    */

    'db' => [

        'driver' => getenv('DB_DRIVER') ?: 'mysql',

        'host' => getenv('DB_HOST') ?: '127.0.0.1',

        'port' => getenv('DB_PORT') ?: 3306,

        'database' => getenv('DB_DATABASE') ?: 'iwa_dashboard',

        'username' => getenv('DB_USERNAME') ?: 'root',

        'password' => getenv('DB_PASSWORD') ?: '1234',

        'charset' => 'utf8mb4',

    ],

];