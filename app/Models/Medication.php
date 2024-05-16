<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'unit_price',
        'quantity'
    ];

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'description' => 'string',
            'unit_price' => 'integer',
            'quantity' => 'integer',
            'updated_at' => 'datetime:Y-m-d H:i:s',
            'created_at' => 'datetime:Y-m-d H:i:s'
        ];
    }
}
