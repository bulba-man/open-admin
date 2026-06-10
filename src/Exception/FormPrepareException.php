<?php

namespace OpenAdmin\Admin\Exception;

use Exception;

class FormPrepareException extends Exception
{
    /**
     * The recommended response to send to the client.
     *
     * @var \Symfony\Component\HttpFoundation\Response|null
     */
    public $response;

    public function __construct($response = null)
    {
        parent::__construct('Form prepare failed.');

        $this->response = $response;
    }

    /**
     * Get the underlying response instance.
     *
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function getResponse()
    {
        return $this->response;
    }
}