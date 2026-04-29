<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourtImage extends Model
{
    protected $fillable = [
        'court_id',
        'image_url'
    ];

    public function court()
    {
        return $this->belongsTo(Court::class);
    }
}