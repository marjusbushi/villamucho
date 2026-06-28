<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuCategory extends Model
{
    protected $fillable = ['name', 'sort_order', 'outlet'];

    public function items()
    {
        return $this->hasMany(MenuItem::class);
    }
}
