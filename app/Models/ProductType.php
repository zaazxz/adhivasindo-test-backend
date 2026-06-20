<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductType extends Model
{
    use HasUuids;

    protected $fillable = ['type_name'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'type_id', 'id');
    }
}
