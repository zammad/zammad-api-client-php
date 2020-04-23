<?php

/**
 * @package Zammad API Client
 * @author  Jens Pfeifer <jens.pfeifer@znuny.com>
 */

namespace ZammadAPIClient\Resource;

class User extends AbstractResource
{
    const URLS = [
        'get'    => 'users/{object_id}',
        'all'    => 'users',
        'create' => 'users',
        'update' => 'users/{object_id}',
        'delete' => 'users/{object_id}',
        'search' => 'users/search',
        'import' => 'users/import',
    ];

    /**
     * Imports users via CSV string.
     *
     * @param string $csv_string   CSV string to import.
     *
     * @return object              This object.
     */
    public function import($csv_string)
    {
        $this->clearError();

        $response = $this->getClient()->post(
            $this->getURL('import'),
            [
                'data' => $csv_string,
            ]
        );

        if ( $response->hasError() ) {
            $this->setError( $response->getError() );
        }
        else {
            $this->clearError();
            $this->setRemoteData( $response->getData() );
        }

        return $this;
    }
}
