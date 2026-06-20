<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasUuids;

    protected $fillable = [
        'type_id',
        'name',
        'desc',
        'price',
        'stock',
        'image',
        'status'
    ];

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function type()
    {
        return $this->belongsTo(ProductType::class, 'type_id', 'id');
    }
}
