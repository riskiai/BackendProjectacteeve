<?php

namespace App\Http\Controllers\User;

use App\Facades\MessageActeeve;
use App\Http\Controllers\Controller;
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
}
