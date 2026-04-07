<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CleaningTaskController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/design-system', function () {
    return Inertia::render('DesignSystem');
})->name('design-system');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Room Management
    Route::middleware('permission:view_rooms')->group(function () {
        Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
        Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
        Route::put('/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');
        Route::patch('/rooms/{room}/status', [RoomController::class, 'updateStatus'])->name('rooms.status');
    });

    // Guest Profiles
    Route::middleware('permission:view_guests')->group(function () {
        Route::get('/guests', [GuestController::class, 'index'])->name('guests.index');
        Route::get('/guests/{guest}', [GuestController::class, 'show'])->name('guests.show');
        Route::post('/guests', [GuestController::class, 'store'])->name('guests.store');
        Route::put('/guests/{guest}', [GuestController::class, 'update'])->name('guests.update');
        Route::delete('/guests/{guest}', [GuestController::class, 'destroy'])->name('guests.destroy');
    });

    // Reservations
    Route::middleware('permission:view_reservations')->group(function () {
        Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
        Route::get('/reservations/calendar', [ReservationController::class, 'calendar'])->name('reservations.calendar');
        Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
        Route::put('/reservations/{reservation}', [ReservationController::class, 'update'])->name('reservations.update');
        Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy'])->name('reservations.destroy');
        Route::post('/reservations/{reservation}/check-in', [ReservationController::class, 'checkIn'])->name('reservations.check-in');
        Route::post('/reservations/{reservation}/check-out', [ReservationController::class, 'checkOut'])->name('reservations.check-out');
        Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');
    });

    // Housekeeping
    Route::middleware('permission:view_housekeeping')->group(function () {
        Route::get('/housekeeping', [CleaningTaskController::class, 'index'])->name('housekeeping.index');
        Route::post('/housekeeping', [CleaningTaskController::class, 'store'])->name('housekeeping.store');
        Route::patch('/housekeeping/{cleaningTask}/status', [CleaningTaskController::class, 'updateStatus'])->name('housekeeping.status');
        Route::patch('/housekeeping/{cleaningTask}/assign', [CleaningTaskController::class, 'assign'])->name('housekeeping.assign');
        Route::post('/housekeeping/{cleaningTask}/issue', [CleaningTaskController::class, 'reportIssue'])->name('housekeeping.issue');
    });

    // POS Bar/Restaurant
    Route::middleware('permission:view_pos_orders')->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('/pos', [PosController::class, 'store'])->name('pos.store');
        Route::post('/pos/{posOrder}/complete', [PosController::class, 'complete'])->name('pos.complete');
        Route::post('/pos/{posOrder}/cancel', [PosController::class, 'cancel'])->name('pos.cancel');
    });

    // Admin-only: User Management + Settings
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{id}/restore', [UserController::class, 'restore'])->name('users.restore');

        // Settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/hotel', [SettingsController::class, 'updateHotel'])->name('settings.hotel');
        Route::put('/settings/financial', [SettingsController::class, 'updateFinancial'])->name('settings.financial');
        Route::put('/settings/housekeeping', [SettingsController::class, 'updateHousekeeping'])->name('settings.housekeeping');

        // Settings: Room Types
        Route::post('/settings/room-types', [SettingsController::class, 'storeRoomType'])->name('settings.room-types.store');
        Route::put('/settings/room-types/{roomType}', [SettingsController::class, 'updateRoomType'])->name('settings.room-types.update');
        Route::delete('/settings/room-types/{roomType}', [SettingsController::class, 'destroyRoomType'])->name('settings.room-types.destroy');

        // Settings: Menu
        Route::post('/settings/menu-categories', [SettingsController::class, 'storeMenuCategory'])->name('settings.menu-categories.store');
        Route::put('/settings/menu-categories/{menuCategory}', [SettingsController::class, 'updateMenuCategory'])->name('settings.menu-categories.update');
        Route::delete('/settings/menu-categories/{menuCategory}', [SettingsController::class, 'destroyMenuCategory'])->name('settings.menu-categories.destroy');
        Route::post('/settings/menu-items', [SettingsController::class, 'storeMenuItem'])->name('settings.menu-items.store');
        Route::put('/settings/menu-items/{menuItem}', [SettingsController::class, 'updateMenuItem'])->name('settings.menu-items.update');
        Route::patch('/settings/menu-items/{menuItem}/toggle', [SettingsController::class, 'toggleMenuItem'])->name('settings.menu-items.toggle');
        Route::delete('/settings/menu-items/{menuItem}', [SettingsController::class, 'destroyMenuItem'])->name('settings.menu-items.destroy');
    });
});

require __DIR__.'/auth.php';
