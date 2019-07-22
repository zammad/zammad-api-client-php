<?php

/**
 * @package Zammad API Client
 * @author  Jens Pfeifer <jens.pfeifer@znuny.com>
 */

namespace ZammadAPIClient\Resource;

class TextModule extends AbstractResource
{
    const URLS = [
        'get'    => 'text_modules/{object_id}',
        'all'    => 'text_modules',
        'create' => 'text_modules',
        'update' => 'text_modules/{object_id}',
        'delete' => 'text_modules/{object_id}',
        'import' => 'text_modules/import',
    ];

    /**
     * Imports text modules via CSV string.
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
