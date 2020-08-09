<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cars extends Model
{

    protected $casts = [
        'available' => 'boolean',
    ];

    protected $fillable = [
        'model','price','available'
    ];
    //
    public function classes()
    {
        return $this->belongsToMany(Classes::class, 'car_classes');
    }

    public function brand()
    {
        return $this->belongsToMany(Brands::class, 'car_brands','car_id','brand_id');
    }

    public function historyLogs(){
        return $this->hasMany(HistoryLog::class);
    }


}
