<?php

use App\Http\Controllers\ApiV1\UserController;
use Illuminate\Support\Facades\Route;


Route::post('/names', [UserController::class, 'getUserNames']);
