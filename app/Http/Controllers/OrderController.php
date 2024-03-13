<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Governante;
use App\Traits\Pagination;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    use SendResponse, Pagination;

    public function getAuthOrder()
    {
        $orders = Order::with("products", "user")->where("user_id", auth()->user()->id);
        if (isset($_GET['filter'])) {
            $filter = json_decode($_GET['filter']);
            $orders->where($filter->name, $filter->value);
        }
        if (isset($_GET['query'])) {
            $orders->where(function ($q) {
                $columns = Schema::getColumnListing('orders');
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
                    $orders->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($orders->orderBy("created_at", "DESC"),  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب الطلبات بنجاح ', [], $res["model"], null, $res["count"]);
    }

    public function getAllOrders()
    {
        $orders = Order::with('products', "user");
        if (isset($_GET['filter'])) {
            $filter = json_decode($_GET['filter']);
            $orders->where($filter->name, $filter->value);
        }
        if (isset($_GET['query'])) {
            $orders->where(function ($q) {
                $columns = Schema::getColumnListing('orders');
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
                    $orders->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($orders->orderBy("created_at", "DESC"),  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب الطلبات بنجاح ', [], $res["model"], null, $res["count"]);
    }

    public function addOrder(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make(
            $request,
            [
                'address' => 'required',
                // 'phone_number' => 'required',
                'products.*.id' => 'required|exists:products,id',
                'products.*.quantity' => 'required|numeric',
            ],
            [
                'products.*.id.required' => 'يجب اختيار المنتج',
                'products.*.quantity.required' => 'يجب ادخال عدد المنتج',
                'products.*.id.exists' => 'قمت بأختيار منتج غير موجود',
                'address.required' => 'يجب ادخال العنوان',
                // 'phone_number.required' => 'يجب ادخال رقم الهاتف',
            ]
        );
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ في البيانات المرسلة', $validator->errors());
        }

        $total_cost = 0;
        $data = [];
        $data = [
            "user_id" => auth()->user()->id,
            "address" => $request["address"],
            "phone_number" => $request["phone_number"] ?? auth()->user()->phone_number,
            "order_type" => $request["order_type"],
            "governante_id" => $request["governante_id"]
        ];

        foreach ($request["products"] as $__product) {
            $product = Product::find($__product["id"]);
            $get_product_price = auth()->user()->user_type == 1 ? $product->single_price : $product->jomla_price;
            if ($product->stock >= $__product["quantity"]) {
                $product->update([
                    'stock' => $product->stock - $__product["quantity"]
                ]);
            } else {
                return $this->send_response(400, 'الكمية المطلوبة غير متوفرة', [], null, null, null);
            }
            if ($product->offer != null) {
                $offer = $product->offer * 1 / 100;
                $product_price = $get_product_price - ($offer * $get_product_price);
                $total_cost += $product_price * $__product["quantity"];
            } else {
                $total_cost += $get_product_price * $__product["quantity"];
            }
        }
        $data["total_cost"] = $total_cost; //if not has coupon
        if ($request["order_type"] == 0) {
            $data["total_cost"] = $total_cost - ($total_cost * 0.05); //5% discount for cash payment
        }

        $Governantes = Governante::find($request["governante_id"]);
        $data["total_cost"] += $Governantes->price; //add price of governate to total cost
        $order = Order::create($data);
        foreach ($request["products"] as $__product) {
            //  to save another data in paviot table 
            $current_product = Product::find($__product["id"]);
            $order->products()->attach($__product["id"], ["offer" => $current_product->offer, "quantity" => $__product["quantity"], "price" => auth()->user()->user_type == 1 ? $current_product->single_price : $current_product->jomla_price]);
        }
        return $this->send_response(200, 'تم اضافة الطلب بنجاح', [], Order::with("products", "user")->find($order->id));
    }

    public function changeStatusOrder(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make(
            $request,
            [
                'order_id' => 'required|exists:orders,id',
                'status' => 'required',
            ],
            [
                'order_id.required' => 'يجب ادخال رقم الطلب',
                'order_id.exists' => 'رقم الطلب غير موجود',
                'status.required' => 'يجب ادخال حالة الطلب',
            ]
        );
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ في البيانات المرسلة', $validator->errors());
        }
        $order = Order::find($request["order_id"]);
        if ($order->status == 0) {
            if ($order->user_id === auth()->user()->id) {
                $order = Order::where("id", $request["order_id"])->update(["status" => $request["status"]]); // to reject order by  user order
            } else if (auth()->user()->user_type == 0) {
                $order = Order::where("id", $request["order_id"])->update(["status" => $request["status"]]); // to reject or prepared order by admin 
            } else {
                return $this->send_response(400, 'لا يمكنك تغيير حالة طلب غير طلبك', [], null, null, null);
            }
        } else if ($order->status == 1) {
            if (auth()->user()->user_type == 0) {
                $order = Order::where("id", $request["order_id"])->update(["status" => $request["status"]]); // to confirm delevird order by admin
            } else {
                return $this->send_response(400, 'لا يمكنك تغيير حالة طلب آخر', [], null, null, null);
            }
        }
        return $this->send_response(200, 'تم تغيير حالة الطلب بنجاح', [], Order::with("products", "user")->find($request["order_id"]));
    }
}
