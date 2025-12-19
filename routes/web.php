<?php

use App\Http\Controllers\TransferController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/transfer', [TransferController::class, 'transfer'])
    ->name('transfer');
