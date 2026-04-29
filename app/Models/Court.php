<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Court extends Model
{
    protected $fillable = [
        'owner_id','name','address','latitude','longitude',
        'phone','image','description','open_time','close_time',
        'status','is_active'
    ];

    public function fields()
    {
        return $this->hasMany(Field::class);
    }

    public function images()
    {
        return $this->hasMany(CourtImage::class);
    }
}