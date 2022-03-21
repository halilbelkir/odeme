<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PayResult extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pay_result', function (Blueprint $table) {
            $table->id();
            $table->string('name_surname')->nullable();
            $table->string('card_number')->nullable();
            $table->string('response_code');
            $table->string('error_message')->nullable();
            $table->string('error_message_title')->nullable();
            $table->string('error_message_detail')->nullable();
            $table->string('hash_data')->nullable();
            $table->string('amount')->nullable();
            $table->string('reference_no')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('md_status')->nullable();
            $table->ipAddress('ip_address');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
