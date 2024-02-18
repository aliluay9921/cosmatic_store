<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\Pagination;
use App\Traits\UploadImage;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use SendResponse, UploadImage, Pagination;

    public function login(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            'phone_number' => 'required',
            'password' => 'required'
        ], [
            'phone_number.required' => 'يرجى ادخال رقم الهاتف',

            'password.required' => 'يرجى ادخال كلمة المرور ',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, "حصل خطأ في المدخلات", $validator->errors(), []);
        }

        if (auth()->attempt(array('phone_number' => $request['phone_number'], 'password' => $request['password']))) {
            // $user = Auth::user();
            $user = auth()->user();
            $token = $user->createToken('cosmatic_store-ali-luay')->accessToken;
            return $this->send_response(200, 'تم تسجيل الدخول بنجاح', [], $user, $token);
        } else {
            return $this->send_response(400, 'هناك مشكلة تحقق من تطابق المدخلات', null, null, null);
        }
    }

    public function register(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            'phone_number' => 'required|unique:users,phone_number',
            'password' => 'required',
            'user_type' => 'required',
            'name' => 'required',
        ], [
            'phone_number.required' => 'يرجى ادخال رقم الهاتف',
            'phone_number.unique' => 'رقم الهاتف الذي قمت بأدخاله تم استخدامه سابقاً',
            'password.required' => 'يرجى ادخال كلمة المرور ',
            'user_type.required' => 'يرجى ادخال نوع المستخدم',
            'name.required' => 'يرجى ادخال الاسم',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, "حصل خطأ في المدخلات", $validator->errors(), []);
        }
        $data = [];
        $data = [
            'name' => $request['name'],
            'phone_number' => $request['phone_number'],
            'password' => bcrypt($request['password']),
            'user_type' => $request['user_type']
        ];

        $user = User::create($data);
        $token = $user->createToken($user->name)->accessToken;

        return $this->send_response(200, 'تم تسجيل الدخول بنجاح', [], User::find($user->id), $token);
    }
}
