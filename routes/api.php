<?php

use App\Http\Controllers\API\Auth\AuthAPIController;
use App\Http\Controllers\API\CustomerAPIController;
use App\Http\Controllers\API\MedicationAPIController;
use App\Http\Controllers\API\PrescriptionAPIController;
use App\Http\Controllers\API\PrescriptionDetailsAPIController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::post('signup',[AuthAPIController::class,'signup']);
    Route::post('login',[AuthAPIController::class,'login']);

    Route::middleware(['auth:sanctum'])->group(function () {

        Route::resource('medications', MedicationAPIController::class)->except(['create','edit']);
        Route::resource('customers', CustomerAPIController::class)->except(['create','edit']);
        Route::resource('prescriptions', PrescriptionAPIController::class)->except(['create','edit']);
        Route::resource('prescription_details', PrescriptionDetailsAPIController::class)->except(['create','edit']);

        Route::post('logout',[AuthAPIController::class,'logout']);
    });
});
