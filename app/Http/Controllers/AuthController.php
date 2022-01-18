<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'auth:sanctum',
            [
                'except'
                =>
                [
                    'login',
                    'create',
                    'unauthorized'
                ]
            ]
        );
    }

    public function create(Request $request)
    {

        $array = ['error' => ''];

        $rules =  [
            'name' => ['required', 'string', 'min:5', 'max:255'],
            'email' => ['required', 'string', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:6'],
            'birthdate' => ['required', 'date']
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $array['error'] = $validator->errors();
            return json_encode($array);
        }
        $name = $request->input('name');
        $email = $request->input('email');
        $birthdate = $request->input('birthdate');
        $password = $request->input('password');

        //criando o novo usuário
        $newUser = new User();
        $newUser->name = $name;
        $newUser->email = $email;
        $newUser->birthdate = $birthdate;
        $newUser->password = password_hash($password, PASSWORD_DEFAULT);
        $newUser->token = '';
        $newUser->save();

        $creds = $request->only('email', 'password');
        if (Auth::attempt($creds)) {
            $user = User::where('email', $creds['email'])->first();

            $item = md5(time() * rand(0, 9999));
            $token = $user->createToken($item)->plainTextToken;

            $array['token'] = $token;
        } else {
            $array['error'] = 'E-mail ou senha incorretos!';
        }

        //logar o usuário recem criado

        return json_encode($array);
    }

    public function unauthorized()
    {
        return response()->json(['error' => 'Não autorizado'], 401);
    }

    public function login(Request $request)
    {
        $array = ['error' => ''];

        $creds = $request->only('email', 'password');

        if (Auth::attempt($creds)) {
            $user = User::where('email', $creds['email'])->first();

            $item = md5(time() * rand(0, 9999));
            $token = $user->createToken($item)->plainTextToken;

            $array['token'] = $token;
        } else {
            $array['error'] = 'E-mail ou senha incorretos!';
            return json_encode($array);
        }
        return json_encode($array);
    }
    public function logout(Request $request)
    {
        $array = ['error' => ''];

        $user = $request->user();

        $user->tokens()->delete();

        return json_encode($array);
    }

    public function refresh(Request $request){
        $request->user()->tokens()->delete();

        return response()->json([
            'access_token' => $request->user()->createToken('api')->plainTextToken,
        ]);
    }
}
