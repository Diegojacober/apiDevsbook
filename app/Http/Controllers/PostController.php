<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Posts;
use App\Models\PostLike;
use App\Models\PostComment;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    private $loggedUser;
    public function __construct()
    {
        $this->middleware('auth:sanctum');

        $this->loggedUser = auth()->user();
        
    }
    public function like($id){
        $array = ['error' => ''];

        $postExists= Posts::find($id);

        if($postExists){

            $isLiked = PostLike::where('id_post',$id)->where('id_user',Auth::id())->count();

            if($isLiked > 0){

                $like = PostLike::where('id_post',$id)->where('id_user',Auth::id())->first();
                $like->delete();
                $array['postLikes'] = PostLike::where('id_post',$id)->count();
                $array['isLiked'] = false;
            }else{

                $newLike = new PostLike();
                $newLike->id_post = $id;
                $newLike->id_user = Auth::id();
                $newLike->created_at = date('Y-m-d H:i:s');
                $newLike->save();
                $array['postLikes'] = PostLike::where('id_post',$id)->count();
                $array['isLiked'] = true;
            }


        }else{
            $array['error'] = 'Post Inexistente';
            return $array;
        }


        return $array;
    }

    public function comment(Request $request,$id){
        $array = ['error' => ''];
        
        $comment = $request->input('txt');

        $rules =  [
            'txt' => ['required', 'string','min:2'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $array['error'] = $validator->errors();
            return json_encode($array);
        }

        $postExists = Posts::find($id);

        if($postExists){

            $newComment = new PostComment();
            $newComment->id_post = $id;
            $newComment->id_user = Auth::id();
            $newComment->created_at = date('Y-m-d H:i:s');
            $newComment->body = $comment;
            $newComment->save();

        }else{
            $array['error'] = 'Post Inexistente';
        }

        return $array;

    }
}
