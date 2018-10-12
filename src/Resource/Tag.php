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

    /**
     * Fetches tags of an object.
     *
     * @param integer $object_id    ID of the object to fetch tags for.
     * @param string $object_type   Type of object to fetch tags for (e. g. 'Ticket').
     *
     * @return object               This object.
     */
    public function get($object_id, $object_type = 'Ticket')
    {
        $this->clearError();

        $object_id = intval($object_id);
        if ( empty($object_id) ) {
            throw new \RuntimeException('Missing object ID');
        }

        $response = $this->getClient()->get(
            $this->getURL('get'),
            [
                'object' => $object_type,
                'o_id'   => $object_id,
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
     * Adds a tag to an object.
     *
     * @param integer $object_id    ID of the object to fetch tags for.
     * @param string $tag           Tag to add.
     * @param string $object_type   Type of object to fetch tags for (e. g. 'Ticket').
     *
     * @return object               This object.
     */
    public function add($object_id, $tag, $object_type = 'Ticket')
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
                'object' => $object_type,
                'o_id'   => $object_id,
                'item'   => $tag,
            ]
        );

        if ( $response->hasError() ) {
            $this->setError( $response->getError() );
        }

        return $this;
    }

    /**
     * Fetches tags for given search term.
     * Pagination available.
     *
     * @param string  $search_term          Search term.
     * @param integer $page                 Page of tags, optional, if given, $objects_per_page must also be given
     * @param integer $objects_per_page     Number of tags per page, optional, if given, $page must also be given
     *
     * @return mixed                        Returns array of ZammadAPIClient\Resource\... objects
     *                                          or this object on failure.
     */
    public function search($search_term, $page = null, $objects_per_page = null)
    {
        $this->clearError();

        if ( empty($search_term) ) {
            throw new \RuntimeException('Missing search term');
        }

        $response = $this->getClient()->get(
            $this->getURL('search'),
            [
                'term' => $search_term,
            ]
        );

        if ( $response->hasError() ) {
            $this->setError( $response->getError() );
        }

        $this->clearError();

        // Return array of resource objects if no $object_id was given.
        // Note: the resource object (this object) used to execute get() will be left empty in this case.
        $objects = [];
        foreach ( $response->getData() as $object_data ) {
            $object = $this->getClient()->resource( get_class($this) );
            $object->setRemoteData($object_data);
            $objects[] = $object;
        }

        return $objects;
    }

    /**
     * Removes a tag from an object.
     *
     * @param integer $object_id    ID of the object to fetch tags for.
     * @param string $tag           Tag to remove.
     * @param string $object_type   Type of object to fetch tags for (e. g. 'Ticket').
     *
     * @return object               This object.
     */
    public function remove($object_id, $tag, $object_type = 'Ticket')
    {
        $this->clearError();

        if ( empty($object_id) ) {
            throw new \RuntimeException('Missing object ID');
        }

        if ( empty($tag) ) {
            throw new \RuntimeException('Missing tag');
        }

        // Delete object in Zammad.
        $response = $this->getClient()->get(
            $this->getURL('remove'),
            [
                'object' => $object_type,
                'o_id'   => $object_id,
                'item'   => $tag,
            ]
        );

        if ( $response->hasError() ) {
            $this->setError( $response->getError() );
            return $this;
        }

        // Clear data of this (local) object.
        $this->clearError();
        $this->clearRemoteData();
        $this->clearUnsavedValues();

        return $this;
    }
}
