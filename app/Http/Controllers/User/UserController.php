<?php

namespace App\Http\Controllers\User;

use App\Facades\MessageActeeve;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdatePasswordRequest;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\UserCollection;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('id', 'like', '%' . $request->search . '%');
                $query->orWhere('name', 'like', '%' . $request->search . '%');
                $query->orWhereHas('role', function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search . '%');
                });
            });
        }

        if ($request->has('date')) {
            $date = str_replace(['[', ']'], '', $request->date);
            $date = explode(", ", $date);

            $query->whereBetween('created_at', $date);
        }

        $users = $query->paginate($request->per_page);

        return new UserCollection($users);
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        DB::beginTransaction();

        $user = User::findOrFail(auth()->user()->id);

        try {
            $user->update([
                "password" => Hash::make($request->new_password)
            ]);

            DB::commit();
            return MessageActeeve::success("user $user->name has been updated");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }

    public function resetPassword(Request $request, $id)
    {
        DB::beginTransaction();

        $user = User::find($id);
        if (!$user) {
            return MessageActeeve::notFound("data not found!");
        }

        $password = Str::random(8);
        if ($request->has('password')) {
            $password = $request->password;
        }

        try {
            $user->update([
                "password" => Hash::make($password)
            ]);
            $user->passwordRecovery = $password;

            Mail::to($user)->send(new ResetPasswordMail($user));

            DB::commit();
            return MessageActeeve::success("user $user->name has been updated");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();

        $user = User::find($id);
        if (!$user) {
            return MessageActeeve::notFound('data not found!');
        }

        try {
            $user->delete();

            DB::commit();
            return MessageActeeve::success("user $user->name has been deleted");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
    }
}
