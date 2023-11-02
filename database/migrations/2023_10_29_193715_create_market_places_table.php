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
        Schema::create('market_places', function (Blueprint $table) {
            $table->id();
            $table->string('marketid')->nullable();
            $table->string('name')->nullable();
            $table->string('location')->nullable();
            $table->string('state')->nullable();
            $table->string('region')->nullable();
            $table->string('open')->default('0');
            $table->string('status')->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_places');
    }
};
