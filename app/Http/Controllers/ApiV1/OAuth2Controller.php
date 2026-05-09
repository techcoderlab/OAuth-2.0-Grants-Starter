<?php

namespace App\Http\Controllers\ApiV1;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * OAuth2Controller handles the orchestration of various OAuth 2.0 grant flows.
 * It acts as a proxy/wrapper around Laravel Passport's internal routes.
 * 
 * Pillars: P1 (Architecture), P2 (Security), P6 (Resilience)
 */
class OAuth2Controller extends Controller
{
    /**
     * Redirect for Authorization Code Flow (Standard or PKCE).
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function authCodeRedirect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put('state', $state);

        $params = [
            'response_type' => 'code',
            'client_id' => $request->client_id,
            'redirect_uri' => $request->redirect_uri,
            'state' => $state,
            'scope' => $request->scope ?? '',
        ];

        // Handle PKCE if code_challenge is desired or if we want to enforce it
        if ($request->has('use_pkce') || $request->has('code_challenge')) {
            $code_verifier = Str::random(128);
            $request->session()->put('code_verifier', $code_verifier);

            $codeChallenge = strtr(rtrim(
                base64_encode(hash('sha256', $code_verifier, true)),
                '='
            ), '+/', '-_');

            $params['code_challenge'] = $codeChallenge;
            $params['code_challenge_method'] = 'S256';
        }

        $query = http_build_query($params);

        return redirect(config('app.url') . '/oauth/authorize?' . $query);
    }

    /**
     * Exchange authorization code for an access token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function authCodeToken(Request $request): JsonResponse
    {
        $state = $request->session()->pull('state');
        $codeVerifier = $request->session()->pull('code_verifier');

        // Security check: Validate state parameter to prevent CSRF.
        if (empty($state) || $state !== $request->state) {
            return response()->json([
                'error' => 'invalid_state',
                'message' => 'The state parameter is invalid or missing.'
            ], 403);
        }

        return $this->proxy($request, 'authorization_code', [
            'client_id' => $request->client_id,
            'client_secret' => $request->client_secret,
            'redirect_uri' => $request->redirect_uri,
            'code' => $request->code,
            'code_verifier' => $codeVerifier, // Will be null if not using PKCE
        ]);
    }

    /**
     * Getting an access token using a Refresh Token Grant flow.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refreshToken(Request $request): JsonResponse
    {
        return $this->proxy($request, 'refresh_token', [
            'refresh_token' => $request->refresh_token,
            'client_id' => $request->client_id,
            'client_secret' => $request->client_secret,
            'scope' => $request->scope ?? '',
        ]);
    }

    /**
     * Getting an access token using a Client Credentials Grant flow.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clientCredsToken(Request $request): JsonResponse
    {
        return $this->proxy($request, 'client_credentials', [
            'client_id' => $request->client_id,
            'client_secret' => $request->client_secret,
            'scope' => $request->scope ?? '',
        ]);
    }

    /**
     * Getting an access token using a Password Grant flow.
     * Note: This grant is considered legacy and should only be used for highly trusted apps.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function passwordToken(Request $request): JsonResponse
    {
        return $this->proxy($request, 'password', [
            'client_id' => $request->client_id,
            'client_secret' => $request->client_secret,
            'username' => $request->username,
            'password' => $request->password,
            'scope' => $request->scope ?? '',
        ]);
    }

    /**
     * Redirect for Implicit Grant flow.
     * Note: This grant is considered legacy and not recommended.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function implicitRedirect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        
        $query = http_build_query([
            'response_type' => 'token',
            'client_id' => $request->client_id,
            'redirect_uri' => $request->redirect_uri,
            'state' => $state,
            'scope' => $request->scope ?? '',
        ]);

        return redirect(config('app.url') . '/oauth/authorize?' . $query);
    }

    /**
     * Proxies a request to Passport's token endpoint.
     *
     * @param Request $request
     * @param string $grantType
     * @param array $data
     * @return JsonResponse
     */
    protected function proxy(Request $request, string $grantType, array $data = []): JsonResponse
    {
        try {
            $data['grant_type'] = $grantType;
            
            // Merge internal data with any additional request parameters
            $request->request->add($data);

            $proxy = Request::create(
                'oauth/token',
                'POST'
            );

            $response = Route::dispatch($proxy);

            if (!$response->isSuccessful()) {
                $errorContent = json_decode($response->getContent(), true);
                return response()->json([
                    'error' => $errorContent['error'] ?? 'server_error',
                    'message' => $errorContent['message'] ?? 'An error occurred during the OAuth flow.',
                    'hint' => $errorContent['hint'] ?? null,
                ], $response->getStatusCode());
            }

            return response()->json(json_decode($response->getContent(), true));
        } catch (Exception $e) {
            return response()->json([
                'error' => 'proxy_error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

