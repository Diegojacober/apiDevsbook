<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\User;

class SearchController extends Controller
{
    private $loggedUser;
    public function __construct()
    {
        $this->middleware('auth:sanctum');

        $this->loggedUser = auth()->user();
        
    }
    public function search(Request $request){
        $array = ['error' => '','users' => ''];

        $txt = $request->input('txt');

        $rules =  [
            'txt' => ['required', 'string','min:1'],
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $array['error'] = $validator->errors();
            return json_encode($array);
        }

        $userList = User::where('name','like','%'.$txt.'%')->get();
        foreach($userList as $userKey => $userItem){
            $array['users'] = [
                'id' => $userItem['id'],
                'name' => $userItem['name'],
                'avatar' => url('/media/avatars/'.$userItem['avatar']),
                'cover' => url('/media/covers/'.$userItem['cover'])
            ];
        }

        return json_encode($array);
    }
}
