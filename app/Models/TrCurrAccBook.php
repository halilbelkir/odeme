<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrCurrAccBook extends Model
{
    protected $table      = "trCurrAccBook";
    protected $connection = 'sqlsrv';
    public $timestamps    = false;
}
