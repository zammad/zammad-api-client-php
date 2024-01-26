<?php

namespace ZammadAPIClient\Resource;

use ZammadAPIClient\ResourceType;

class Links extends AbstractResource
{
    const URLS = [
        'get' => 'links',
        'add' => 'links/add',
        'delete' => 'links/remove'
    ];
    public function add(Ticket $source, Ticket $target, $type = 'normal')
    {
        $this->clearError();
        if(empty($source->getID()) || empty($target->getID())){
            $this->setError('Tickets not valid.');
            return [];
        }
        $data = [
            "link_type"                 =>  $type,
            "link_object_target"        =>  "Ticket",
            "link_object_target_value"  =>  $target->getValue('id'),
            "link_object_source"        =>  "Ticket",
            "link_object_source_number" =>  $source->getValue('number')
        ];
        $url = $this->getURL('add');
        $response = $this->getClient()->post(
            $url,
            $data,
            [
                'expand' => true
            ]
        );
        if($response->hasError()){
            $this->setError($response->getError());
            return $this;
        }
        $this->clearError();
        $this->setRemoteData($response->getData());
        $this->clearUnsavedValues();
        return $this;
    }

    public function get($object_id)
    {
        $this->clearError();
        if(empty($object_id)){
            $this->setError('LinkID Object not given');
            return [];
        }

        $url = $this->getURL('get');
        $response = $this->getClient()->post(
          $url,
            [
                "link_object" => "Ticket",
                "link_object_value" => $object_id
            ],
          [
              'expand' => true
          ]
        );
        if($response->hasError()){
            $this->setError($response->getError());
            return $this;
        }
        $this->clearError();
        $this->setRemoteData($response->getData());
        $this->clearUnsavedValues();
        return $this;
    }

    public function delete()
    {
        $this->clearError();
        $this->setError('not yet supported.'); //TODO: implement delete();
        return [];
    }

}
