<?php

use App\Http\Controllers\ApiV1\OAuth2Controller;
use Illuminate\Support\Facades\Route;



Route::get('/redirect', [OAuth2Controller::class, 'authCodeRedirect']);
Route::post('/callback', [OAuth2Controller::class, 'authCodeToken']);
Route::post('/refresh', [OAuth2Controller::class, 'refreshToken']);
Route::post('/client-access', [
    OAuth2Controller::class, 'clientCredsToken'
]);
