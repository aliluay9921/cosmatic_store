<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Traits\Pagination;
use App\Traits\UploadImage;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    use SendResponse, Pagination, UploadImage;

    public function getBrands()
    {
        $brands = Brand::select("*");

        if (isset($_GET['query'])) {
            $brands->where(function ($q) {
                $columns = Schema::getColumnListing('brands');
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
                    $brands->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($brands->orderBy("created_at", "DESC"),  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب الماركات بنجاح ', [], $res["model"], null, $res["count"]);
    }

    public function addBrand(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            'name' => 'required|unique:brands,name',
            'logo' => 'required',
        ], [
            "name.required" => "يجب إدخال اسم الماركة",
            "logo.required" => "يجب إدخال صورة الماركة",
            "name.unique" => "اسم الماركة موجود مسبقا",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, "حصل خطأ في المدخلات", $validator->errors(), []);
        }
        $data = [];
        $data = [
            'name' => $request['name'],
            'logo' => $this->uploadPicture($request['logo'], '/images/brand_logos/'),
        ];
        $brand = Brand::create($data);
        return $this->send_response(200, 'تم اضافة الماركة بنجاح', [], Brand::find($brand->id));
    }

    public function updateBrand(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            'id' => 'required|exists:brands,id',
            'name' => 'required|unique:brands,name,' . $request['id'],

        ], [
            "id.required" => "يجب إدخال رقم الماركة",
            "id.exists" => "الماركة غير موجودة",
            "name.required" => "يجب إدخال اسم الماركة",
            "name.unique" => "اسم الماركة موجود مسبقا",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, "حصل خطأ في المدخلات", $validator->errors(), []);
        }
        $brand = Brand::find($request['id']);
        $data = [];
        if (isset($request['logo'])) {
            $data['logo'] = $this->uploadPicture($request['logo'], '/images/brand_logos/');
        }
        $data['name'] = $request['name'];
        $brand->update($data);
        return $this->send_response(200, 'تم تعديل الماركة بنجاح', [], Brand::find($brand->id));
    }

    public function deleteBrand(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            'id' => 'required|exists:brands,id',
        ], [
            "id.required" => "يجب إدخال رقم الماركة",
            "id.exists" => "الماركة غير موجودة",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, "حصل خطأ في المدخلات", $validator->errors(), []);
        }
        $brand = Brand::find($request['id']);
        $brand->delete();
        return $this->send_response(200, 'تم حذف الماركة بنجاح', []);
    }
}
