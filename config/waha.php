<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Konfigurasi waha session
    |--------------------------------------------------------------------------
    | Setiap sesi dapat memiliki nama, ID, dan nama dorongnya sendiri.
    | Sesi default digunakan ketika tidak ada sesi spesifik yang ditentukan.
    |
    */
    'default' => [
        'session' => 'pendaftaran',
    ],

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi sesi
    |--------------------------------------------------------------------------
    | Anda dapat menambahkan sesi baru dengan menambahkan entri baru di sini.
    | Setiap sesi dapat memiliki nama, ID, dan nama dorongnya sendiri.
    | ID sesi adalah ID unik untuk sesi WhatsApp yang digunakan untuk mengirim pesan.
    |
    */
    "sessions" => [
        "default" => [
            "name" => "default",
        ],

        "byu-ferry" => [
            "name" => "byu-ferry",
            "id" => "6285179699401@c.us",
            "pushName" => "Admin RSIA"
        ],

        "pendaftaran" => [
            "name" => "pendaftaran",
            "id" => "6285640009934@c.us",
            "pushName" => "RSIA AISYIYAH PEKAJANGAN"
        ]
    ],
];
