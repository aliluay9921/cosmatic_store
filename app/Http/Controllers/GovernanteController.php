<?php

namespace App\Http\Controllers;

use App\Models\Governante;
use App\Traits\Pagination;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class GovernanteController extends Controller
{
    use SendResponse, Pagination;

    public function getGovernantes()
    {
        $brands = Governante::select("*");

        if (isset($_GET['query'])) {
            $brands->where(function ($q) {
                $columns = Schema::getColumnListing('governantes');
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
        return $this->send_response(200, 'تم جلب المحافظات بنجاح ', [], $res["model"], null, $res["count"]);
    }

    public function addGovernante(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            'name' => 'required',
            'price' => 'required',
        ], [
            "name.required" => "يجب إدخال اسم المحافظة",
            "price.required" => "يجب أدخال السعر",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, "حصل خطأ في المدخلات", $validator->errors(), []);
        }
        $data = [];
        $data = [
            'name' => $request['name'],
            'price' => $request['price'],
        ];
        $governante = Governante::create($data);
        return $this->send_response(200, 'تم اضافة المحافظة بنجاح', [], Governante::find($governante->id));
    }

    public function updateGovernante(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            'id' => 'required|exists:governantes,id',
            'name' => 'required|unique:governantes,name,' . $request['id'],

        ], [
            "id.required" => "يجب إدخال رقم المحافظة",
            "id.exists" => "المحافظة غير موجودة",
            "name.required" => "يجب إدخال اسم المحافظة",
            "price.required" => "يجب أدخال السعر",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, "حصل خطأ في المدخلات", $validator->errors(), []);
        }
        $governante = Governante::find($request['id']);
        $data = [];

        $data['name'] = $request['name'];
        $data['price'] = $request['price'];
        $governante->update($data);
        return $this->send_response(200, 'تم تعديل المحافظة بنجاح', [], Governante::find($governante->id));
    }
}