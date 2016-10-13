<?php

/**
 * @package Zammad API Client
 * @author  Jens Pfeifer <jens.pfeifer@znuny.com>
 */

namespace ZammadAPIClient\Resource;

use ZammadAPIClient\ResourceType;

class Ticket extends AbstractResource
{
    const URLS = [
        'get'    => 'tickets/{object_id}',
        'all'    => 'tickets',
        'create' => 'tickets',
        'update' => 'tickets/{object_id}',
        'delete' => 'tickets/{object_id}',
        'search' => 'tickets/search',
    ];

    /**
     * Fetches TicketArticle objects of this Ticket object.
     *
     * @return Array of TicketArticle objects   Returns array of ZammadAPIClient\Resource\TicketArticle objects or an empty array.
     */
    public function getTicketArticles()
    {
        $this->clearError();

        if ( empty( $this->getID() ) ) {
            return [];
        }

        $ticket_articles = $this->getClient()->resource( ResourceType::TICKET_ARTICLE )->getForTicket( $this->getID() );
        if ( !is_array($ticket_articles) ) {
            $this->setError( $ticket_articles->getError() );
            return [];
        }

        return $ticket_articles;
    }
}
