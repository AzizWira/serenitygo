<?php
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\Owner\{DashboardController,BatchController,TaxController,SettingsController,ImportController};
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/track', [PublicController::class, 'tracking'])->middleware('throttle:30,1')->name('tracking');
Route::get('/api/calculate', [PublicController::class, 'calculate'])->middleware('throttle:30,1')->name('calculate');
Route::middleware('guest')->group(function () {
    Route::get('/secretkya', [LoginController::class, 'create'])->name('login');
    Route::post('/secretkya', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.attempt');
});
Route::middleware('auth')->prefix('owner')->name('owner.')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/batches', [BatchController::class, 'index'])->name('batches.index');
    Route::get('/batches/create', [BatchController::class, 'create'])->name('batches.create');
    Route::post('/batches', [BatchController::class, 'store'])->name('batches.store');
    Route::get('/batches/{batch}', [BatchController::class, 'show'])->name('batches.show');
    Route::get('/batches/{batch}/edit', [BatchController::class, 'edit'])->name('batches.edit');
    Route::put('/batches/{batch}', [BatchController::class, 'update'])->name('batches.update');
    Route::delete('/batches/{batch}', [BatchController::class, 'destroy'])->name('batches.destroy');
    Route::post('/batches/{batch}/status', [BatchController::class, 'updateStatus'])->name('batches.status');
    Route::post('/batches/{batch}/items', [BatchController::class, 'addItem'])->name('batches.items.store');
    Route::get('/batches/{batch}/items/{item}/edit', [BatchController::class, 'editItem'])->name('batches.items.edit');
    Route::put('/batches/{batch}/items/{item}', [BatchController::class, 'updateItem'])->name('batches.items.update');
    Route::delete('/batches/{batch}/items/{item}', [BatchController::class, 'deleteItem'])->name('batches.items.destroy');
    Route::get('/taxes', [TaxController::class, 'index'])->name('taxes.index');
    Route::post('/batches/{batch}/taxes', [TaxController::class, 'store'])->name('taxes.store');
    Route::put('/taxes/{tax}', [TaxController::class, 'update'])->name('taxes.update');
    Route::delete('/taxes/{tax}', [TaxController::class, 'destroy'])->name('taxes.destroy');
    Route::get('/settings/rates', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/rates/{rate}', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/rates', [SettingsController::class, 'store'])->name('settings.store');
    Route::put('/settings/rounding', [SettingsController::class, 'updateRounding'])->name('settings.rounding');
    Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
    Route::post('/imports/preview', [ImportController::class, 'preview'])->name('imports.preview');
    Route::post('/imports/commit', [ImportController::class, 'commit'])->name('imports.commit');
});
