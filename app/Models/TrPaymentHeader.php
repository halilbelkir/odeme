<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrPaymentHeader extends Model
{
    protected $table      = "trPaymentHeader";
    protected $connection = 'sqlsrv';
    public $timestamps    = false;
}
