<?php

/**
 * @package Zammad API Client
 * @author  Jens Pfeifer <jens.pfeifer@znuny.com>
 */

namespace ZammadAPIClient\Resource;

use ZammadAPIClient\Exception\AlreadyFetchedObjectException;

abstract class AbstractResource
{
    // API client used for requests to Zammad.
    private $client;

    // Data returned by response from Zammad.
    private $remote_data = [];

    // Values that were set by the API client to be saved in Zammad later by a call to save().
    private $values = [];

    // Error message of last response from Zammad.
    private $error = null;

    /**
     * @param object $client        ZammadAPIClient object
     */
    public function __construct( \ZammadAPIClient\Client $client )
    {
        $this->client = $client;
    }

    /**
     * Returns the ZammadAPIClient object.
     *
     * @return object    ZammadAPIClient object
     */
    protected function getClient()
    {
        return $this->client;
    }

    /**
     * Sets remote data that was returned from Zammad.
     *
     * @param array $remote_data    Data to set.
     *
     * @return object
     */
    protected function setRemoteData( array $remote_data = [] )
    {
        $this->remote_data = $remote_data;
        return $this;
    }

    /**
     * Clears remote data that was returned by Zammad.
     *
     * @return object   This object
     */
    protected function clearRemoteData()
    {
        $this->remote_data = [];
        return $this;
    }

    /**
     * Fetches remote data that was returned by Zammad.
     *
     * @return array Array with the object's data. Might be empty.
     */
    protected function getRemoteData()
    {
        return $this->remote_data;
    }

    /**
     * Sets local values that will be saved later in Zammad by a call to save().
     *
     * Already set values will be merged with the ones given.
     * If you want to completely discard the existing values,
     * call clearValues() before setValues().
     *
     * @param array $values     Values to set (key-value-pairs).
     *
     * @return object   This object
     */
    public function setValues( array $values )
    {
        $this->values = array_merge( $this->values, $values );
        return $this;
    }

    /**
     * Sets a single local value that will be saved later in Zammad by a call to save().
     *
     * @param string $key       Key of value to set.
     * @param mixed $value      Value to set.
     *
     * @return object   This object
     */
    public function setValue( $key, $value )
    {
        $this->values[$key] = $value;
        return $this;
    }

    /**
     * Gets value of object.
     *
     * First, it's been checked if the key exists in the local values that will be saved later in Zammad
     * by a call to save().
     *
     * If not found, additionally the remote data will be searched for the key. This
     * ensures that a value is being returned that hasn't been changed locally.
     *
     * @param string $key       Key to fetch value for.
     *
     * @return mixed            Value or null if not found. Null could also be the set value.
     */
    public function getValue($key)
    {
        if ( array_key_exists( $key, $this->values ) ) {
            return $this->values[$key];
        }

        $remote_data = $this->getRemoteData();
        if ( array_key_exists( $key, $remote_data ) ) {
            return $remote_data[$key];
        }

        return null;
    }

    /**
     * Gets all values of object.
     *
     * Local values will be merged with remote data, where local values overwrite remote data.
     * This ensures that values can be returned that haven't been changed locally.
     *
     * @return mixed        Array with values of object.
     */
    public function getValues()
    {
        $values = array_merge( $this->getRemoteData(), $this->values );
        return $values;
    }

    /**
     * Gets all unsaved values of object.
     *
     * @return mixed        Array with unsaved values of object.
     */
    public function getUnsavedValues()
    {
        return $this->values;
    }

    /**
     * Clears local values, which means that the local changes will be discarded and
     * a call to save() does not update the remote data.
     *
     * @return object   This object
     */
    public function clearUnsavedValues()
    {
        $this->values = [];
        return $this;
    }

    /**
     * Gets the ID of the object.
     * This is a convenience method for getValue('id')
     *
     * @return string       ID of this object, if available.
     */
    public function getID()
    {
        return $this->getValue('id');
    }

    /**
     * Checks if there are unsaved local values.
     *
     * @return boolean          True: dirty, false: not dirty.
     */
    public function isDirty()
    {
        return !empty( $this->getUnsavedValues() );
    }

    /**
     * Sets error message from last action.
     *
     * @param string $error      Error
     *
     * @return object   This object
     */
    protected function setError( $error )
    {
        $this->error = $error;
        return $this;
    }

    /**
     * Gets error message from last action.
     *
     * @return string   Error from last action
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Checks if last action resulted in an error.
     *
     * @return boolean
     */
    public function hasError()
    {
        return !empty( $this->getError() );
    }

    /**
     * Clears error message from last action.
     *
     * @return object   This object
     */
    protected function clearError()
    {
        $this->error = null;
        return $this;
    }

    /**
     * Fetches object data for given object ID.
     *
     * @param mixed $object_id      ID of object to fetch, false value or omit to fetch all objects.
     *
     * @return object               This object.
     */
    public function get($object_id)
    {
        if ( !empty( $this->getValues() ) ) {
            throw new AlreadyFetchedObjectException('Object already contains values, get() not possible, use a new object');
        }

        if ( empty($object_id) ) {
            throw new \RuntimeException('Missing object ID');
        }

        $url = $this->getURL(
            'get',
            [
                'object_id' => $object_id,
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
        }
        else {
            $this->clearError();
            $this->setRemoteData( $response->getData() );
        }

        return $this;
    }

    /**
     * Fetches object data for all objects of this type.
     * Pagination available.
     *
     * @param integer $page                 Page of objects, optional, if given, $objects_per_page must also be given.
     * @param integer $objects_per_page     Number of objects per page, optional, if given, $page must also be given.
     *
     * @return mixed                        Returns array of ZammadAPIClient\Resource\... objects
     *                                          or this object on failure.
     */
    public function all( $page = null, $objects_per_page = null )
    {
        if ( !empty( $this->getValues() ) ) {
            throw new AlreadyFetchedObjectException('Object already contains values, all() not possible, use a new object');
        }

        if ( isset($page) && $page <= 0 ) {
            throw new \RuntimeException('Parameter page must be > 0');
        }
        if ( isset($objects_per_page) && $objects_per_page <= 0 ) {
            throw new \RuntimeException('Parameter objects_per_page must be > 0');
        }
        if (
            ( isset($page) && !isset($objects_per_page) )
            || ( !isset($page) && isset($objects_per_page) )
        ) {
            throw new \RuntimeException('Parameters page and objects_per_page must both be given');
        }

        $url_parameters = [
            'expand' => true,
        ];

        if ( !empty($page) && !empty($objects_per_page) ) {
            $url_parameters['page']     = $page;
            $url_parameters['per_page'] = $objects_per_page;
        }

        $url      = $this->getURL('all');
        $response = $this->getClient()->get(
            $url,
            $url_parameters
        );

        if ( $response->hasError() ) {
            $this->setError( $response->getError() );
            return $this;
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
     * Fetches object data for given search term.
     * Pagination available.
     *
     * @param string  $search_term          Search term.
     * @param integer $page                 Page of objects, optional, if given, $objects_per_page must also be given.
     * @param integer $objects_per_page     Number of objects per page, optional, if given, $page must also be given.
     *
     * @return mixed                        Returns array of ZammadAPIClient\Resource\... objects
     *                                          or this object on failure.
     */
    public function search( $search_term, $page = null, $objects_per_page = null )
    {
        if ( !empty( $this->getValues() ) ) {
            throw new AlreadyFetchedObjectException('Object already contains values, search() not possible, use a new object');
        }

        if ( !empty($page) && $page <= 0 ) {
            throw new \RuntimeException('Parameter page must be a > 0');
        }
        if ( !empty($objects_per_page) && $objects_per_page <= 0 ) {
            throw new \RuntimeException('Parameter objects_per_page must be a > 0');
        }
        if (
            ( !empty($page) && empty($objects_per_page) )
            || ( empty($page) && !empty($objects_per_page) )
        ) {
            throw new \RuntimeException('Parameters page and objects_per_page must both be given');
        }

        $url_parameters = [
            'expand' => true,
            'query'  => $search_term,
        ];

        if ( !empty($page) && !empty($objects_per_page) ) {
            $url_parameters['page']     = $page;
            $url_parameters['per_page'] = $objects_per_page;
        }

        $url      = $this->getURL('search');
        $response = $this->getClient()->get(
            $url,
            $url_parameters
        );

        if ( $response->hasError() ) {
            $this->setError( $response->getError() );
            return $this;
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
     * Saves object data. Works for objects that are new or will be updated.
     *
     * @return mixed      Save successful (object reference, $this) or not (false).
     */
    public function save()
    {
        if ( empty( $this->getID() ) ) {
            return $this->create();
        }

        return $this->update();
    }

    /**
     * Creates a new object with the current data.
     *
     * @return object      This object
     */
    protected function create()
    {
        if ( !empty( $this->getID() ) ) {
            throw new \Exception('Object already has an ID, create() not possible');
        }

        if ( !$this->isDirty() ) {
            return $this;
        }

        $url      = $this->getURL('create');
        $response = $this->getClient()->post(
            $url,
            $this->getUnsavedValues(),
            [
                'expand' => true,
            ]
         );
        if ( $response->hasError() ) {
            $this->setError( $response->getError() );
            return $this;
        }

        $this->clearError();
        $this->setRemoteData( $response->getData() );
        $this->clearUnsavedValues();

        return $this;
    }

    /**
     * Updates an existing object with the current data.
     *
     * @return object      This object
     */
    protected function update()
    {
        $object_id = $this->getID();
        if ( empty($object_id) ) {
            throw new \Exception('Object has no ID, update() not possible');
        }

        if ( !$this->isDirty() ) {
            return $this;
        }

        $url = $this->getURL(
            'update',
            [
                'object_id' => $object_id,
            ]
        );

        $response = $this->getClient()->put(
            $url,
            $this->getUnsavedValues(),
            [
                'expand' => true,
            ]
        );
        if ( $response->hasError() ) {
            $this->setError( $response->getError() );
            return $this;
        }

        $this->clearError();
        $this->setRemoteData( $response->getData() );
        $this->clearUnsavedValues();

        return $this;
    }

    /**
     * Deletes the data of this object.
     * If data contains an ID, the object will also be deleted in Zammad.
     *
     * @return object      This object
     */
    public function delete()
    {
        // Delete object in Zammad.
        $object_id = $this->getID();
        if ( !empty($object_id) ) {
            $url = $this->getURL(
                'delete',
                [
                    'object_id' => $object_id,
                ]
            );

            $response = $this->getClient()->delete(
                $url,
                [
                    'expand' => true,
                ]
            );
            if ( $response->hasError() ) {
                $this->setError( $response->getError() );
                return $this;
            }
        }

        // Clear data of this (local) object.
        $this->clearError();
        $this->clearRemoteData();
        $this->clearUnsavedValues();

        return $this;
    }

    /**
     * Returns the URL for the given method name, including its replaced placeholders.
     *
     * @param string $method_name               E. g. 'get', 'all', etc.
     * @param array  $placeholder_values        Array of placeholder => value pairs,
     *                                              e. g. [ 'object_id' => 2 ] will replace
     *                                              {object_id} in URL with 2.
     *
     * @return string                           URL, e. g. 'tickets/10'.
     */
    protected function getURL( $method_name, array $placeholder_values = [] )
    {
        if ( !array_key_exists( $method_name, $this::URLS ) ) {
            throw new \RuntimeException(
                "Method '$method_name' is not supported for "
                . get_class($this)
                . ' resource'
            );
        }

        $url = $this::URLS[$method_name];
        foreach ( $placeholder_values as $placeholder => $value ) {
            $url = preg_replace( "/\{$placeholder\}/", "$value", $url );
        }

        return $url;
    }

    public function can( $method_name )
    {
        return array_key_exists( $method_name, $this::URLS );
    }
}
