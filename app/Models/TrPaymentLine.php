<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrPaymentLine extends Model
{
    protected $table      = "trPaymentLine";
    protected $connection = 'sqlsrv';
    public $timestamps    = false;
}
