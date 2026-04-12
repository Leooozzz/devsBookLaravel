<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'create', 'unauthorized']]);
    }
    public function create(Request $request)
    {

        $array = ['error'=>''];
        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');
        $birthday = $request->input('birthday');

        if ($name && $email && $password && $birthday) {
            if (strtotime($birthday) === false) {
                $array['error'] = "Invalid birthdate";
                return $array;
            }
            $email_exists = User::where('email', $email)->count();
            if ($email_exists === 0) {
                $hash = Hash::make($password);

                $new_user = new User();

                $new_user->name = $name;
                $new_user->email = $email;
                $new_user->password = $hash;
                $new_user->birthday = $birthday;

                $new_user->save();
        
                $array['user'] = $new_user;
                
            } else {
                $array['error'] = "email already registered";
                return $array;
            }
        } else {
            $array['error'] = 'did not send all fields';
            return $array;
        }
        return $array;
    }
}
