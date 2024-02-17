<?php

namespace App\Http\Controllers\User;

use App\Facades\MessageActeeve;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\UserCollection;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        $users = $query->paginate($request->per_page);

        return new UserCollection($users);
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
