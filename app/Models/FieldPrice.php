<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldPrice extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
    'field_id',
    'start_time',
    'end_time',
    'price',
    'day_of_week',
    'is_active'
];
}