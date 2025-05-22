<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/app');

    // Tampilkan halaman selamat datang
    return view('welcome');
});
