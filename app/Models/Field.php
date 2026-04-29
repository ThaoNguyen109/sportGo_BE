<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Field extends Model
{
    protected $fillable = ['court_id','name','is_active'];

    public function prices()
    {
        return $this->hasMany(FieldPrice::class);
    }
}
