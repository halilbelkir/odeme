<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrPaymentLineCurrency extends Model
{
    use HasFactory;
    protected $table = "trPaymentLineCurrency";
    public $timestamps = false;
}
