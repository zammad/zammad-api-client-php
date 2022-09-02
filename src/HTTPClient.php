<?php

/**
 * @package Zammad API Client
 * @author  Jens Pfeifer <jens.pfeifer@znuny.com>
 */

namespace ZammadAPIClient;

use Psr\Http\Message\ResponseInterface;

class HTTPClient extends \GuzzleHttp\Client
{
    private $base_url;
    private $authentication_options;

    /**
     * Creates an HTTPClient object.
     *
     * @param Array $options                    Options to use for client:
     *                                          $options = [
     *                                              // URL of Zammad
     *                                              'url' => 'https://my.zammad.com:3000',
     *
     *                                              // authentication via username and password
     *                                              'username' => 'my-username',
     *                                              'password' => 'my-password',
     *                                              // OR: authentication via HTTP token
     *                                              'http_token' => 'my-token',
     *                                              // OR: authentication via OAuth2 token
     *                                              'oauth2_token' => 'my-token',
     *
     *                                              // Optional: timeout (in seconds) for requests, defaults to 5
     *                                              // 0: no timeout
     *                                              'timeout' => 10,
     *
     *                                              // Optional: Enable debug output
     *                                              'debug' => true,
     *
     *                                              // Optional: Enable SSL verification (defaults to true).
     *                                              // You can also give a path to a CA bundle file.
     *                                              'verify' => true,
     *                                          ];
     *
     * @return Object                           HTTPClient object
     */
    public function __construct( array $options = [] )
    {
        //
        // Check options
        //

        // URL
        if ( empty( $options['url'] ) ) {
            throw new \RuntimeException('Missing option "url"');
        }
        $url_components = parse_url( $options['url'] );
        if (
            $url_components === false
            || empty( $url_components['scheme'] )
            || empty( $url_components['host'] )
        ) {
            throw new \RuntimeException('Invalid URL');
        }

        // Authentication
        $number_of_authentication_types_given = 0;
        if ( !empty( $options['username'] ) || !empty( $options['password'] ) ) {
            if ( empty( $options['username'] ) ) {
                throw new \RuntimeException('Missing option "username"');
            }
            else if ( empty( $options['password'] ) ) {
                throw new \RuntimeException('Missing option "password"');
            }

            $number_of_authentication_types_given++;

            $this->authentication_options = [
                'username' => $options['username'],
                'password' => $options['password'],
            ];
        }

        if ( !empty( $options['http_token'] ) ) {
            $number_of_authentication_types_given++;

            $this->authentication_options = [
                'http_token' => $options['http_token'],
            ];
        }

        if ( !empty( $options['oauth2_token'] ) ) {
            $number_of_authentication_types_given++;

            $this->authentication_options = [
                'oauth2_token' => $options['oauth2_token'],
            ];
        }

        if ( $number_of_authentication_types_given != 1 ) {
            throw new \RuntimeException('Missing authentication options: Either give username/password, http_token or oauth2_token');
        }

        // Assemble base URL
        $this->base_url = $options['url'] . '/api/' . Client::API_VERSION . '/';

        // Optional: override timeout
        $timeout = 5;
        if ( array_key_exists( 'timeout', $options ) ) {
            $timeout = intval($options['timeout']);
            if ($timeout < 0) {
                $timeout = 0;
            }
        }

        // Debug flag
        $debug = false;
        if ( array_key_exists( 'debug', $options ) ) {
            $debug = $options['debug'] ? true : false;
        }

        // Verify ssl
        $verifySsl = true;
        if (
            array_key_exists('verify', $options)
            && (
                is_bool($options['verify'])
                || (
                    is_string($options['verify'])
                    && file_exists($options['verify'])
                )
            )
        ) {
            $verifySsl = $options['verify'];
        }

        // Execute constructor of base class
        parent::__construct([
            'base_uri'         => $this->base_url,
            'timeout'          => $timeout,
            'connect_timeout'  => $timeout,
            'debug'            => $debug,
            'verify'           => $verifySsl,
        ]);
    }

    /**
     * Overrides base class request method to automatically add authentication options to request.
     */
    public function request(string $method, $uri = '', array $options = [] ): ResponseInterface
    {
        //
        // Add authentication options
        //

        // Username and password
        if (
            !empty( $this->authentication_options['username'] )
            && !empty( $this->authentication_options['password'] )
        ) {
            $options['auth'] = [
                $this->authentication_options['username'],
                $this->authentication_options['password'],
            ];
        }
        // HTTP token
        else if ( !empty( $this->authentication_options['http_token'] ) ) {
            $options['headers']['Authorization']
                = 'Token token=' . $this->authentication_options['http_token'];
        }
        // OAuth2 token
        else if ( !empty( $this->authentication_options['oauth2_token'] ) ) {
            $options['headers']['Authorization']
                = 'Bearer ' . $this->authentication_options['oauth2_token'];
        }
        else {
            throw new \RuntimeException('No authentication options available');
        }

        try {
            $response = parent::request( $method, $uri, $options );
        }
        catch ( \GuzzleHttp\Exception\TransferException $e ) {
            if (method_exists($e, 'hasResponse') && $e->hasResponse()) {
                return $e->getResponse();
            }
            throw $e;
        }

        return $response;
    }
}
