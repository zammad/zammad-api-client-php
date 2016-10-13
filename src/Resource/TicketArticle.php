<?php

/**
 * @package Zammad API Client
 * @author  Jens Pfeifer <jens.pfeifer@znuny.com>
 */

namespace ZammadAPIClient\Resource;

class TicketArticle extends AbstractResource
{
    const URLS = [
        'get'            => 'ticket_articles/{object_id}',
        'get_for_ticket' => 'ticket_articles/by_ticket/{ticket_id}',
        'create'         => 'ticket_articles',
        'update'         => 'ticket_articles/{object_id}',
        'delete'         => 'ticket_articles/{object_id}',
    ];

    /**
     * Fetches TicketArticle objects for given ticket ID.
     *
     * @param integer $ticket_id      ID of ticket to fetch article data for.
     *
     * @return array                Array of TicketArticle objects.
     */
    public function getForTicket( $ticket_id )
    {
        if ( !empty( $this->getValues() ) ) {
            throw new \RuntimeException('Object already contains values, getForTicket() not possible, use a new object');
        }

       $ticket_id = intval($ticket_id);
        if ( empty($ticket_id) ) {
            throw new \RuntimeException('Missing ticket ID');
        }

        $url = $this->getURL(
            'get_for_ticket',
            [
                'ticket_id' => $ticket_id,
            ]
        );
        $response = $this->getClient()->get(
            $url,
            [
                'expand' => true,
            ]
        );

        if ( $response->hasError() ) {
            $this->setError( $response->getError() );
            return $this;
        }

        $this->clearError();

        // Return array of TicketArticle objects.
        $ticket_articles = [];
        foreach ( $response->getData() as $ticket_article_data ) {
            $ticket_article = $this->getClient()->resource( get_class($this) );
            $ticket_article->setRemoteData($ticket_article_data);
            $ticket_articles[] = $ticket_article;
        }

        return $ticket_articles;
    }
}
