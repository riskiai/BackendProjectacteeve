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

    public static function success($message)
    {
        return response()->json([
            'status' => self::SUCCESS,
            'status_code' => self::HTTP_OK,
            'message' => $message
        ], self::HTTP_OK);
    }

    public static function created($message)
    {
        return response()->json([
            'status' => self::SUCCESS,
            'status_code' => self::HTTP_CREATED,
            'message' => $message
        ], self::HTTP_CREATED);
    }

    public static function notFound($message)
    {
        return response()->json([
            'status' => self::WARNING,
            'status_code' => self::HTTP_NOT_FOUND,
            'message' => $message
        ], self::HTTP_NOT_FOUND);
    }

    public static function warning($message)
    {
        return response()->json([
            'status' => self::WARNING,
            'status_code' => self::HTTP_BAD_REQUEST,
            'message' => $message
        ], self::HTTP_BAD_REQUEST);
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
