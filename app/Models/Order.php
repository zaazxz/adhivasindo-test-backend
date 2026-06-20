<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\OrderDetail;
use App\Models\User;

class Order extends Model
{
    use HasUuids;

    protected $guarded = [];
    public $timestamps = false;

    protected $casts = [
        'total_price' => 'decimal:2',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function orderDetails() {
        return $this->hasMany(OrderDetail::class);
    }
}
