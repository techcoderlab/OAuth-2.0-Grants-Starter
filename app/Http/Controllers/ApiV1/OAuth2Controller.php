<?php

namespace App\Http\Controllers\ApiV1;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;

class OAuth2Controller extends Controller
{
    /**
     * Redirect for PKCE Flow with required parameters.
     *
     * @param Request $request The incoming HTTP request.
     * @return RedirectResponse Redirects to authorization endpoint.
     *
     */
    public function authCodeRedirect(Request $request)
    {
        // Generate random state and code verifier.
        $state = Str::random(40);
        $code_verifier = Str::random(128);

        // Store state and code verifier in session.
        try {
            $request->session()->put('state', $state);
            $request->session()->put('code_verifier', $code_verifier);
        } catch (Exception $e) {
            // Log error and handle exception here.
            return response()
                ->json(['error' => $e->getMessage()], 500);
        }

        // Generate code challenge using SHA256 and base64 encoding.
        $codeChallenge = strtr(rtrim(
            base64_encode(hash('sha256', $code_verifier, true)),
            '='
        ), '+/', '-_');

        // Build query string with authorization parameters.
        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $request->client_id,
            'redirect_uri' => $request->redirect_uri,
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'scope' => '',
        ]);

        // Redirect to authorization endpoint with query string.
        return redirect(config('app.url') . '/oauth/authorize?' . $query);
    }
    /**
     * Getting an access token in exchange of code using
     * Authorization Code Grant flow.
     *
     * @param Request $request The incoming HTTP request.
     * @return JsonResponse Returns access token or error response.
     *
     */
    public function authCodeToken(Request $request)
    {

        $state = $request->session()->pull('state');
        $codeVerifier = $request->session()->pull('code_verifier');

        // Validate state parameter.
        if (strlen($state) <= 0 || $state !== $request->state) {
            return response()->json(['error' =>
            'Invalid state parameter'], 405);
        }

        // // Find client details by client ID.
        // $client = DB::table('oauth_clients')
        //     ->where('id', $request->client_id)->first();

        // // Check if client exists.
        // if (is_null($client))
        //     return response()
        //         ->json(['error' => 'Invalid client_id parameter'], 405);

        /* Proxy request to token endpoint with client credentials
         and exchange code for access token. */
        return $this->proxy($request, 'authorization_code', [
            'client_id' => $request->client_id,
            'client_secret' => $request->client_secret,
            'redirect_uri' => $request->redirect_uri,
            'code' => $request->code,
            'code_verifier' => $codeVerifier,
            'state' => $request->state,
            'scope' => '',
        ]);
    }

    /**
     * Getting an access token using a Refresh Token Grant flow.
     *
     * @param Request $request The incoming HTTP request.
     * @return JsonResponse Returns new access token or error response.
     *
     */
    public function refreshToken(Request $request)
    {
        /* Proxy request to token endpoint with client credentials
         and exchange refresh token for access token. */
        return $this->proxy($request, 'refresh_token', [
            'refresh_token' => $request->refresh_token,
            'client_id' => $request->client_id,
            'client_secret' => $request->client_secret,
            'scope' => '',
        ]);
    }


    /**
     * Getting an access token using a Client Credential Grant flow.
     *
     * @param Request $request The incoming HTTP request.
     * @return JsonResponse Returns new access token or error response.
     *
     */
    public function clientCredsToken(Request $request)
    {
        /* Proxy request to token endpoint with client credentials
         and exchange refresh token for access token. */
        return $this->proxy($request, 'client_credentials', [
            'client_id' => $request->client_id,
            'client_secret' => $request->client_secret,
            'scope' => '',
        ]);
    }

    /**
     * Proxies a request to the token endpoint with necessary parameters.
     *
     * @param Request $request The incoming HTTP request.
     * @param string $grantType The grant type used for the request
     (e.g., "client_credentials", "authorization_code", "refresh_token",
      "password").
     * @param array $datas Additional data to be added to the request body.
     * @return JsonResponse Returns the response from the token endpoint.
     *
     * @throws Exception If request fails or response is unsuccessful.
     */
    protected function proxy(Request $request, $grantType, array $datas = [])
    {

        $datas['grant_type'] = $grantType;
        $request->request->add($datas);

        $proxy = Request::create(
            'oauth/token',
            'POST'
        );

        $response =  Route::dispatch($proxy);

        if (!$response->isSuccessful()) {
            return response()
                ->json(['error' =>
                json_decode($response->getContent(), TRUE)], 500);
        }

        $data = json_decode($response->getContent(), TRUE);

        return response()->json($data);
    }
}
