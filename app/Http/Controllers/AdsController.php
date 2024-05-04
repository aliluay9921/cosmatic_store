<?php

namespace App\Http\Controllers;

use App\Models\Ads;
use App\Traits\Pagination;
use App\Traits\UploadImage;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class adsController extends Controller
{
    use SendResponse, Pagination, UploadImage;

    public function getAds()
    {
        if (isset($_GET["ads_id"])) {
            $ads = Ads::find($_GET["ads_id"]);
            return $this->send_response(200, "تم جلب منتجات الأعلان", [], $ads->products);
        }
        $ads = Ads::with("products");
        if (isset($_GET['query'])) {
            $ads->where(function ($q) {
                $columns = Schema::getColumnListing('ads');
                foreach ($columns as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $_GET['query'] . '%');
                }
            });
        }
        if (isset($_GET)) {
            foreach ($_GET as $key => $value) {
                if ($key == 'skip' || $key == 'limit' || $key == 'query') {
                    continue;
                } else {
                    $sort = $value == 'true' ? 'desc' : 'asc';
                    $ads->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($ads->orderBy("created_at", "DESC"),  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب الاعلانات بنجاح ', [], $res["model"], null, $res["count"]);
    }



    public function addAds(Request $request)
    {
        $request = request()->json()->all();
        $validator = Validator::make($request, [
            'type' => 'required',
            'image' => 'required',
            'url' => 'nullable|url',
            'expaired' => 'required|date|after:today',
            'products_id' => $request['type'] == 1 ? 'required' : 'nullable',
            'products_id.*' => 'required|exists:products,id',
        ], [
            "type.required" => "يجب إدخال نوع الإعلان",
            "image.required" => "يجب إدخال صورة الإعلان",
            "url.url" => "يجب أن يكون الرابط صحيح",
            "expaired.required" => "يجب إدخال تاريخ انتهاء الإعلان",
            "expaired.date" => "يجب أن يكون تاريخ انتهاء الإعلان تاريخ",
            "expaired.after" => "يجب أن يكون تاريخ انتهاء الإعلان بعد اليوم",
            "products_id.*.required" => "يجب إدخال  المنتج",
            "products_id.*.exists" => " المنتج غير موجود",
            'products_id.required' => 'يجب إدخال المنتجات',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, "حصل خطأ في المدخلات", $validator->errors(), []);
        }
        $data = [];
        $data = [
            'type' => $request['type'],
            'image' => $this->uploadPicture($request['image'], '/images/ads_images/'),
            'url' => $request['url'] ?? null,
            'expaired' => $request['expaired'],
        ];
        $ads = Ads::create($data);
        if (array_key_exists('products_id', $request)) {

            foreach ($request['products_id'] as $product_id) {
                $ads->products()->attach($product_id);
                error_log("" . $product_id);
            }
        }
        return $this->send_response(200, "تم اضافة الإعلان بنجاح", [], Ads::find($ads->id));
    }

    public function updateAds(Request $request)
    {
        $request = request()->json()->all();
        $validator = Validator::make($request, [
            'id' => 'required|exists:ads',
            'type' => 'required',
            'image' => 'nullable',
            'url' => 'nullable|url',
            'expaired' => 'required|date|after:today',
            'products_id' => $request['type'] == 1 ? 'required' : 'nullable',
            'products_id.*' => 'required|exists:products,id',
        ], [
            "id.required" => "يجب إدخال الإعلان",
            "id.exists" => "الإعلان غير موجود",
            "type.required" => "يجب إدخال نوع الإعلان",
            "image.nullable" => "يجب إدخال صورة الإعلان",
            "url.url" => "يجب أن يكون الرابط صحيح",
            "expaired.required" => "يجب إدخال تاريخ انتهاء الإعلان",
            "expaired.date" => "يجب أن يكون تاريخ انتهاء الإعلان تاريخ",
            "expaired.after" => "يجب أن يكون تاريخ انتهاء الإعلان بعد اليوم",
            "products_id.*.required" => "يجب إدخال  المنتج",
            "products_id.*.exists" => " المنتج غير موجود",
            'products_id.required' => 'يجب إدخال المنتجات',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, "حصل خطأ في المدخلات", $validator->errors(), []);
        }
        $ads = Ads::find($request['id']);
        $data = [];
        $data = [
            'type' => $request['type'],

            'url' => $request['url'] ?? $ads->url,
            'expaired' => $request['expaired'],
        ];
        if (array_key_exists("image", $request)) {
            $data["image"] = $this->uploadPicture($request['image'], '/images/ads_images/');
        }
        $ads->update($data);
        $ads->products()->detach();
        if (array_key_exists('products_id', $request)) {
            foreach ($request['products_id'] as $product_id) {
                $ads->products()->attach($product_id);
                error_log("" . $product_id);
            }
        }
        return $this->send_response(200, "تم تعديل الإعلان بنجاح", [], Ads::with("products")->find($ads->id));
    }

    public function deleteAds(Request $request)
    {
        $request = request()->json()->all();
        $validator = Validator::make($request, [
            'id' => 'required|exists:ads',
        ], [
            "id.required" => "يجب إدخال الإعلان",
            "id.exists" => "الإعلان غير موجود",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, "حصل خطأ في المدخلات", $validator->errors(), []);
        }
        $ads = Ads::find($request['id']);
        $ads->products()->detach();
        $ads->delete();
        return $this->send_response(200, "تم حذف الإعلان بنجاح", [], []);
    }
}
