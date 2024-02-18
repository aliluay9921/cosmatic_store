<?php

namespace App\Models;

use App\Traits\Uuids;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ads extends Model
{
    use HasFactory, Uuids, SoftDeletes;
    protected $table = 'ads';
    protected $guarded = [];
    protected $dates = ['deleted_at'];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'ads_products', 'ads_id', 'product_id');
    }
}
