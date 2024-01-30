<?php

namespace App\Facades;

use App\Notifications\SendEmailVerification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

class SendMail
{
    public static function verification($user)
    {
        if ($user) {
            $token = Password::createToken(
                $user
            );

            $url = url("/auth/verification?token=$token&email=$user->email");
            return Notification::route('mail', $user->email)->notify(new SendEmailVerification($url, $user));
        }
    }
}
