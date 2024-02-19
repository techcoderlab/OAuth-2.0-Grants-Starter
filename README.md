# Implementing different grant flows Of OAuth 2.0

A grant is a method of acquiring an access token. Deciding which grants to implement depends on the type of client the end user will be using, and the experience you want for your users.

### Authorization Code Grant With PKCE

The authorization code grant should be very familiar if you’ve ever signed into a web app using your Facebook or Google account.

Flow

Part One

The client will redirect the user to the authorization server with the following parameters in the query string:

-   **response_type with the value code**
-   **client_id with the client identifier**
-   **redirect_uri with the client redirect URI. This parameter is optional, but if not send the user will be redirected to a pre-registered redirect URI.**
-   **scope a space delimited list of scopes**
-   **state with a CSRF token. This parameter is optional but highly recommended. You should store the value of the CSRF token in the user’s session to be validated when they return.**

All of these parameters will be validated by the authorization server.

The user will then be asked to login to the authorization server and approve the client.

If the user approves the client they will be redirected from the authorization server to the client’s redirect URI with the following parameters in the query string:

    • code with the authorization code
    • state with the state parameter sent in the original request. You should compare this value with the value stored in the user’s session to ensure the authorization code obtained is in response to requests made by this client rather than another client application.

Part Two

The client will now send a POST request to the authorization server with the following parameters:

-   **grant_type with the value of authorization_code**
-   **client_id with the client identifier**
-   **client_secret with the client secret**
-   **redirect_uri with the same redirect URI the user was redirect back to**
-   **code with the authorization code from the query string**
-   **Note that you need to decode the code query string first. You can do that with urldecode($code).**
-   **The authorization server will respond with a JSON object containing the following properties:**

    • token_type with the value Bearer
    • expires_in with an integer representing the TTL of the access token
    • access_token a JWT signed with the authorization server’s private key
    • refresh_token an encrypted payload that can be used to refresh the access token when it expires.

### Client Credentials Grant

This grant is suitable for machine-to-machine authentication, for example for use in a cron job which is performing maintenance tasks over an API. Another example would be a client making requests to an API that don’t require user’s permission.

Flow:

-   **grant_type with the value client_credentials**
-   **client_id with the client’s ID**
-   **client_secret with the client’s secret**
-   **scope with a space-delimited list of requested scope permissions.**
-   **The authorization server will respond with a JSON object containing the following properties**

    • token_type with the value Bearer
    • expires_in with an integer representing the TTL of the access token
    • access_token a JWT signed with the authorization server’s private key
