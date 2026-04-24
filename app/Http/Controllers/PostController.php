<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostLike;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class PostController extends Controller
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

    public function like($id)
    {

        $postExists = Post::find($id);
        if (!$postExists) {
            return response()->json([
                'error' => 'post not exist',
            ], 400);
        }

        if ($postExists) {
            $isLiked = PostLike::where('id_post', $id)->where('id_user', $this->loggedUser['id'])->count();
        }

        if ($isLiked > 0) {
            $pl = PostLike::where('id_post', $id)->where('id_user', $this->loggedUser['id'])->first();
            $pl->delete();
            $isLiked = false;
        } else {
            $newPostLike = new PostLike();
            $newPostLike->id_post = $id;
            $newPostLike->id_user = $this->loggedUser['id'];
            $newPostLike->created_at = date('Y-m-d H:i:s');
            $newPostLike->save();

            $isLiked = true;
        }
        $likeCount = PostLike::where('id_post', $id)->count();

        return response()->json([
            'sucess' => true,
            'isLiked' => $isLiked,
            'likeCount' => $likeCount
        ], 200);
    }

    public function comment(Request $request, $id)
    {
        $txt = $request->input('text');

        $postExists = Post::find($id);
        if (!$postExists) {
            return response()->json([
                'error' => 'Post not exist',
            ], 400);
        }
        if ($postExists) {
            if (!$txt) {
                return response()->json([
                    'error' => 'Commented not send',
                ], 400);
            }
            if($txt){
                $newComment = new PostComment();
                $newComment->id_post = $id;
                $newComment->id_user = $this->loggedUser['id'];
                $newComment->created_at = date('Y-m-d H:i:s');
                $newComment->save();
            }
        }
        return response()->json([
            'sucess' => true,
            'comment'=>$newComment,
        ], 200);
    }
}
