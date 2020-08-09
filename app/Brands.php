<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Brands extends Model
{
    protected $fillable = [
        'brand'
    ];

    //
    public function carModels()
    {
        return $this->belongsToMany(Cars::class, 'cars');
    }
}
