<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Actions extends Model
{
    //
    public function historyLogs(){
        return $this->hasMany(HistoryLog::class);
    }
}
