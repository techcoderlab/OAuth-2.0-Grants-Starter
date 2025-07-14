# OAuth 2.0 Grants Starter - Laravel Documentation

## Table of Contents
1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Installation & Setup](#installation--setup)
4. [OAuth 2.0 Grant Types](#oauth-20-grant-types)
5. [Implementation Guide](#implementation-guide)
6. [Code Examples](#code-examples)
7. [Security Considerations](#security-considerations)
8. [Testing](#testing)
9. [Troubleshooting](#troubleshooting)
10. [Best Practices](#best-practices)

## Overview

This starter kit provides a comprehensive implementation of OAuth 2.0 grant types in Laravel, designed to help web developers quickly integrate secure authentication and authorization into their applications. The repository demonstrates the most commonly used OAuth 2.0 flows with practical, production-ready code examples.

### What is OAuth 2.0?

OAuth 2.0 is an authorization framework that enables applications to obtain limited access to user accounts on an HTTP service. It allows third-party applications to access user data without exposing user credentials.

### Supported Grant Types

This starter kit implements the following OAuth 2.0 grant types:

1. **Authorization Code Grant** - For web applications with server-side components
2. **Client Credentials Grant** - For machine-to-machine authentication
3. **Authorization Code Grant with PKCE** - For single-page applications and mobile apps
4. **Implicit Grant** - For client-side applications (legacy, not recommended)
5. **Password Grant** - For trusted first-party applications (deprecated)

## Prerequisites

Before using this starter kit, ensure you have:

- **PHP 8.1+**
- **Laravel 10.0+**
- **Composer** installed
- **MySQL/PostgreSQL** database
- Basic understanding of OAuth 2.0 concepts
- Knowledge of Laravel framework fundamentals

## Installation & Setup

### 1. Clone the Repository

```bash
git clone https://github.com/techcoderlab/OAuth-2.0-Grants-Starter.git
cd OAuth-2.0-Grants-Starter
```

### 2. Install Laravel Passport

```bash
composer require laravel/passport
```

### 3. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Configure your database and other environment variables in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=oauth_starter
DB_USERNAME=root
DB_PASSWORD=

# These will be generated in the next steps
PASSPORT_PERSONAL_ACCESS_CLIENT_ID=
PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=
PASSPORT_PASSWORD_CLIENT_ID=
PASSPORT_PASSWORD_CLIENT_SECRET=
```

### 4. Database Setup and Passport Installation

```bash
php artisan migrate
php artisan passport:install --uuids
php artisan passport:keys --force
```

**Important Note**: After running `passport:install`, copy the personal and password clients ID and secrets to your `.env` file:

```env
PASSPORT_PERSONAL_ACCESS_CLIENT_ID=your-personal-client-id
PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=your-personal-client-secret
PASSPORT_PASSWORD_CLIENT_ID=your-password-client-id
PASSPORT_PASSWORD_CLIENT_SECRET=your-password-client-secret
```

### 5. Publish Passport Views (Optional)

If you want to customize the authorization screen:

```bash
php artisan vendor:publish --tag=passport-views
```

## OAuth 2.0 Grant Types

### 1. Authorization Code Grant

**Use Case**: Web applications that can securely store client secrets.

**Flow Overview**:
1. User clicks "Login with OAuth"
2. App redirects to authorization server
3. User logs in and grants permissions
4. Authorization server redirects back with authorization code
5. App exchanges code for access token
6. App uses access token to access protected resources

**When to Use**:
- Traditional web applications
- Applications with server-side components
- When you can keep client secret secure

### 2. Client Credentials Grant

**Use Case**: Machine-to-machine authentication where no user interaction is required.

**Flow Overview**:
1. Application sends client ID and secret to authorization server
2. Authorization server validates credentials
3. Server returns access token
4. Application uses token to access API resources

**When to Use**:
- API-to-API communication
- Cron jobs and background services
- Server-to-server authentication

### 3. Authorization Code Grant with PKCE

**Use Case**: Single-page applications (SPAs) and mobile apps that cannot securely store client secrets.

**Flow Overview**:
1. Client generates code verifier and challenge
2. User is redirected to authorization server with challenge
3. User authorizes the application
4. Authorization server returns code to client
5. Client exchanges code + verifier for access token

**When to Use**:
- Single-page applications (React, Vue, Angular)
- Mobile applications
- Any public client that cannot store secrets

## Implementation Guide

### Setting Up Laravel Passport

Laravel Passport provides a complete OAuth 2.0 server implementation. Here's how to integrate it:

#### 1. Service Provider Registration

In `config/app.php`, add the Passport service provider:

```php
'providers' => [
    // Other service providers...
    Laravel\Passport\PassportServiceProvider::class,
],
```

#### 2. Authentication Configuration

In `config/auth.php`, set the API guard driver to `passport`:

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'passport',
        'provider' => 'users',
        'hash' => false,
    ],
],
```

#### 3. User Model Setup

Add the `HasApiTokens` trait to your User model:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    
    // Your existing model code...
}
```

#### 4. AuthServiceProvider Configuration

Add the following configuration to your `App\Providers\AuthServiceProvider` in the `boot` method:

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // Your existing policies...
    ];

    public function boot()
    {
        $this->registerPolicies();

        // Passport configuration
        Passport::loadKeysFrom(storage_path());
        Passport::hashClientSecrets();
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));
        
        // Define token scopes
        Passport::tokensCan([
            'user:read' => 'Read user information',
            'user:write' => 'Modify user information',
            'api:read' => 'Read API data',
            'api:write' => 'Write API data',
        ]);
    }
}
```

### Creating OAuth Clients

#### For Client Credentials Grant

Use the following Artisan command to create a client credentials client:

```bash
php artisan passport:client --client
```

**Important Note**: If you are using client_credentials grant, add a client middleware in your `app/Http/Kernel.php` file and use `client` middleware instead of `auth:api`:

```php
<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Laravel\Passport\Http\Middleware\CheckClientCredentials;

class Kernel extends HttpKernel
{
    // ... other middleware groups

    protected $middlewareAliases = [
        // ... other middleware aliases
        'client' => CheckClientCredentials::class,
    ];
}
```

#### For Other Grant Types

```bash
# Create a password grant client
php artisan passport:client --password

# Create an authorization code grant client
php artisan passport:client
```

## Code Examples

### 1. Authorization Code Grant Implementation

#### Authorization Controller

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    public function redirectToProvider(Request $request)
    {
        $state = Str::random(40);
        $request->session()->put('oauth_state', $state);
        
        $query = http_build_query([
            'client_id' => config('oauth.client_id'),
            'redirect_uri' => config('oauth.redirect_uri'),
            'response_type' => 'code',
            'scope' => 'user:read user:write',
            'state' => $state,
        ]);
        
        return redirect(config('oauth.authorization_url') . '?' . $query);
    }
    
    public function handleCallback(Request $request)
    {
        $state = $request->session()->pull('oauth_state');
        
        if (empty($state) || $state !== $request->state) {
            return redirect('/')->withErrors(['oauth' => 'Invalid state parameter']);
        }
        
        $response = Http::post(config('oauth.token_url'), [
            'grant_type' => 'authorization_code',
            'client_id' => config('oauth.client_id'),
            'client_secret' => config('oauth.client_secret'),
            'redirect_uri' => config('oauth.redirect_uri'),
            'code' => $request->code,
        ]);
        
        if ($response->successful()) {
            $tokens = $response->json();
            // Store tokens securely
            session(['access_token' => $tokens['access_token']]);
            return redirect('/dashboard');
        }
        
        return redirect('/')->withErrors(['oauth' => 'Failed to obtain access token']);
    }
}
```

#### Routes

```php
<?php

use App\Http\Controllers\Auth\OAuthController;

Route::get('/auth/oauth', [OAuthController::class, 'redirectToProvider']);
Route::get('/auth/oauth/callback', [OAuthController::class, 'handleCallback']);
```

### 2. Client Credentials Grant Implementation

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ApiService
{
    protected $accessToken;
    
    public function __construct()
    {
        $this->accessToken = $this->getAccessToken();
    }
    
    private function getAccessToken()
    {
        $response = Http::post(config('oauth.token_url'), [
            'grant_type' => 'client_credentials',
            'client_id' => config('oauth.client_id'),
            'client_secret' => config('oauth.client_secret'),
            'scope' => 'api:read api:write',
        ]);
        
        if ($response->successful()) {
            return $response->json()['access_token'];
        }
        
        throw new \Exception('Failed to obtain access token');
    }
    
    public function makeApiCall($endpoint, $method = 'GET', $data = [])
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Accept' => 'application/json',
        ])->$method(config('oauth.api_base_url') . $endpoint, $data);
        
        return $response->json();
    }
}
```

### 3. PKCE Implementation for SPAs

#### Frontend JavaScript

#### Step 6: Frontend PKCE Implementation

```javascript
class OAuthPKCE {
    constructor(clientId, redirectUri, authorizationUrl, tokenUrl) {
        this.clientId = clientId;
        this.redirectUri = redirectUri;
        this.authorizationUrl = authorizationUrl;
        this.tokenUrl = tokenUrl;
    }
    
    async generateCodeChallenge(codeVerifier) {
        const encoder = new TextEncoder();
        const data = encoder.encode(codeVerifier);
        const digest = await crypto.subtle.digest('SHA-256', data);
        return btoa(String.fromCharCode(...new Uint8Array(digest)))
            .replace(/\+/g, '-')
            .replace(/\//g, '_')
            .replace(/=/g, '');
    }
    
    generateCodeVerifier() {
        const array = new Uint8Array(32);
        crypto.getRandomValues(array);
        return btoa(String.fromCharCode(...array))
            .replace(/\+/g, '-')
            .replace(/\//g, '_')
            .replace(/=/g, '');
    }
    
    async startAuthFlow() {
        const codeVerifier = this.generateCodeVerifier();
        const codeChallenge = await this.generateCodeChallenge(codeVerifier);
        
        localStorage.setItem('oauth_code_verifier', codeVerifier);
        
        const params = new URLSearchParams({
            client_id: this.clientId,
            redirect_uri: this.redirectUri,
            response_type: 'code',
            scope: 'user:read user:write',
            code_challenge: codeChallenge,
            code_challenge_method: 'S256',
            state: this.generateState()
        });
        
        window.location.href = `${this.authorizationUrl}?${params}`;
    }
    
    async handleCallback() {
        const urlParams = new URLSearchParams(window.location.search);
        const code = urlParams.get('code');
        const codeVerifier = localStorage.getItem('oauth_code_verifier');
        
        if (!code || !codeVerifier) {
            throw new Error('Missing authorization code or code verifier');
        }
        
        const response = await fetch(this.tokenUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                grant_type: 'authorization_code',
                client_id: this.clientId,
                code: code,
                redirect_uri: this.redirectUri,
                code_verifier: codeVerifier
            })
        });
        
        const tokens = await response.json();
        
        if (response.ok) {
            localStorage.setItem('access_token', tokens.access_token);
            localStorage.setItem('refresh_token', tokens.refresh_token);
            localStorage.removeItem('oauth_code_verifier');
            return tokens;
        }
        
        throw new Error('Failed to exchange code for tokens');
    }
    
    generateState() {
        const array = new Uint8Array(16);
        crypto.getRandomValues(array);
        return btoa(String.fromCharCode(...array));
    }
}
```

### 4. Middleware for API Protection

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\Token;

class ValidateOAuthToken
{
    public function handle(Request $request, Closure $next, ...$scopes)
    {
        if (!$request->bearerToken()) {
            return response()->json(['error' => 'Token not provided'], 401);
        }
        
        $token = Token::findToken($request->bearerToken());
        
        if (!$token || $token->revoked) {
            return response()->json(['error' => 'Invalid token'], 401);
        }
        
        if ($token->expires_at < now()) {
            return response()->json(['error' => 'Token expired'], 401);
        }
        
        // Check scopes if provided
        if (!empty($scopes)) {
            $tokenScopes = $token->scopes;
            foreach ($scopes as $scope) {
                if (!in_array($scope, $tokenScopes)) {
                    return response()->json(['error' => 'Insufficient scope'], 403);
                }
            }
        }
        
        return $next($request);
    }
}
```

## Security Considerations

### 1. Token Storage

- **Server-side**: Store tokens securely in encrypted database columns
- **Client-side**: Use secure HTTP-only cookies or secure storage APIs
- **Never** store tokens in local storage for sensitive applications

### 2. HTTPS Requirements

Always use HTTPS in production:

```php
// Force HTTPS in production
if (app()->environment('production')) {
    \URL::forceScheme('https');
}
```

### 3. Token Expiration

Configure appropriate token lifetimes:

```php
// In AuthServiceProvider
public function boot()
{
    $this->registerPolicies();
    
    Passport::tokensExpireIn(now()->addMinutes(60));
    Passport::refreshTokensExpireIn(now()->addDays(30));
}
```

### 4. Scope Management

Define and enforce appropriate scopes:

```php
// In AuthServiceProvider
Passport::tokensCan([
    'user:read' => 'Read user information',
    'user:write' => 'Modify user information',
    'api:read' => 'Read API data',
    'api:write' => 'Write API data',
]);
```

### 5. Rate Limiting

Implement rate limiting for OAuth endpoints:

```php
// In routes/api.php
Route::middleware(['throttle:60,1'])->group(function () {
    Route::post('/oauth/token', '\Laravel\Passport\Http\Controllers\AccessTokenController@issueToken');
});
```

## Testing

### Unit Testing OAuth Flows

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use App\Models\User;

class OAuthTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_authorization_code_flow()
    {
        $user = User::factory()->create();
        
        $response = $this->get('/auth/oauth');
        
        $response->assertStatus(302);
        $response->assertSessionHas('oauth_state');
    }
    
    public function test_client_credentials_flow()
    {
        $response = $this->post('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => 'test-client',
            'client_secret' => 'test-secret',
            'scope' => 'api:read'
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_in'
        ]);
    }
    
    public function test_protected_route_requires_token()
    {
        $response = $this->get('/api/user');
        $response->assertStatus(401);
    }
    
    public function test_protected_route_with_valid_token()
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['user:read']);
        
        $response = $this->get('/api/user');
        $response->assertStatus(200);
    }
}
```

### Integration Testing

```php
<?php

namespace Tests\Integration;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Client;

class OAuthIntegrationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_full_authorization_flow()
    {
        // Create OAuth client
        $client = Client::factory()->create([
            'redirect' => 'http://localhost/callback'
        ]);
        
        // Step 1: Get authorization code
        $authResponse = $this->get('/oauth/authorize?' . http_build_query([
            'client_id' => $client->id,
            'redirect_uri' => 'http://localhost/callback',
            'response_type' => 'code',
            'scope' => 'user:read',
            'state' => 'random-state'
        ]));
        
        // Step 2: Exchange code for token
        $tokenResponse = $this->post('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'redirect_uri' => 'http://localhost/callback',
            'code' => 'authorization-code'
        ]);
        
        $tokenResponse->assertJsonStructure([
            'access_token',
            'refresh_token',
            'token_type',
            'expires_in'
        ]);
    }
}
```

## Troubleshooting

### Common Issues and Solutions

#### 1. "Invalid Client" Error

**Problem**: Client ID or secret is incorrect.

**Solution**:
```bash
# Verify client credentials
php artisan passport:client --show
```

#### 2. "Invalid Grant" Error

**Problem**: Authorization code has expired or been used.

**Solution**:
- Ensure codes are used immediately after generation
- Check code expiration settings
- Verify redirect URI matches exactly

#### 3. "Invalid Scope" Error

**Problem**: Requested scope is not available or properly configured.

**Solution**:
```php
// In AuthServiceProvider, define available scopes
Passport::tokensCan([
    'user:read' => 'Read user information',
    'user:write' => 'Modify user information',
]);
```

#### 4. Token Not Found in Request

**Problem**: Bearer token not properly sent in Authorization header.

**Solution**:
```javascript
// Correct way to send token
fetch('/api/protected', {
    headers: {
        'Authorization': `Bearer ${accessToken}`,
        'Accept': 'application/json'
    }
});
```

#### 5. CORS Issues with SPA

**Problem**: Cross-origin requests blocked.

**Solution**:
```php
// In config/cors.php
'paths' => ['api/*', 'oauth/*'],
'allowed_methods' => ['*'],
'allowed_origins' => ['http://localhost:3000'],
'allowed_headers' => ['*'],
'supports_credentials' => true,
```

### Debug Mode

Enable debug mode for detailed error messages:

```php
// In .env for development only
APP_DEBUG=true
PASSPORT_DEBUG=true
```

## Best Practices

### 1. Security Best Practices

- **Always use HTTPS** in production
- **Implement proper CSRF protection** for web flows
- **Use short-lived access tokens** (15-60 minutes)
- **Implement token refresh logic** for long-running applications
- **Store secrets securely** using environment variables
- **Validate all input parameters** on OAuth endpoints
- **Implement proper logging** for security events

### 2. Performance Optimization

- **Cache OAuth client information** to reduce database queries
- **Use Redis for token storage** in high-traffic applications
- **Implement connection pooling** for database connections
- **Use CDN for static assets** in OAuth flows
- **Optimize token validation** with caching strategies

### 3. User Experience

- **Provide clear error messages** for OAuth failures
- **Implement proper loading states** during OAuth flows
- **Allow users to revoke tokens** through settings
- **Provide token management interface** for power users
- **Implement graceful token refresh** in SPAs

### 4. Monitoring and Logging

```php
// Log OAuth events for monitoring
Log::info('OAuth token issued', [
    'client_id' => $client->id,
    'user_id' => $user->id,
    'scopes' => $scopes,
    'ip_address' => request()->ip()
]);
```

### 5. Code Organization

- **Separate OAuth logic** into dedicated service classes
- **Use form requests** for OAuth parameter validation
- **Implement proper error handling** with custom exceptions
- **Create reusable OAuth components** for frontend
- **Follow Laravel coding standards** throughout the application

---

This documentation provides a comprehensive guide for implementing OAuth 2.0 grants in Laravel applications. For additional support or questions, please refer to the Laravel Passport documentation or create an issue in the repository.
