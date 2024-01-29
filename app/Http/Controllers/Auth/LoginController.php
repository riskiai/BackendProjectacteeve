<?php

namespace App\Http\Controllers\Auth;

use App\Facades\MessageActeeve;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request)
    {
        DB::beginTransaction();

        $user = User::whereEmail($request->email)->first();
        if (!$user) {
            return MessageActeeve::render([
                'status' => MessageActeeve::WARNING,
                'status_code' => MessageActeeve::HTTP_BAD_REQUEST,
                'message' => 'email or password wrong!'
            ], MessageActeeve::HTTP_BAD_REQUEST);
        }

        if (!Hash::check($request->password, $user->password)) {
            return MessageActeeve::render([
                'status' => MessageActeeve::WARNING,
                'status_code' => MessageActeeve::HTTP_BAD_REQUEST,
                'message' => 'email or password wrong!'
            ], MessageActeeve::HTTP_BAD_REQUEST);
        }

        try {
            $token = $user->createToken('api', ['authenticated'])->plainTextToken;

            return MessageActeeve::render([
                'data' => $user,
                'secret' => $token,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }
}
