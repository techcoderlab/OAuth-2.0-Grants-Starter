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

## API Usage Guide

This section provides practical examples of how to interact with the OAuth 2.0 endpoints provided by this starter kit using `curl`.

### 1. Authorization Code Grant (Standard)

Used for web applications that can securely store client secrets.

**Step 1: Redirect to Authorization Server**
Direct the user to this URL in their browser:

```bash
# Replace with your actual client_id and redirect_uri
http://localhost:8000/oauth/redirect?client_id=YOUR_CLIENT_ID&redirect_uri=YOUR_REDIRECT_URI&scope=user:read
```

**Step 2: Exchange Code for Token (Callback)**
After the user authorizes, they will be redirected back with a `code`. Use this to get the access token:

```bash
curl -X POST http://localhost:8000/oauth/callback \
     -H "Accept: application/json" \
     -d "client_id=YOUR_CLIENT_ID" \
     -d "client_secret=YOUR_CLIENT_SECRET" \
     -d "redirect_uri=YOUR_REDIRECT_URI" \
     -d "code=AUTHORIZATION_CODE_FROM_STEP_1" \
     -d "state=STATE_FROM_STEP_1"
```

### 2. Authorization Code Grant with PKCE

Recommended for mobile and single-page applications.

**Step 1: Redirect with PKCE**

```bash
http://localhost:8000/oauth/redirect?client_id=YOUR_CLIENT_ID&redirect_uri=YOUR_REDIRECT_URI&use_pkce=true
```

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
        const digest = await crypto.subtle.digest("SHA-256", data);
        return btoa(String.fromCharCode(...new Uint8Array(digest)))
            .replace(/\+/g, "-")
            .replace(/\//g, "_")
            .replace(/=/g, "");
    }

    generateCodeVerifier() {
        const array = new Uint8Array(32);
        crypto.getRandomValues(array);
        return btoa(String.fromCharCode(...array))
            .replace(/\+/g, "-")
            .replace(/\//g, "_")
            .replace(/=/g, "");
    }

    async startAuthFlow() {
        const codeVerifier = this.generateCodeVerifier();
        const codeChallenge = await this.generateCodeChallenge(codeVerifier);

        localStorage.setItem("oauth_code_verifier", codeVerifier);

        const params = new URLSearchParams({
            client_id: this.clientId,
            redirect_uri: this.redirectUri,
            response_type: "code",
            scope: "user:read user:write",
            code_challenge: codeChallenge,
            code_challenge_method: "S256",
            state: this.generateState(),
        });

        window.location.href = `${this.authorizationUrl}?${params}`;
    }

    async handleCallback() {
        const urlParams = new URLSearchParams(window.location.search);
        const code = urlParams.get("code");
        const codeVerifier = localStorage.getItem("oauth_code_verifier");

        if (!code || !codeVerifier) {
            throw new Error("Missing authorization code or code verifier");
        }

        const response = await fetch(this.tokenUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                grant_type: "authorization_code",
                client_id: this.clientId,
                code: code,
                redirect_uri: this.redirectUri,
                code_verifier: codeVerifier,
            }),
        });

        const tokens = await response.json();

        if (response.ok) {
            localStorage.setItem("access_token", tokens.access_token);
            localStorage.setItem("refresh_token", tokens.refresh_token);
            localStorage.removeItem("oauth_code_verifier");
            return tokens;
        }

        throw new Error("Failed to exchange code for tokens");
    }

    generateState() {
        const array = new Uint8Array(16);
        crypto.getRandomValues(array);
        return btoa(String.fromCharCode(...array));
    }
}
```

**Step 2: Exchange Code (Callback)**
The controller handles the `code_verifier` internally via session for this starter kit.

```bash
curl -X POST http://localhost:8000/oauth/callback \
     -H "Accept: application/json" \
     -d "client_id=YOUR_CLIENT_ID" \
     -d "redirect_uri=YOUR_REDIRECT_URI" \
     -d "code=AUTHORIZATION_CODE" \
     -d "state=STATE"
```

### 3. Client Credentials Grant

For machine-to-machine communication.

```bash
curl -X POST http://localhost:8000/oauth/client-access \
     -H "Accept: application/json" \
     -d "client_id=YOUR_CLIENT_ID" \
     -d "client_secret=YOUR_CLIENT_SECRET" \
     -d "scope=api:read"
```

### 4. Password Grant (Legacy/Trusted Apps)

```bash
curl -X POST http://localhost:8000/oauth/password-access \
     -H "Accept: application/json" \
     -d "client_id=YOUR_CLIENT_ID" \
     -d "client_secret=YOUR_CLIENT_SECRET" \
     -d "username=user@example.com" \
     -d "password=your-password" \
     -d "scope=user:read"
```

### 5. Refresh Token Grant

```bash
curl -X POST http://localhost:8000/oauth/refresh \
     -H "Accept: application/json" \
     -d "client_id=YOUR_CLIENT_ID" \
     -d "client_secret=YOUR_CLIENT_SECRET" \
     -d "refresh_token=YOUR_REFRESH_TOKEN"
```

### 6. Implicit Grant (Legacy/Not Recommended)

Direct the user to:

```bash
http://localhost:8000/oauth/implicit-redirect?client_id=YOUR_CLIENT_ID&redirect_uri=YOUR_REDIRECT_URI&scope=user:read
```

---

## Security Considerations

1. **HTTPS**: Always use HTTPS in production.
2. **Secrets**: Never expose `client_secret` in frontend applications. Use PKCE for public clients.
3. **State**: Always validate the `state` parameter to prevent CSRF attacks.
4. **Scopes**: Use the minimum required scopes for each token.

## Testing

You can test these flows using the built-in Laravel testing suite or tools like Postman.

```bash
php artisan test
```

## Troubleshooting

- **401 Unauthorized**: Check if your `client_id` or `client_secret` is correct.
- **403 Forbidden**: Invalid `state` parameter or insufficient scopes.
- **405 Method Not Allowed**: Ensure you are using the correct HTTP method (POST for token exchanges).

## Best Practices

### 1. Security Best Practices

- **Use PKCE** for all public clients (mobile, SPAs).
- **Always use HTTPS** in production environments.
- **Implement CSRF protection** for all authorization code flows.
- **Use short-lived access tokens** and refresh tokens for better security.
- **Store secrets securely** using environment variables or a secrets manager.
- **Validate all input parameters** strictly on OAuth endpoints.

### 2. Performance & UX

- **Cache OAuth client information** to reduce database overhead.
- **Implement graceful token refresh** logic in your frontend applications.
- **Provide clear error messages** to help users and developers debug issues.
- **Allow users to revoke tokens** via their account settings.

### 3. Monitoring & Logging

- **Log OAuth events** (issue, refresh, revoke) for auditing purposes.
- **Monitor for suspicious activity**, such as high-frequency login failures.

---

This documentation provides a comprehensive guide for implementing OAuth 2.0 grants in Laravel applications. For additional support or questions, please refer to the [Laravel Passport Documentation](https://laravel.com/docs/passport).
