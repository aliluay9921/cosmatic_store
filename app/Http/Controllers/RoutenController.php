<?php

namespace App\Http\Controllers;

use App\Models\Routen;
use App\Traits\Pagination;
use App\Traits\SendResponse;
use App\Traits\UploadImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class RoutenController extends Controller
{
    use SendResponse, Pagination, UploadImage;

    public function getRouten()
    {
        if (isset($_GET["routen_id"])) {
            $routens = Routen::find($_GET["routen_id"]);
            return $this->send_response(200, "تم جلب منتجات الأعلان", [], $routens->products);
        }
        $routens = Routen::with("products");
        if (isset($_GET['query'])) {
            $routens->where(function ($q) {
                $columns = Schema::getColumnListing('routens');
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
                    $routens->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($routens->orderBy("created_at", "DESC"),  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب الروتينات بنجاح ', [], $res["model"], null, $res["count"]);
    }

    public function addRouten(Request $request)
    {
        $request = request()->json()->all();
        $validator = Validator::make($request, [
            'image' => 'required',
            'name' => 'required',
            'products_id' => 'required',
            'products_id.*' => 'required|exists:products,id',
        ], [
            "image.required" => "يجب إدخال صورة الروتين",
            "name.required" => "يجب إدخال اسم الروتين",
            "products_id.*.exists" => " المنتج غير موجود",
            'products_id.required' => 'يجب إدخال المنتجات',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, "حصل خطأ في المدخلات", $validator->errors(), []);
        }
        $data = [];
        $data = [
            'name' => $request['name'],
            'image' => $this->uploadPicture($request['image'], '/images/routen_images/'),
        ];
        $routen = Routen::create($data);
        if (array_key_exists('products_id', $request)) {

            foreach ($request['products_id'] as $product_id) {
                $routen->products()->attach($product_id);
                error_log("" . $product_id);
            }
        }
        return $this->send_response(200, "تم اضافة الروتين بنجاح", [], Routen::find($routen->id));
    }

    public function deleteRouten($id)
    {
        $request = request()->json()->all();
        $validator = Validator::make($request, [
            'id' => 'required|exists:routens',
        ], [
            "id.required" => "يجب إدخال الروتين",
            "id.exists" => "الروتين غير موجود",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, "حصل خطأ في المدخلات", $validator->errors(), []);
        }
        $routens = Routen::find($request['id']);
        $routens->delete();
        return $this->send_response(200, "تم حذف الروتين بنجاح", [], []);
    }
}
