<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class FeedController extends Controller
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
}
