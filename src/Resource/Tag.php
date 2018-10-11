<?php

/**
 * @package Zammad API Client
 * @author  Alexander Reichardt <alexander.reichardt@icloud.com>
 */

namespace ZammadAPIClient\Resource;

class Tag extends AbstractResource
{
    const URLS = [
        'get'    => 'tags',
        'search' => 'tag_search?term={query}',
        'add'    => 'tags/add',
        'remove' => 'tags/remove'
    ];

    public function get($object_id, $object = 'Ticket')
    {
        $this->clearError();

        $object_id = intval($object_id);
        if ( empty($object_id) ) {
            throw new \RuntimeException('Missing object ID');
        }

        $response = $this->getClient()->get(
            $this->getURL('get'),
            [
                'object' => $object,
                'o_id' => $object_id
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

    /**
     * Adds a Tag to an object
     *
     * @param integer $object_id
     * @param string $tag
     * @param string $object
     * @return $this This object
     */
    public function add($object_id, $tag, $object = 'Ticket')
    {
        $this->clearError();

        $object_id = intval($object_id);
        if ( empty($object_id) ) {
            throw new \RuntimeException('Missing object ID');
        }

        if ( empty($tag) ) {
            throw new \RuntimeException('Missing tag');
        }

        $response = $this->getClient()->get(
            $this->getURL('add'),
            [
                'object' => $object,
                'o_id' => $object_id,
                'item' => $tag
            ]
        );

        if ( $response->hasError() ) {
            $this->setError( $response->getError() );
        }

        return $this;
    }

    public function search($term, $page = null, $objects_per_page = null)
    {
        $this->clearError();

        if ( empty($term) ) {
            throw new \RuntimeException('Missing search term');
        }

        $response = $this->getClient()->get(
            $this->getURL('search'),
            [
                'term' => $term
            ]
        );

        if ( $response->hasError() ) {
            $this->setError( $response->getError() );
        }

        $this->clearError();

        // Return array of resource objects if no $object_id was given.
        // Note: the resource object (this object) used to execute get() will be left empty in this case.
        $objects       = [];
        foreach ( $response->getData() as $object_data ) {
            $object = $this->getClient()->resource( get_class($this) );
            $object->setRemoteData($object_data);
            $objects[] = $object;
        }

        return $objects;
    }

    /**
     * Removes a Tag from a object
     *
     * @param integer $object_id
     * @param string $tag
     * @param string $object
     * @return $this This object
     */
    public function remove($object_id, $tag, $object = 'Ticket')
    {
        $this->clearError();

        // Delete object in Zammad.
        if ( !empty($object_id) ) {
            $response = $this->getClient()->get(
                $this->getURL('remove'),
                [
                    'object' => $object,
                    'o_id' => $object_id,
                    'item' => $tag
                ]
            );

            if ( $response->hasError() ) {
                $this->setError( $response->getError() );
                return $this;
            }
        }

        if ( empty($object_id) ) {
            throw new \RuntimeException('Missing object ID');
        }

        if ( empty($tag) ) {
            throw new \RuntimeException('Missing tag');
        }

        $response = $this->getClient()->get(
            $this->getURL('remove'),
            [
                'object' => $object,
                'o_id' => $object_id,
                'item' => $tag
            ]
        );

        if ( $response->hasError() ) {
            $this->setError( $response->getError() );
        }

        // Clear data of this (local) object.
        $this->clearError();
        $this->clearRemoteData();
        $this->clearUnsavedValues();

        return $this;
    }
}