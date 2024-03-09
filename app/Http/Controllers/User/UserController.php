<?php

namespace App\Http\Controllers\User;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Facades\MessageActeeve;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Resources\UserCollection;
use App\Http\Requests\User\UpdateRequest;
use App\Http\Requests\User\UpdatePasswordRequest;

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

    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return MessageActeeve::notFound('User not found!');
        }

        return MessageActeeve::render([
            'status' => MessageActeeve::SUCCESS,
            'status_code' => MessageActeeve::HTTP_OK,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                ]
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, $id)
    {
        DB::beginTransaction();
    
        $user = User::find($id);
        if (!$user) {
            return MessageActeeve::notFound('Data not found!');
        }
    
        try {
            $userData = [];
    
            // Update bidang-bidang yang disertakan dalam permintaan
            if ($request->has('name')) {
                $userData['name'] = $request->name;
            }
            if ($request->has('email')) {
                $userData['email'] = $request->email;
            }
            if ($request->has('role')) {
                $userData['role_id'] = $request->role;
            }
    
            $user->update($userData);
    
            DB::commit();
            return MessageActeeve::success("User $user->name has been updated");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageActeeve::error($th->getMessage());
        }
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
