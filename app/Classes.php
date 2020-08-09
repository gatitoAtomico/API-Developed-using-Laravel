<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    //
    public function carModels()
    {
        return $this->belongsToMany(Cars::class, 'cars');
    }
}
