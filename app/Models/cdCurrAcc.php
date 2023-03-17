<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class cdCurrAcc extends Model
{
    protected $table      = "cdCurrAcc";
    protected $connection = 'sqlsrv';
    public $timestamps    = false;
}
