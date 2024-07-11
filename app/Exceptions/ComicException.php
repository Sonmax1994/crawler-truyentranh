<?php

namespace App\Exceptions;

use Exception;

class ComicException extends Exception
{
    public function __construct(protected $data = [], protected $status = true, protected $message = "")
    {

    }

    public function render()
    {
        return response()->json([
            'status' => $this->status,
            'message' => $this->message,
            'data' => $this->data
        ]);
    }
}
