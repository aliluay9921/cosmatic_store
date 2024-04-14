<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Traits\Pagination;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    use SendResponse, Pagination;


    public function getJomlaCode()
    {
        $setting = Setting::first();
        return $this->send_response(200, "تم جلب الكود بنجاح", [], $setting);
    }

    public function addCodeJomla(Request $request)
    {
        $request = request()->json()->all();
        $validator = Validator::make($request, [
            'jomla_code' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, "البيانات المدخلة غير صحيحة", $validator->errors());
        }
        $get_data = Setting::first();
        if ($get_data) {
            $get_data->update($request);
            return $this->send_response(200, "تم تحديث الكود بنجاح", [], $get_data);
        } else {
            $setting = Setting::create($request);
            return $this->send_response(200, "تم اضافة الكود بنجاح", [], $setting);
        }
    }

    public function updateJomlaCode(Request $request)
    {
        $request = request()->json()->all();
        $validator = Validator::make($request, [
            'jomla_code' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, "البيانات المدخلة غير صحيحة", $validator->errors());
        }
        $setting = Setting::first();
        $setting->update($request);
        return $this->send_response(200, "تم تحديث الكود بنجاح", [], $setting);
    }
}
