<?php

/**
 * @package Zammad API Client
 * @author  Jens Pfeifer <jens.pfeifer@znuny.com>
 */

namespace ZammadAPIClient\Resource;

class TicketArticle extends AbstractResource
{
    const URLS = [
        'get'                    => 'ticket_articles/{object_id}',
        'get_for_ticket'         => 'ticket_articles/by_ticket/{ticket_id}',
        'get_attachment_content' => 'ticket_attachment/{ticket_id}/{ticket_article_id}/{object_id}',
        'create'                 => 'ticket_articles',
        'update'                 => 'ticket_articles/{object_id}',
        'delete'                 => 'ticket_articles/{object_id}',
    ];

    /**
     * Fetches TicketArticle objects for given ticket ID.
     *
     * @param integer $ticket_id      ID of ticket to fetch article data for.
     *
     * @return array                Array of TicketArticle objects.
     */
    public function getForTicket($ticket_id)
    {
        if ( !empty( $this->getValues() ) ) {
            throw new \RuntimeException('Object already contains values, getForTicket() not possible, use a new object');
        }

        $this->clearError();

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

        // Return array of TicketArticle objects.
        $ticket_articles = [];
        foreach ( $response->getData() as $ticket_article_data ) {
            $ticket_article = $this->getClient()->resource( get_class($this) );
            $ticket_article->setRemoteData($ticket_article_data);
            $ticket_articles[] = $ticket_article;
        }

        return $ticket_articles;
    }

    /**
     * Fetches the actual content of the file of a ticket article attachment.
     * Note that the resource object must contain data of saved article (resource object must contain ID of article).
     *
     * @param integer $attachment_id      ID of attachment to fetch content for.
     *
     * @return string                     Attachment content or false on error.
     */
    public function getAttachmentContent($attachment_id)
    {
        $attachment_id = intval($attachment_id);
        if (!$attachment_id) {
            throw new \Exception('Attachment ID is invalid');
        }

        if ( empty( $this->getID() ) ) {
            throw new \Exception('Object data does not contain an ID, getAttachmentContent() not possible');
        }

        $this->clearError();

        $attachments = $this->getValue('attachments');
        if ( !is_array($attachments) || !count($attachments) ) {
            return false;
        }

        // Check if given attachment ID exists for article.
        $attachment_key = array_search( $attachment_id, array_column( $attachments, 'id' ) );
        if ( $attachment_key === false ) {
            return false;
        }
        $attachment = $attachments[$attachment_key];

        // Fetch content.
        $url = $this->getURL(
            'get_attachment_content',
            [
                'ticket_id'         => $this->getValue('ticket_id'),
                'ticket_article_id' => $this->getID(),
                'object_id'         => $attachment['id'],
            ]
        );

        $response = $this->getClient()->get($url);
        if ( $response->hasError() ) {
            $this->setError( $response->getError() );
            return false;
        }

        $content = $response->getBody();
        return $content;
    }
}
