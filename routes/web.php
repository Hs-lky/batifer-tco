<?php

use App\Http\Controllers\DossierController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Saisie'))->name('saisie');
Route::get('/analyse', fn () => Inertia::render('Analyse'))->name('analyse');

Route::resource('dossiers', DossierController::class)
    ->only(['index', 'store', 'show', 'update', 'destroy']);

Route::get('/statistiques', [StatsController::class, 'index'])->name('statistiques');
Route::get('/produits', [StatsController::class, 'parProduit'])->name('produits');
Route::get('/fournisseurs', [StatsController::class, 'parFournisseur'])->name('fournisseurs');
Route::get('/parametres', fn () => Inertia::render('Parametres'))->name('parametres');
Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
Route::get('/export', fn () => Inertia::render('Export'))->name('export');
Route::get('/export/csv', [ExportController::class, 'csv'])->name('export.csv');
Route::get('/export/json', [ExportController::class, 'json'])->name('export.json');
