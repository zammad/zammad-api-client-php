<?php

return [

    /** Zammad API Base URL
     * Full URL to your Zammad instance including the API prefix, e.g.:
     * https://zammad.example.com/api/v1
    */
    'url' => env('ZAMMAD_URL', 'http://127.0.0.1:8098'),

    /** API Authentication Token
     *  Zammad personal access token. Create one in your Zammad profile under
     * "Token Access". See https://docs.zammad.org/en/latest/api/intro.html
    */
    'token' => env('ZAMMAD_TOKEN', ''),

];
