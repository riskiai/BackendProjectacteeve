<?php

namespace App\Facades;

use Illuminate\Http\Response;

class MessageActeeve extends Response
{
    const WARNING = 'WARNING';
    const SUCCESS = 'SUCCESS';
    const ERROR = 'ERROR';

    public static function render($data = [], $statusCode = self::HTTP_OK)
    {
        return response()->json($data, $statusCode);
    }

    public static function error($message)
    {
        return response()->json([
            'status' => self::ERROR,
            'status_code' => self::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $message
        ], self::HTTP_INTERNAL_SERVER_ERROR);
    }
}
