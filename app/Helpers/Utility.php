<?php

namespace App\Helpers;

class Utility
{
    public static function apiSuccess($message = 'Success', $data = [], $code = 200)
    {
        return response()->json([
            'status'  => true,
            'code'    => $code,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    public static function apiError($message = 'Error', $errors = [], $code = 422)
    {
        return response()->json([
            'status'  => false,
            'code'    => $code,
            'message' => $message,
            'errors'  => $errors,
        ], $code);
    }
}
