<?php

/**
 * @package Zammad API Client
 * @author  Jens Pfeifer <jens.pfeifer@znuny.com>
 */

namespace ZammadAPIClient;

use ZammadAPIClient\Client\Response;

class Client
{
    const API_VERSION = 'v1';

    private $http_client;
    private $last_response;
    private $options;
    private $on_behalf_of_user;

    /**
     * Creates a Client object.
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
     *                                              timeout => 10,
     *
     *                                              // Optional: Enable debug output
     *                                              debug => true,
     *                                          ];
     *
     * @param HTTPClientInterface $client       Optional, pass in custom HTTP client.
     *
     * @return Object                           Client object
     */
    public function __construct( array $options = [], HTTPClientInterface $client = null)
    {
        $this->options     = $options;
        $this->http_client = $client ?? new HTTPClient($options);
    }

    /**
     * Executes a request.
     *
     * @param String $method         GET, PUT, POST, DELETE
     * @param String $url            E. g. tickets/1
     * @param Array  $url_parameters E. g. [ 'expand' => true, ]
     *
     * @return Response object
     */
    private function request ( $method, $url, array $url_parameters = [], array $options = [] )
    {
        $method = mb_strtoupper($method);

        if (!empty($url_parameters)) {
          $options['query'] = $url_parameters;
        }

        // Set JSON headers
        $options['headers']['Accept']       = 'application/json';
        $options['headers']['Content-Type'] = 'application/json; charset=utf-8';

        // Set "on behalf of user" header
        if ( !empty($this->on_behalf_of_user) ) {
            $options['headers']['X-On-Behalf-Of'] = $this->on_behalf_of_user;
        }

        $http_client_response = $this->http_client->request( $method, $url, $options );
        if ( !is_object($http_client_response) ) {
            throw new \RuntimeException('Unable to create HTTP client request.');
        }

        $response_body = $http_client_response->getBody()
        var_dump('body steam check'); // Debug
        var_dump($response_body instanceof StreamInterface); // Debug

        if ($response_body instanceof StreamInterface) {
            $response_body = $response_body->getContents();
        }

        // Turn HTTP client's response into our own.
        $response = new Response(
            $http_client_response->getStatusCode(),
            $http_client_response->getReasonPhrase(),
            $response_body,
            $http_client_response->getHeaders()
        );

        $this->setLastResponse($response);

        return $response;
    }

    /**
     * Executes GET request.
     *
     * @param String $url            E. g. tickets/1
     * @param Array  $url_parameters E. g. [ 'expand' => true, ]
     *
     * @return Response object
     */
    public function get( $url, array $url_parameters = [] )
    {
        $response = $this->request(
            'GET',
            $url,
            $url_parameters
        );

        return $response;
    }

    /**
     * Executes POST request.
     *
     * @param String $url            E. g. tickets/1
     * @param Array $data            Array with data to send as JSON.
     * @param Array  $url_parameters E. g. [ 'expand' => true, ]
     *
     * @return Response object
     */
    public function post( $url, array $data = [], array $url_parameters = [] )
    {
        $response = $this->request(
            'POST',
            $url,
            $url_parameters,
            [ 'json' => $data, ]
        );

        return $response;
    }

    /**
     * Executes PUT request.
     *
     * @param String $url            E. g. tickets/1
     * @param Array $data            Array with data to send as JSON.
     * @param Array  $url_parameters E. g. [ 'expand' => true, ]
     *
     * @return Response object
     */
    public function put( $url, array $data = [], array $url_parameters = [] )
    {
        $response = $this->request(
            'PUT',
            $url,
            $url_parameters,
            [ 'json' => $data, ]
        );

        return $response;
    }

    /**
     * Executes DELETE request.
     *
     * @param String $url            E. g. tickets/1
     * @param Array  $url_parameters E. g. [ 'expand' => true, ]
     *
     * @return Response object
     */
    public function delete( $url, array $url_parameters = [] )
    {
        $response = $this->request(
            'DELETE',
            $url,
            $url_parameters
        );

        return $response;
    }

    /**
     * Creates a resource object.
     *
     * @param String $resource_type         ResourceType::TICKET
     *
     * @return Object                       Resource object
     */
    public function resource($resource_type)
    {
        $resource_object = new $resource_type($this);
        return $resource_object;
    }

    /**
     * Sets user on behalf of which API calls will be executed.
     *
     * @param String $user         User ID, login or email address
     */
    public function setOnBehalfOfUser($user)
    {
        $this->on_behalf_of_user = $user;
    }

    /**
     * Unsets user on behalf of which API calls will be executed.
     * API calls will then be called again by the user who is being used
     * for authentication.
     */
    public function unsetOnBehalfOfUser()
    {
        $this->on_behalf_of_user = null;
    }

    /**
     * Stores Response object as last Response object.
     *
     * @param Object $response              Response object to store.
     */
    private function setLastResponse( Response $response )
    {
        $this->last_response = $response;
    }

    /**
     * Returns last Response object.
     *
     * @return objects                      Last Response object.
     */
    public function getLastResponse()
    {
        return $this->last_response;
    }
}
