<?php

namespace App\Exceptions;

use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AttendeeLimitReached extends BadRequestHttpException
{
    protected $message = 'The attendee limit has been reached.';

    public function __construct(\Throwable $previous = null, array $headers = [])
    {
        parent::__construct($this->message, $previous, Response::HTTP_BAD_REQUEST, $headers);
    }
}
