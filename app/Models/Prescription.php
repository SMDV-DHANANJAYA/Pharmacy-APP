<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prescription extends Model
{
    use HasFactory, SoftDeletes;

    protected $with = ['prescription_details'];

    protected $fillable = [
        'customer_id',
        'note',
        'total_amount'
    ];

    protected function casts(): array
    {
        return [
            'customer_id' => 'integer',
            'note' => 'string',
            'total_amount' => 'float',
            'updated_at' => 'datetime:Y-m-d H:i:s',
            'created_at' => 'datetime:Y-m-d H:i:s'
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function prescription_details(): HasMany
    {
        return $this->hasMany(PrescriptionDetails::class);
    }
}
