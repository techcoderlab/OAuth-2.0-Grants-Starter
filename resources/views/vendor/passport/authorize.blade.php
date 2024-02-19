<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }} - Authorization</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">

         <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>


   <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">

    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">
            {{ __('Authorization Request') }}
    </h2>

    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 ">
        {{ $client->name }} is requesting permission to access your account.
    </p>


        <!-- Scope List -->
    @if (count($scopes) > 0)
        <div class="scopes mt-3">
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                This application will be able to:
            </p>
            <ul>
                @foreach ($scopes as $scope)
                    <li>{{ $scope->description }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-4" style="display:flex;">
        <!-- Authorize Button -->
         <form style="width:50%; display:flex; justify-content:center;"   method="post"
            action="{{ route('passport.authorizations.approve') }}">
            @csrf
            <input type="hidden" name="state" value="{{ $request->state }}">
            <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">

            <x-primary-button style="width:100%; margin-right:5px;" >
                {{ __('Authorize') }}
            </x-primary-button>

        </form>
        <!-- Cancel Button -->
        <form style="width:50%; display:flex; justify-content:center;"  method="post"
            action="{{ route('passport.authorizations.deny') }}">
            @csrf
            @method('DELETE')
            <input type="hidden" name="state" value="{{ $request->state }}">
            <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <x-secondary-button style="width:100%; margin-left:5px;">
                {{ __('Cancel') }}
            </x-secondary-button>
        </form>



    </div>

</div>
         </div>

    </body>

    </html>
