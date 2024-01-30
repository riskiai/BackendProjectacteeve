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
            $role = [strtolower($user->role->name)];
            $token = $user->createToken('api', $role)->plainTextToken;

            DB::commit();
            return MessageActeeve::render([
                "id" => $user->id,
                "role_id" => $user->role_id,
                "name" => $user->name,
                "email" => $user->email,
                "email_verified_at" => $user->email_verified_at,
                "created_at" => $user->created_at,
                "updated_at" => $user->updated_at,
                'secret' => $token,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }
}
