<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrCreditCardPaymentLine extends Model
{
    protected $table      = "trCreditCardPaymentLine";
    protected $connection = 'sqlsrv';
    public $timestamps    = false;
}
