<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\PostComment;
use App\Models\PostLike;
use App\Models\Posts;
use App\Models\UserRelation;
use Intervention\Image\Facades\Image;

class UserController extends Controller
{
    private $loggedUser;
    public function __construct()
    {
        $this->middleware('auth:sanctum');

        $this->loggedUser = auth()->user();
    }

    public function update(Request $request)
    {

        $array = ['error' => ''];

        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');
        $password_confirm = $request->input('password_confirm');
        $city = $request->input('city');
        $work = $request->input('work');
        $birthdate = $request->input('birthdate');

        $user = User::find(Auth::id());

        if ($name) {
            $user->name = $name;
        }
        if ($email) {
            if ($email != $user->email) {
                $emailExists = User::where('email', $email)->count();
                if ($emailExists === 0) {
                    $user->email = $email;
                } else {
                    $array['error'] = 'o e-mail já existe';
                    return $array;
                }
            }
        }
        if ($birthdate) {
            if (strtotime($birthdate) === false) {
                $array['error'] = 'Data de nascimento inválida';
                return $array;
            } else {
                $user->birthdate = $birthdate;
            }
        }

        if ($city) {
            $user->city = $city;
        }
        if ($work) {
            $user->work = $work;
        }

        if ($password && $password_confirm) {
            if ($password === $password_confirm) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $user->password = $hash;
            } else {
                $array['error'] = 'Você deve confirmar a senha';
                return $array;
            }
        }
        $user->save();
        return json_encode($array);
    }

    public function updateAvatar(Request $request){
       
        $array = ['error' => ''];

        $rules = [
            'avatar' => 'required|mimes:jpg,jpeg,png',
        ];
    
        $validator = Validator::make($request->all(),$rules);
    
        if($validator->fails()){
            $array['error'] = $validator->errors();
            return $array;
        }
    
        if ($request->hasFile('avatar')) {
            if ($request->file('avatar')->isValid()) {
                $ext = $request->file('avatar')->extension();
                $fileName = md5(time() + rand(0,7894)).'.'.$ext;
                $destPath = public_path('/media/avatars');
                $image = $request->file('avatar');
                $img = Image::make($image->path())->fit(200,200)->save($destPath.'/'.$fileName);

               $user = User::find(Auth::id());
               $user->avatar = $fileName;
               $user->save();

               $array['url'] = url('/media/avatars/'.$fileName);
    
            }
            else{
                $array['error'] = 'A extensão do arquivo enviado não é suportado';
            }
        } else {
            $array['error'] = 'Não foi enviado nenhum arquivo';
        }

        return $array;
    }

    public function updateCover(Request $request){

        $array = ['error' => ''];

        $rules = [
            'cover' => 'required|mimes:jpg,jpeg,png',
        ];
    
        $validator = Validator::make($request->all(),$rules);
    
        if($validator->fails()){
            $array['error'] = $validator->errors();
            return $array;
        }
    
        if ($request->hasFile('cover')) {
            if ($request->file('cover')->isValid()) {
                $ext = $request->file('cover')->extension();
                $fileName = md5(time() + rand(0,7894)).'.'.$ext;
                $destPath = public_path('/media/covers');
                $image = $request->file('cover');
                $img = Image::make($image->path())->fit(850,310)->save($destPath.'/'.$fileName);

               $user = User::find(Auth::id());
               $user->cover = $fileName;
               $user->save();

               $array['url'] = url('/media/covers/'.$fileName);
    
            }
            else{
                $array['error'] = 'A extensão do arquivo enviado não é suportado';
            }
        } else {
            $array['error'] = 'Não foi enviado nenhum arquivo';
        }

        return $array;

    }
    public function read($id = false){
        $array = ['error' => ''];

        if($id){
            $info = User::find($id);
            if(!$info){
                $array['error'] = 'Usuário informado inexistente';
                return $array;
            }
        }else{
            $id = Auth::id();
        }

        $info['avatar'] = url('/media/avatars/'.$info['avatar']);
        $info['cover'] = url('/media/covers/'.$info['cover']);

        $info['me'] = ($info['id'] == Auth::id()) ? true : false;

        $dateFrom = new \DateTime($info['birthdate']);
        $dateTo = new \DateTime('today');
        $info['age'] = $dateFrom->diff($dateTo)->y; 

        $followers = UserRelation::where('user_to',$info['id'])->count();
        $info['followers'] = $followers;

        $following = UserRelation::where('user_from',$info['id'])->count();
        $info['following'] = $following;

        $info['photoCount'] = Posts::where('user_id',$info['id'])->where('type','photo')->count();

       $HasInteraction = UserRelation::where('user_from',Auth::id())->where('user_to',$info['id'])->count();

        $info['isFollowing'] = ($HasInteraction > 0) ? true : false;

        $array['data'] = $info;
        return json_encode($array);
    }

    public function follow($id){
        $array = ['error' => ''];


        if($id == Auth::id()){
            $array['error'] = 'Você não pode se seguir';
            return json_encode($array);
        }

        $userExists = User::find($id);
        if($userExists){

            $Hasrelation = UserRelation::where('user_from',Auth::id())->where('user_to',$id)->first();

            if($Hasrelation){
                $Hasrelation->delete();
                $array['isFollowing'] = false;
            }else{
                $newRelation = new UserRelation();
                $newRelation->user_from = Auth::id();
                $newRelation->user_to = $id;
                $newRelation->save();
                $array['isFollowing'] = true;
            }
        }


        return json_encode($array);
    }

    public function followers($id = false){
        $array = ['error' => ''];

        if($id){
            $info = User::find($id);
            if(!$info){
                $array['error'] = 'Usuário informado inexistente';
                return $array;
            }
        }else{
            $id = Auth::id();
        }

        $followers = UserRelation::where('user_to',$id)->get();
        $following =UserRelation::where('user_from',$id)->get();

        $array['countFollowers'] = count($followers);
        $array['countFollowing'] = count($following);
        $array['followers'] = [];
        $array['following'] = [];

        foreach ($followers as $item) {
           $user = user::find($item['user_from']);
           $array['followers'][] = [
               'id' => $user['id'],
               'name' => $user['name'],
               'avatar' => url('/media/avatars'.$user['avatar'])
           ];
        }

        foreach ($following as $item) {
            $user = user::find($item['user_to']);
            $array['following'][] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'avatar' => url('/media/avatars'.$user['avatar'])
            ];
         }

        return json_encode($array);
    }

}
