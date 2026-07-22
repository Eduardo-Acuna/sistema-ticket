<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\SeatController;
use App\Http\Controllers\Api\SectorController;
use App\Http\Controllers\Api\VenueController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/events', [EventController::class, 'index']);
Route::get('/events/featured', [EventController::class, 'featured']);
Route::get('/events/{event}', [EventController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/venues', [VenueController::class, 'index']);

Route::get('/sectors/{sector}/layout', [SeatController::class, 'getLayout']);

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (usuario autenticado)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/user/tickets', [OrderController::class, 'userTickets']);

    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::get('/orders/{order}/tickets', [OrderController::class, 'tickets']);

    Route::post('/sectors/{sectorId}/reserve', [SeatController::class, 'reserveSeats']);
    Route::post('/sectors/{sectorId}/release', [SeatController::class, 'releaseSeats']);

    /*
    |--------------------------------------------------------------------------
    | RUTAS PROTEGIDAS (solo admin)
    |--------------------------------------------------------------------------
    */
    Route::middleware('admin')->group(function () {
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/dashboard/sales', [DashboardController::class, 'sales']);

        // Eventos (index con ?admin=1 para ver todos los estados)
        Route::post('/events', [EventController::class, 'store']);
        Route::put('/events/{event}', [EventController::class, 'update']);
        Route::delete('/events/{event}', [EventController::class, 'destroy']);
        Route::post('/events/{event}/publish', [EventController::class, 'publish']);
        Route::post('/events/{event}/duplicate', [EventController::class, 'duplicate']);

        // Categorías
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        // Lugares
        Route::post('/venues', [VenueController::class, 'store']);
        Route::put('/venues/{venue}', [VenueController::class, 'update']);
        Route::delete('/venues/{venue}', [VenueController::class, 'destroy']);

        // Sectores
        Route::get('/events/{event}/sectors', [SectorController::class, 'index']);
        Route::post('/events/{event}/sectors', [SectorController::class, 'store']);
        Route::put('/sectors/{sector}', [SectorController::class, 'update']);
        Route::delete('/sectors/{sector}', [SectorController::class, 'destroy']);

        // Generación de asientos
        Route::post('/sectors/{sectorId}/generate-seats', [SeatController::class, 'generate']);
    });
});
