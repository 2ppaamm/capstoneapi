<?php
use Auth0\Laravel\Configuration;
use Auth0\SDK\Configuration\SdkConfiguration;

return [
    'registerGuards' => true,
    'registerMiddleware' => true,
    'registerAuthenticationRoutes' => true,
    'configurationPath' => null,

    'guards' => [
        'default' => [
            'strategy' => env('AUTH0_STRATEGY', 'none'), // Default fallback strategy
            'domain' => env('AUTH0_DOMAIN'),
            'custom_domain' => env('AUTH0_CUSTOM_DOMAIN'),
            'clientId' => env('AUTH0_CLIENT_ID'),
            'client_secret' => env('AUTH0_CLIENT_SECRET'),
            'audience' => [env('AUTH0_AUDIENCE')],
            'organization' => env('AUTH0_ORGANIZATION'),
            'use_pkce' => env('AUTH0_USE_PKCE', false),
            'scope' => [env('AUTH0_SCOPE', 'openid profile email')],
            'response_mode' => env('AUTH0_RESPONSE_MODE', 'query'),
            'response_type' => env('AUTH0_RESPONSE_TYPE', 'code'),
            'token_algorithm' => env('AUTH0_TOKEN_ALGORITHM', 'RS256'),
            'token_jwks_uri' => env('AUTH0_TOKEN_JWKS_URI'),
            'token_max_age' => env('AUTH0_TOKEN_MAX_AGE', 3600),
            'token_leeway' => env('AUTH0_TOKEN_LEEWAY', 60),
            'token_cache' => env('AUTH0_TOKEN_CACHE', true),
            'token_cache_ttl' => env('AUTH0_TOKEN_CACHE_TTL', 1440),
            'http_max_retries' => env('AUTH0_HTTP_MAX_RETRIES', 2),
            'http_telemetry' => env('AUTH0_HTTP_TELEMETRY', true),
            'management_token' => env('AUTH0_MANAGEMENT_TOKEN'),
            'management_token_cache' => env('AUTH0_MANAGEMENT_TOKEN_CACHE', true),
            'client_assertion_signing_key' => env('AUTH0_CLIENT_ASSERTION_SIGNING_KEY'),
            'client_assertion_signing_algorithm' => env('AUTH0_CLIENT_ASSERTION_SIGNING_ALGORITHM', 'RS256'),
            'pushed_authorization_request' => env('AUTH0_PUSHED_AUTHORIZATION_REQUEST', false),
            'backchannel_logout_cache' => env('AUTH0_BACKCHANNEL_LOGOUT_CACHE', false),
            'backchannel_logout_expires' => env('AUTH0_BACKCHANNEL_LOGOUT_EXPIRES', 3600),
            'cookieSecret' => env('APP_KEY'),
        ],

        'api' => [
            'strategy' => 'webapp',
        ],

        'web' => [
            'strategy' => 'regular',
            'cookieSecret' => env('APP_KEY'),
            'redirect_uri' => env('APP_URL') . '/callback',
            'session_storage' => env('AUTH0_SESSION_STORAGE', 'file'),
            'session_storage_id' => env('AUTH0_SESSION_STORAGE_ID'),
            'transient_storage' => env('AUTH0_TRANSIENT_STORAGE', 'file'),
            'transient_storage_id' => env('AUTH0_TRANSIENT_STORAGE_ID'),
        ],
    ],

    'routes' => [
        'index' => env('AUTH0_ROUTE_INDEX', '/'),
        'callback' => env('AUTH0_ROUTE_CALLBACK', '/callback'),
        'login' => env('AUTH0_ROUTE_LOGIN', '/login'),
        'after_login' => env('AUTH0_ROUTE_AFTER_LOGIN', '/'),
        'logout' => env('AUTH0_ROUTE_LOGOUT', '/logout'),
        'after_logout' => env('AUTH0_ROUTE_AFTER_LOGOUT', '/'),
    ],
];