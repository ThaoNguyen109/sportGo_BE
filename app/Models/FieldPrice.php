<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldPrice extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'field_id','start_time','end_time','price'
    ];
}
