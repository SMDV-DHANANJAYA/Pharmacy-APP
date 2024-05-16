<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $with = ['prescriptions'];

    protected $fillable = [
        'name',
        'nic',
        'age',
        'mobile',
        'address'
    ];

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'nic' => 'string',
            'age' => 'integer',
            'mobile' => 'string',
            'address' => 'string',
            'updated_at' => 'datetime:Y-m-d H:i:s',
            'created_at' => 'datetime:Y-m-d H:i:s'
        ];
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }
}
