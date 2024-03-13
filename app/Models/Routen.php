<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Routen extends Model
{
    use HasFactory, Uuids;
    protected $guarded = [];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'routen_products', 'routen_id', 'product_id');
    }
}
