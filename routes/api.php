<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Role;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('auth/register', App\Http\Controllers\Auth\RegisterController::class);

Route::get('/user/bookings', function (Request $request) {
    abort_unless($request->user()?->hasRole(Role::ROLE_USER), 403);

    return response()->json([]);
});

Route::get('/owner/properties', function (Request $request) {
    abort_unless($request->user()?->hasRole(Role::ROLE_OWNER), 403);

    return response()->json([]);
});
