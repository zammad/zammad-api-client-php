<?php

/**
 * @package Zammad API Client
 * @author  Fran Rey <franreycastedo@gmail.com>
 */

namespace ZammadAPIClient\Resource;

class Link extends AbstractResource
{
    const URLS = [
        'get'    => 'links',
        'add'    => 'links/add',
        'remove' => 'links/remove'
    ];

    const LINKTYPES = [
        'normal',
        'parent',
        'child'
    ];

    /**
     * Fetches links of an object.
     *
     * @param int    $object_id     ID of the object to fetch links for.
     * @param string $object_type   Type of object to fetch links for (e. g. 'Ticket').
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
                'link_object' => $object_type,
                'link_object_value'   => $object_id,
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
     * Adds a link between two tickets.
     *
     * @param Ticket $source        Source ticket object.
     * @param Ticket $target        Target ticket object.
     * @param string $type          Link type (default: 'normal').
     *
     * @return object               This object.
     */
    public function add(Ticket $source, Ticket $target, $type = 'normal')
    {
        $this->clearError();

        if ( empty($source->getID()) || empty($target->getID()) ) {
            $this->setError('Tickets not valid.');
            return $this;
        }
        if ( !in_array($type, self::LINKTYPES, true) ) {
            $this->setError('Linktype is not supported.');
            return $this;
        }

        $response = $this->getClient()->post(
            $this->getURL('add'),
            [
                'link_type'                 => $type,
                'link_object_target'        => 'Ticket',
                'link_object_target_value'  => $target->getID(),
                'link_object_source'        => 'Ticket',
                'link_object_source_number' => $source->getValue('number')
            ]
        );

        if ( $response->hasError() ) {
            $this->setError( $response->getError() );
        }

        return $this;
    }

    /**
     * Removes a link between two tickets.
     *
     * @param Ticket $source        Source ticket object.
     * @param Ticket $target       Target ticket object.
     * @param string $type         Link type (default: 'normal').
     *
     * @return object               This object.
     */
    public function remove(Ticket $source, Ticket $target, $type = 'normal')
    {
        $this->clearError();

        if ( empty($source->getID()) || empty($target->getID()) ) {
            $this->setError('Tickets not valid.');
            return $this;
        }
        if ( !in_array($type, self::LINKTYPES, true) ) {
            $this->setError('Linktype is not supported.');
            return $this;
        }

        $response = $this->getClient()->delete(
            $this->getURL('remove'),
            [
                'link_type'                => $type,
                'link_object_source'       => 'Ticket',
                'link_object_source_value' => $source->getID(),
                'link_object_target'       => 'Ticket',
                'link_object_target_value' => $target->getID()
            ]
        );

        if ( $response->hasError() ) {
            $this->setError( $response->getError() );
            return $this;
        }

        $this->clearError();
        $this->clearRemoteData();
        $this->clearUnsavedValues();

        return $this;
    }
}
