<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Image;
use App\Models\Product;
use App\Traits\Pagination;
use App\Traits\UploadImage;
use App\Traits\SendResponse;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    use SendResponse, Pagination, UploadImage;
    public function getProducts()
    {
        if (isset($_GET["brand_id"])) {
            $products = Product::where('brand_id', $_GET["brand_id"]);
        } else {
            $products = Product::select("*");
            // check if the offer is expired
            foreach ($products->get() as $product) {
                if ($product->offer_expired < Carbon::now()->format('Y-m-d')) {
                    $product->update([
                        "offer" => 0,
                        "offer_expired" => null
                    ]);
                }
            }
        }
        if (isset($_GET['filter'])) {

            $filter = json_decode($_GET['filter']);
            if ($filter->name == "offer" && $filter->value == "true") {

                $products->where('offer', '>', 0);
            } elseif ($filter->name == "brand") {
                $products->whereHas('brand', function ($q) use ($filter) {
                    $q->where('name', $filter->value);
                });
            } else {
                $products->where($filter->name, $filter->value);
            }
        }

        if (isset($_GET['query'])) {
            $products->where(function ($q) {
                $columns = Schema::getColumnListing('products');
                $q->whereHas('brand', function ($q) {
                    $q->where('name', 'LIKE', '%' . $_GET['query'] . '%');
                });
                foreach ($columns as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $_GET['query'] . '%');
                }
            });
        }
        if (isset($_GET)) {
            foreach ($_GET as $key => $value) {
                if ($key == 'skip' || $key == 'limit' || $key == 'query' || $key == 'filter') {
                    continue;
                } else {
                    $sort = $value == 'true' ? 'desc' : 'asc';
                    $products->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($products->orderBy("created_at", "DESC"),  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب المنتجات بنجاح ', [], $res["model"], null, $res["count"]);
    }
    public function getProductById()
    {
        $result = [];
        $product = Product::find($_GET['id']);
        $products = Product::where("brand_id", $product->brand_id)->where("category_id", $product->category_id)->take(5)->get();
        $result["product"] = $product;
        $result["products"] = $products;
        if ($product) {
            return $this->send_response(200, 'تم جلب المنتج بنجاح ', [], $result);
        } else {
            return $this->send_response(400, 'لا يوجد منتج بهذا الرقم', [], []);
        }
    }
    public function addProduct(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "name_ar" => "required|string|max:255",
            "name_en" => "required|string|max:255",
            "description" => "required|string|max:255",
            "single_price" => "required|numeric",
            "jomla_price" => "required|numeric",
            "brand_id" => "required|exists:brands,id",
            "images" => "required",
            "offer" => "numeric|max:100",
            "offer_expired" => "required_with:offer|date|after_or_equal:today",
            "stock" => "required",
        ], [
            "name_ar.required" => "يجب إدخال اسم المنتج بالعربية",
            "name_en.required" => "يجب إدخال اسم المنتج بالانجليزية",
            "description.required" => "يجب إدخال وصف المنتج",
            "single_price.required" => "يجب إدخال سعر المنتج للمستخدم العادي",
            "jomla_price.required" => "يجب إدخال سعر المنتج للجملة",
            "brand_id.required" => "يجب إدخال صنف المنتج",
            "images.required" => "يجب إدخال صور المنتج",
            "offer.max" => "يجب ألا يتجاوز العرض 100",
            "offer_expired.required_with" => "يجب إدخال تاريخ انتهاء العرض",
            "stock.required" => "يجب ادخال الكمية المتوفرة"
        ]);

        if ($validator->fails()) {
            return $this->send_response(400, "حصل خطأ في المدخلات", $validator->errors(), []);
        }

        $data = [];
        $data = [
            "name_ar" => $request["name_ar"],
            "name_en" => $request["name_en"],
            "description" => $request["description"],
            "single_price" => $request["single_price"],
            "jomla_price" => $request["jomla_price"],
            "brand_id" => $request["brand_id"],
            "offer" => $request["offer"] ?? null,
            "offer_expired" => $request["offer_expired"] ?? null,
            "stock" => $request["stock"],
        ];
        $product = Product::create($data);

        foreach ($request["images"] as $image) {
            Image::create([
                "product_id" => $product->id,
                "image" => $this->uploadPicture($image, '/images/product_images'),
            ]);
        }

        return $this->send_response(200, 'تم إضافة المنتج بنجاح', [], Product::find($product->id));
    }

    public function updateProduct(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "id" => "required|exists:products,id",
            "name_ar" => "required|string|max:255",
            "name_en" => "required|string|max:255",
            "description" => "required|string|max:255",
            "single_price" => "required|numeric",
            "jomla_price" => "required|numeric",
            "brand_id" => "required|exists:brands,id",
            "offer" => "numeric|max:100",
            "stock" => "required",
        ], [
            "id.required" => "يجب إدخال رقم المنتج",
            "id.exists" => "المنتج غير موجود",
            "name_ar.required" => "يجب إدخال اسم المنتج بالعربية",
            "name_en.required" => "يجب إدخال اسم المنتج بالانجليزية",
            "description.required" => "يجب إدخال وصف المنتج",
            "single_price.required" => "يجب إدخال سعر المنتج للمستخدم العادي",
            "jomla_price.required" => "يجب إدخال سعر المنتج للجملة",
            "brand_id.required" => "يجب إدخال صنف المنتج",
            "offer.max" => "يجب ألا يتجاوز العرض 100",
            "stock.required" => "يجب ادخال الكمية المتوفرة"
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, "حصل خطأ في المدخلات", $validator->errors(), []);
        }

        $data = [];
        $product = Product::find($request["id"]);
        $data = [

            "name_ar" => $request["name_ar"],
            "name_en" => $request["name_en"],
            "description" => $request["description"],
            "single_price" => $request["single_price"],
            "jomla_price" => $request["jomla_price"],
            "brand_id" => $request["brand_id"],
            "offer" => $request["offer"] ?? null,
            "offer_expired" => $request["offer_expired"] ?? $product->offer_expired,
            "stock" => $request["stock"],
        ];

        if (array_key_exists("offer", $request) && $request["offer"] == null  || $product->offer > 0) {
            $data["offer_expired"] = null;
        }
        if (array_key_exists("images", $request)) {
            foreach ($request["images"] as $image) {
                Image::create([
                    "product_id" => $product->id,
                    "image" => $this->uploadPicture($image, '/images/product_images'),
                ]);
            }
        }
        $product->update($data);
        return $this->send_response(200, 'تم تعديل المنتج بنجاح', [], Product::find($product->id));
    }
    public function deleteProduct(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "id" => "required|exists:products,id"
        ], [
            "id.required" => "يجب إدخال رقم المنتج",
            "id.exists" => "المنتج غير موجود"
        ]);

        if ($validator->fails()) {
            return $this->send_response(400, "حصل خطأ في المدخلات", $validator->errors(), []);
        }
        $product = Product::find($request['id']);
        if ($product) {
            $product->delete();
            return $this->send_response(200, 'تم حذف المنتج بنجاح', [], []);
        } else {
            return $this->send_response(400, 'لا يوجد منتج بهذا الرقم', [], []);
        }
    }
}
