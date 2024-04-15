<?php

return array(

    /*
    |--------------------------------------------------------------------------
    |   Your auth0 domain
    |--------------------------------------------------------------------------
    |   As set in the auth0 administration page
    |
    */

     'domain'        => 'pamelalim.auth0.com',
//     'domain'        => 'allgiftedllc.au.auth0.com',
    /*
    |--------------------------------------------------------------------------
    |   Your APP id
    |--------------------------------------------------------------------------
    |   As set in the auth0 administration page
    |
    */
     'client_id'     => 'x0AAlKqaQ8Zw6YF4kdMzTE08oAXjIhGJ',
//     'client_id'     => 'bs3jSKz2Ewrye8dD2qRVrD0Tra2tOqHC',

    /*
    |--------------------------------------------------------------------------
    |   Your APP secret
    |--------------------------------------------------------------------------
    |   As set in the auth0 administration page
    |
    */
     'client_secret' => '0ypbEEyAjhBYCjT0_DjO0c_fsTzcsU2zvObRLojrn2Ebq4r0M_dGl9e8vfe0Fay5',
//     'client_secret' => 'qb0mNnZVLMt1Q8mxVhxdTQDpx1FAYkeg3AsaztRI89IcqUgZoyOsNxQ3-jhnpNbb',
   /*
    |--------------------------------------------------------------------------
    |   The redirect URI
    |--------------------------------------------------------------------------
    |   Should be the same that the one configure in the route to handle the
    |   'Auth0\Login\Auth0Controller@callback'
    |
    */

     'redirect_uri'  => 'https://mathapi.pamelalim.me/api/protected',

    /*
    |--------------------------------------------------------------------------
    |   Persistence Configuration
    |--------------------------------------------------------------------------
    |   persist_user            (Boolean) Optional. Indicates if you want to persist the user info, default true
    |   persist_access_token    (Boolean) Optional. Indicates if you want to persist the access token, default false
    |   persist_id_token        (Boolean) Optional. Indicates if you want to persist the id token, default false
    |
    */

    // 'persist_user' => true,
    // 'persist_access_token' => false,
    // 'persist_id_token' => false,

    /*
    |--------------------------------------------------------------------------
    |   The authorized token issuers
    |--------------------------------------------------------------------------
    |   This is used to verify the decoded tokens when using RS256
    |
    */
     'authorized_issuers'  => [ 'https://pamelalim.auth0.com/' ],
    /*
    |--------------------------------------------------------------------------
    |   The authorized token issuers
    |--------------------------------------------------------------------------
    |   This is used to verify the decoded tokens when using RS256
    |
    */
     'api_identifier'  => 'https://mathapi.allgifted.com',
     'supported_algs' => [ 'RS256', 'HS256' ],
);
