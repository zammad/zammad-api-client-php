<?php

/**
 * @package Zammad API Client
 * @author  Jens Pfeifer <jens.pfeifer@znuny.com>
 */

namespace ZammadAPIClient\Resource;

class Organization extends AbstractResource
{
    const URLS = [
        'get'    => 'organizations/{object_id}',
        'all'    => 'organizations',
        'create' => 'organizations',
        'update' => 'organizations/{object_id}',
        'delete' => 'organizations/{object_id}',
        'search' => 'organizations/search',
        'import' => 'organizations/import',
    ];

    /**
     * Imports organizations via CSV string.
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
