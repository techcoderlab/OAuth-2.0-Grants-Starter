<?php

use App\Http\Controllers\ApiV1\OAuth2Controller;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| OAuth 2.0 Routes
|--------------------------------------------------------------------------
|
| These routes handle the various OAuth 2.0 grant flows by proxying 
| requests to Laravel Passport.
|
*/

// Authorization Code Grant (Standard & PKCE)
Route::get('/redirect', [OAuth2Controller::class, 'authCodeRedirect'])->name('oauth.redirect');
Route::post('/callback', [OAuth2Controller::class, 'authCodeToken'])->name('oauth.callback');

// Client Credentials Grant
Route::post('/client-access', [OAuth2Controller::class, 'clientCredsToken'])->name('oauth.client');

// Password Grant (Legacy)
Route::post('/password-access', [OAuth2Controller::class, 'passwordToken'])->name('oauth.password');

// Refresh Token Grant
Route::post('/refresh', [OAuth2Controller::class, 'refreshToken'])->name('oauth.refresh');

// Implicit Grant (Legacy)
Route::get('/implicit-redirect', [OAuth2Controller::class, 'implicitRedirect'])->name('oauth.implicit');

