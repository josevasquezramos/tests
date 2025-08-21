<?php

use App\Http\Controllers\Pdf\MaletaPdfController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', fn () => view('welcome'));

Route::get('/pdf/maletas/{maleta}', [MaletaPdfController::class, 'show'])
    ->name('pdf.maleta');