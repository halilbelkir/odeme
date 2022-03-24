<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayResult extends Model
{
    use HasFactory;
    protected $table = "pay_result";

    public function user()
    {
        return $this->belongsTo(User::class,'id');
    }
}
