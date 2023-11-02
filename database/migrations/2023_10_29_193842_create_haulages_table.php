<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('haulages', function (Blueprint $table) {
            $table->id();
            $table->string('gmpid')->nullable();
            $table->string('orderid')->nullable();
            $table->string('trackingid')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('region')->nullable();
            $table->string('address')->nullable();
            $table->string('amount')->nullable();
            $table->longText('description')->nullable();
            $table->string('rdate')->nullable();
            $table->string('destination_region')->nullable();
            $table->string('weight')->nullable();
            $table->string('value_declaration')->nullable();
            $table->string('branch')->nullable();
            $table->string('user_guid')->nullable();
            $table->string('who')->nullable();
            $table->string('deleted')->default('0');
            $table->string('status')->default('1');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('haulages');
    }
};
