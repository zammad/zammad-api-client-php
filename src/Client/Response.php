<?php

/**
 * @package Zammad API Client
 * @author  Jens Pfeifer <jens.pfeifer@znuny.com>
 */

namespace ZammadAPIClient\Client;

class Response
{
    private $status_code;
    private $reason_phrase;
    private $body;
    private $headers;
    private $data  = [];
    private $error = null;

    public function __construct(
        $status_code,
        $reason_phrase,
        $body,
        array  $headers = []
    )
    {
        $this->status_code   = intval($status_code);
        $this->reason_phrase = $reason_phrase;
        $this->body          = $body;
        $this->headers       = $headers;

        // Store decoded JSON data, if present
        if (
            !empty( $this->headers['Content-Type'] )
            && mb_strpos( $this->headers['Content-Type'][0], 'application/json;' ) !== false
        ) {
            $this->data = json_decode( $this->body, true );

            if ( !empty( $this->data['error'] ) ) {
                $this->error = $this->data['error'];
            }
        }
    }

    public function getStatusCode()
    {
        return $this->status_code;
    }

    public function getReasonPhrase()
    {
        return $this->reason_phrase;
    }

    public function getStatusMessage()
    {
        return $this->status_code . ' - ' . $this->reason_phrase;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getError()
    {
        return $this->error;
    }

    public function hasError()
    {
        return !empty( $this->getError() );
    }
}

