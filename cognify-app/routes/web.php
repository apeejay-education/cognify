<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\Web\LeadFormController;

Route::get('/leads/create', [LeadFormController::class, 'create']);
Route::post('/leads', [LeadFormController::class, 'store']);

use App\Http\Controllers\Admin\LeadAdminController;

Route::prefix('admin')->group(function () {
    Route::get('/leads', [LeadAdminController::class, 'index']);
    Route::get('/leads/{lead}', [LeadAdminController::class, 'show'])->name('admin.leads.show');
    Route::post('/leads/{lead}/assign', [LeadAdminController::class, 'assign']);
    Route::post('/leads/{lead}/activities', [LeadAdminController::class, 'addActivity']);
});

