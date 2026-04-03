<?php

use App\Http\Controllers\Site\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Site Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/assinar', [HomeController::class, 'assinar'])->name('assinar');
Route::get('/obrigado', [HomeController::class, 'obrigado'])->name('obrigado');
Route::get('/blog', [HomeController::class, 'blog'])->name('blog');
Route::get('/blog/{slug}', [HomeController::class, 'blogPost'])->name('blog.post');
Route::get('/sobre', [HomeController::class, 'sobre'])->name('sobre');
Route::get('/suporte', [HomeController::class, 'suporte'])->name('suporte');
Route::get('/termos-de-uso', [HomeController::class, 'termos'])->name('termos');
Route::get('/politica-de-privacidade', [HomeController::class, 'privacidade'])->name('privacidade');
Route::get('/politica-de-reembolso', [HomeController::class, 'reembolso'])->name('reembolso');
Route::get('/faq', [HomeController::class, 'faq'])->name('faq');
