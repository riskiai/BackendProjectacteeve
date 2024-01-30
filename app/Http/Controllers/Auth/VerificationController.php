<?php

namespace App\Http\Controllers\Auth;

use App\Facades\MessageActeeve;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

class VerificationController extends Controller
{
    public function __invoke(Request $request)
    {
        DB::beginTransaction();

        $user = User::whereEmail($request->email)->first();
        if (!$user) {
            return MessageActeeve::notFound('account not found!');
        }

        $request->merge([
            "password" => ""
        ]);

        try {
            $status = Password::reset(
                $request->only('email', 'token', 'password'),
                function ($user, $password) {
                    $user->forceFill([
                        'email_verified_at' => Carbon::now()
                    ])->setRememberToken(Str::random(10));

                    $user->save();

                    event(new PasswordReset($user));
                }
            );

            DB::commit();
            if ($status == Password::PASSWORD_RESET) {
                return MessageActeeve::success("Verification successfully.");
            } else {
                return MessageActeeve::render([
                    'status' => MessageActeeve::WARNING,
                    'status_code' => MessageActeeve::HTTP_UNAUTHORIZED,
                    'message' => 'Token is expired.'
                ], MessageActeeve::HTTP_UNAUTHORIZED);
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }
}
