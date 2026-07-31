<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class Utility
{
    public static function apiSuccess($message = 'Success', $data = [], $code = 200)
    {
        return response()->json([
            'status' => true,
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public static function apiError($message = 'Error', $errors = [], $code = 221)
    {
        return response()->json([
            'status' => false,
            'code' => $code,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    public static function numberToWords($amount, $currency)
    {
        $f = new \NumberFormatter('en_IN', \NumberFormatter::SPELLOUT);
        $words = ucfirst($f->format($amount));

        return $words.' '.$currency.'  only ';
    }

    public static function checkViewPermission($moduleName, $userId = '')
    {
        $authUserId = Auth::id() ?? $userId;

        return User::whereKey($authUserId)
            ->whereHas('role.permissions', function ($query) use ($moduleName) {
                $query->whereIn('name', [
                    'view_own_'.$moduleName,
                ]);
            })
            ->exists();
    }

    public static function checkBranchesViewPermission($moduleName, $userId = '')
    {
        $authUserId = Auth::id() ?? $userId;

        return User::whereKey($authUserId)
            ->whereHas('role.permissions', function ($query) use ($moduleName) {
                $query->whereIn('name', [
                    'view_branches_'.$moduleName,
                ]);
            })
            ->exists();
    }
}
