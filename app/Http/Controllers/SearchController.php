<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');

        $this->middleware(function ($request, $next) {
            $this->loggedUser = Auth::user();
            return $next($request);
        });
    }

    public function search(Request $request)
    {
        $txt = $request->input('txt');

        if (!$txt) {
            return response()->json([
                'error' => 'Search not completed',
            ], 400);
        }

        $users = User::where('name', 'like', '%' . $txt . '%')->get();

        $userList = [];

        foreach ($users as $userItem) {
            $userList[] = [
                'id' => $userItem->id,
                'name' => $userItem->name,
                'avatar' => url('media/avatars/' . $userItem->avatar)
            ];
        }

        return response()->json([
            'success' => true,
            'users' => $userList
        ]);
    }
}
