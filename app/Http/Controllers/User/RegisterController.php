<?php

namespace App\Http\Controllers\User;

use App\Facades\MessageActeeve;
use App\Facades\SendMail;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request)
    {
        DB::beginTransaction();

        $role = Role::find($request->role);

        $request->merge([
            'role_id' => $role->id,
            'password' => Hash::make('ACT123')
        ]);

        try {
            $user = User::create($request->all());

            SendMail::verification($user);

            DB::commit();
            return MessageActeeve::created("has been added user, and show email for verification.");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }
}
