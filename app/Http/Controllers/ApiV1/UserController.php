<?php

namespace App\Http\Controllers\ApiV1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function getUserNames(Request $request)
    {
        return response()
            ->json(['list' => User::all(['name'])], Response::HTTP_OK);
    }
}
