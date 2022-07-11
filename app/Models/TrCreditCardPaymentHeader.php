<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrCreditCardPaymentHeader extends Model
{
    protected $table      = "trCreditCardPaymentHeader";
    protected $connection = 'sqlsrv';
    public $timestamps    = false;

}
