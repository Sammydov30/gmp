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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('storeid')->nullable();
            $table->string('marketid')->nullable();
            $table->string('gmpid')->nullable();
            $table->string('name')->nullable();
            $table->string('category')->nullable();
            $table->string('amount')->nullable();
            $table->string('discountedamount')->nullable();
            $table->mediumText('descripton')->nullable();
            $table->string('inquirable')->default('0');
            $table->string('status')->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
