<?php

namespace OpenAdmin\Admin\Exception;

use Exception;
use Illuminate\Support\MessageBag;

class FormValidationException extends Exception
{
    public $messageBag;

    /**
     * The recommended response to send to the client.
     *
     * @var \Symfony\Component\HttpFoundation\Response|null
     */
    public $response;

    public function __construct(MessageBag $messageBag, $response = null)
    {
        parent::__construct($messageBag->first());

        $this->messageBag = $messageBag;
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