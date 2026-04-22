<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;

class UserController extends Controller
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

    public function update(Request $request)
    {

        $name = $request->input('name');
        $email = $request->input('email');
        $birthday = $request->input('birthday');
        $city = $request->input('city');
        $work = $request->input('work');
        $password = $request->input('password');
        $password_confirm = $request->input('password_confirm');

        $user = User::find($this->loggedUser['id']);

        if ($name) {
            $user->name = $name;
        }
        if ($email) {
            if ($email != $user->email) {
                $emailExists = User::where('email', $email)->count();
                if ($emailExists === 0) {
                    $user->email = $email;
                } else {
                    return response()->json([
                        'error' => 'Email exists'
                    ], 409);
                }
            }
        }
        if ($birthday) {
            if (strtotime($birthday) === false) {
                return response()->json([
                    'error' => 'Invalid birthdate'
                ], 400);
            }
            $user->birthday = $birthday;
        }
        if ($city) {
            $user->city = $city;
        }
        if ($work) {
            $user->work = $work;
        }
        if ($password && $password_confirm) {
            if ($password != $password_confirm) {
                return response()->json([
                    'error' => 'Invalid password'
                ], 400);
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $user->password = $hash;
        }
        $user->save();

        return response()->json([
            'sucess' => true,
            'user' => $user->only([
                'id',
                'name',
                'email',
                'birthday',
                'city',
                'work',
                'avatar'
            ])
        ], 200);
    }

    public function updateAvatar(Request $request)
    {
        $user = User::find($this->loggedUser['id']);

        $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];

        $image = $request->file('avatar');

        if ($image) {
            if (in_array($image->getClientMimeType(), $allowedTypes)) {
                $filename = md5(time() . rand(0, 9999)) . '.jpg';
                $destPath = public_path('/media/avatars');

                $img = Image::make($image->path())->fit(200, 200)->save($destPath . '/' . $filename);
                $user->avatar = $filename;
                $user->save();
            } else {
                return response()->json([
                    'error' => 'Invalid or missing file'
                ], 400);
            }
        } else {
            return response()->json([
                'error' => 'Invalid or missing file'
            ], 400);
        }
        return response()->json([
            'sucess' => true,
            'user' => $user->only([
                'id',
                'name',
                'email',
                'birthday',
                'city',
                'work',
                'avatar'
            ])
        ], 200);
    }

    public function updateCover(Request $request) {}
}
