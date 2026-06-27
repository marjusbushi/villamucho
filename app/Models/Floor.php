<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Floor extends Model
{
    protected $fillable = ['number', 'name'];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
        ];
    }
}
