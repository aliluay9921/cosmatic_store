<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SettingController;
use App\Models\Setting;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


route::post("login", [AuthController::class, "login"]);
route::post("register", [AuthController::class, "register"]);


route::get("get_brands", [BrandController::class, "getBrands"]);
route::get("get_products", [ProductController::class, "getProducts"]);
route::get("get_ads", [AdsController::class, "getAds"]);
route::get("get_jomla_code", [SettingController::class, "getJomlaCode"]);


Route::middleware(['auth:api'])->group(function () {
    Route::middleware('admin')->group(function () {


        route::post("add_product", [ProductController::class, "addProduct"]);
        route::post("add_brand", [BrandController::class, "addBrand"]);
        route::post("add_ads", [AdsController::class, "addAds"]);
        route::post("add_code_jomla", [SettingController::class, "addCodeJomla"]);


        route::put("update_brand", [BrandController::class, "updateBrand"]);
        route::put("update_product", [ProductController::class, "updateProduct"]);
        route::put("update_ads", [AdsController::class, "updateAds"]);
        route::put("update_jomla_code", [SettingController::class, "updateJomlaCode"]);


        route::delete("delete_brand", [BrandController::class, "deleteBrand"]);
        route::delete("delete_product", [ProductController::class, "deleteProduct"]);
        route::delete("delete_ads", [AdsController::class, "deleteAds"]);
    });
});
