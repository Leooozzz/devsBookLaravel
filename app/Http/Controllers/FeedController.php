<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostLike;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;

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


    public function create(Request $request)
    {
        $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];
        $type = $request->input('type');
        $body = $request->input('body');
        $photo = $request->file('photo');
        $newPost = new Post();

        if ($type) {
            switch ($type) {
                case 'text':
                    if (!$body) {
                        return response()->json([
                            'error' => 'Text not send'
                        ], 400);
                    }
                    break;
                case 'photo':
                    if ($photo) {
                        if (in_array($photo->getClientMimeType(), $allowedTypes)) {
                            $filename = md5(time() . rand(0, 9999)) . '.jpg';
                            $destPath = public_path('/media/uploads');
                            $img = Image::make($photo->path())->resize(800, null, function ($constraint) {
                                $constraint->aspectRatio();
                            })->save($destPath . '/' . $filename);
                            $body = $filename;
                        } else {
                            return response()->json([
                                'error' => 'File not supported'
                            ], 400);
                        }
                    } else {
                        return response()->json([
                            'error' => 'File not send'
                        ], 400);
                    }
                    break;
                default:
                    return response()->json([
                        'error' => 'Type of post non-existent'
                    ], 400);
                    break;
            }
            if ($body) {
                $newPost->id_user = $this->loggedUser['id'];
                $newPost->type = $type;
                $newPost->body = $body;
                $newPost->created_at = date('Y-m-d H:i:s');
                $newPost->save();
            }
        } else {
            return response()->json([
                'error' => 'Invalid or missing file'
            ], 400);
        }


        return response()->json([
            'sucess' => true
        ], 200);
    }


    public function read(Request $request)
    {

        $page = intval(request()->input('page'));
        $perPage = 10;


        $users = [];

        $userList = UserRelation::where('user_from', $this->loggedUser['id'])->get();
        foreach ($userList as $userItem) {
            $users[] = $userItem['user_to'];
        }
        $users[] = $this->loggedUser['id'];


        $postList = Post::whereIn('id_user', $users)->orderBy('created_at', 'desc')->offset($page * $perPage)->limit($perPage)->get();

        $total = Post::whereIn('id_user', $users)->count();

        $pageCount = ($total / $perPage);

        $posts = $this->_postListToObject($postList, $this->loggedUser['id']);
        return response()->json([
            'sucess' => true,
            'posts' => $posts,
            'currentPage' =>$page,
            'pageCount' => ceil($pageCount)
        ], 200);
    }
    private function _postListToObject($postList, $idUser)
{
    foreach ($postList as $postKey => $postItem) {

        $postList[$postKey]['mine'] = ($postItem['id_user'] === $idUser);

        $userInfo = User::find($postItem['id_user']);
        $userInfo['cover'] = url('/media/covers/' . $userInfo['cover']);
        $userInfo['avatar'] = url('/media/avatars/' . $userInfo['avatar']);
        $postList[$postKey]['user'] = $userInfo;

        $likes = PostLike::where('id_post', $postItem['id'])->count();
        $postList[$postKey]['likeCount'] = $likes;

        $isLiked = PostLike::where('id_post', $postItem['id'])
            ->where('id_user', $idUser)
            ->count();

        $postList[$postKey]['liked'] = ($isLiked > 0);

        $comments = PostComment::where('id_post', $postItem['id'])->get();

        foreach ($comments as $commentKey => $comment) {
            $user = User::find($comment['id_user']);
            $user['cover'] = url('/media/covers/' . $user['cover']);
            $user['avatar'] = url('/media/avatars/' . $user['avatar']);

            $comments[$commentKey]['user'] = $user;
        }

        $postList[$postKey]['comments'] = $comments;
    }

    return $postList;
}
}
