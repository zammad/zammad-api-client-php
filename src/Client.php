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
     *                                          ];
     *
     * @return Object                           Client object
     */
    public function __construct( array $options = [] )
    {
        $this->http_client = new HTTPClient($options);
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

        $url_parameter_string = '';
        foreach ( $url_parameters as $url_parameter => $value ) {
            $url_parameter_string .= mb_strlen($url_parameter_string) ? ';' : '?';
            $url_parameter_string .= $url_parameter . '=' . urlencode($value);
        }

        $url .= $url_parameter_string;

        // Set JSON headers
        $options['headers']['Accept']       = 'application/json';
        $options['headers']['Content-Type'] = 'application/json; charset=utf-8';

        $http_client_response = $this->http_client->request( $method, $url, $options );

        // Turn HTTP client's response into our own.
        $response = new Response(
            $http_client_response->getStatusCode(),
            $http_client_response->getReasonPhrase(),
            $http_client_response->getBody(),
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
    public function resource( $resource_type )
    {
        $resource_object = new $resource_type($this);
        return $resource_object;
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
