<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Models\Posts;
use App\Models\User;
use App\Models\UserRelation;
use App\Models\PostLike;
use App\Models\PostComment;
use Intervention\Image\Facades\Image;

class FeedController extends Controller
{
    private $loggedUser;
    public function __construct()
    {
        $this->middleware('auth:sanctum');

        $this->loggedUser = auth()->user();
    }
    public function create(Request $request)
    {
        $array = ['error' => ''];

        $data = $request->only([
            'type',
            'body',
            'photo'
        ]);

        $rules = [
            'type' => ['required', 'string', Rule::in(array('text', 'photo'))],
            'body' => [
                Rule::requiredIf($data['type'] === 'text'),
                'exclude_if:type,photo',
                'string'
            ],
            'photo' => [
                Rule::requiredIf($data['type'] === 'photo'),
                'exclude_if:type,text',
                'image',
                'max:10000',
                'mimes:jpeg,jpg,png'
            ]
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $array['error'] = $validator->errors();
            return json_encode($array);
        }

        $type = $data['type'];
        $body = $data['body'] ?? '';
        $photo = $data['photo'] ?? '';

        $newPost = new Posts();
        $newPost->user_id = Auth::id();
        $newPost->type = $type;
        $newPost->created_at = date('Y-m-d H:i:s');
        switch ($type) {
            case 'text':
                $newPost->body = $body;
                break;
            case 'photo':
                $filename = md5(time() * rand(12, 9999)) . '.jpg';
                $destPath = public_path('/media/uploads/') . '/' . $filename;
                $manager = Image::make($photo->path())->resize(800, null, function ($constraint) {
                    $constraint->aspectRatio();
                })->save($destPath);
                $newPost->body = $filename;
                break;
        }
        $newPost->save();
        $array['post'] = $newPost;

        return json_encode($array);
    }


    public function read(Request $request)
    {
        $array = ['error' => ''];


        $page = intval($request->input('page'));
        $perPage = 1;

        $users = [];
        $userList = UserRelation::where('user_from', Auth::id());
        foreach ($userList as $user) {
            $users[] = $user['user_to'];
        }
        $users[] =  Auth::id();

        $postList = Posts::whereIn('user_id', $users)->orderBy('created_at', 'desc')
            ->offset($page * $perPage)->limit($perPage)->get();

        $posts = $this->_postListToObject($postList, Auth::id());

        $array['posts'] = $posts;
        $total = Posts::whereIn('user_id', $users)->count();
        $pageCount = ceil($total / $perPage);
        $array['pageCount'] = $pageCount;
        $array['currentPage'] = $page;
        return json_encode($array);
    }

    private function _postListToObject($postList, $idUser)
    {
        foreach ($postList as $postKey => $postItem) {
            if ($postItem['user_id'] == $idUser) {
                $postList[$postKey]['mine'] = true;
            } else {
                $postList[$postKey]['mine'] = false;
            }

            $userInfo = User::find($postItem['user_id']);
            $userInfo['avatar'] = url('/media/avatars' . $userInfo['avatar']);
            $userInfo['cover'] = url('/media/covers' . $userInfo['cover']);
            $postList[$postKey]['user'] = $userInfo;

            $likes = PostLike::where('id_post', $postItem['id'])->count();
            $postList[$postKey]['likeCount'] = $likes;

            $isLiked = PostLike::where('id_post', $postItem['id'])
                ->where('id_user', $idUser)->count();

            $postList[$postKey]['liked'] = ($isLiked > 0) ? true : false;


            $comments = PostComment::where('id_post', $postItem['id'])->get();
            foreach ($comments as $commentKey => $comment) {
                $user = User::find($comment['id_user']);
                $user['avatar'] = url('/media/avatars' . $user['avatar']);
                $user['cover'] = url('/media/covers' . $user['cover']);
                $comments[$commentKey]['user'] = $user;
            }
            $postList[$postKey]['comments'] = $comments;
        }


        return $postList;
    }

    public function userFeed(Request $request, $id = false)
    {
        $array = ['error' => ''];
        
        if($id == false){
            $id = Auth::id();
        }

        $page = intval($request->input('page'));
        $perPage = 1;

        $postList = Posts::where('user_id', $id)->orderBy('created_at', 'desc')
            ->offset($page * $perPage)->limit($perPage)->get();

        $posts = $this->_postListToObject($postList, Auth::id());

        $array['posts'] = $posts;
        $total = Posts::where('user_id', $id)->count();
        $pageCount = ceil($total / $perPage);
        $array['pageCount'] = $pageCount;
        $array['currentPage'] = $page;

        return json_encode($array);
    }

    public function userPhotos(Request $request, $id = false){
        $array = ['error' => ''];
        
        if($id == false){
            $id = Auth::id();
        }

        $page = intval($request->input('page'));
        $perPage = 1;

        $postList = Posts::where('user_id', $id)->where('type','photo')->orderBy('created_at', 'desc')
            ->offset($page * $perPage)->limit($perPage)->get();

        $photos = $this->_postListToObject($postList, Auth::id());


        foreach($photos as $pkey => $photo){
            $photos[$pkey]['body'] = url('/media/uploads/'.$photos[$pkey]['body']);
        }

        $array['photos'] = $photos;
        $total = Posts::where('user_id', $id)->where('type','photo')->count();
        $pageCount = ceil($total / $perPage);
        $array['pageCount'] = $pageCount;
        $array['currentPage'] = $page;

        return json_encode($array);
    }
}
