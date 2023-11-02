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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('gmpid')->nullable()->unique();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('othername')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('verifytoken')->nullable()->unique();
            $table->string('email_verified_at')->nullable();
            $table->string('is_verified')->default('0');
            $table->string('loginid')->nullable();
            $table->string('phone')->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('profilepicture')->nullable();
            $table->string('token')->nullable();
            $table->string('seller')->default('0');
            $table->string('sellerid')->nullable();
            $table->string('otp')->nullable();
            $table->string('otpexpiration')->nullable();
            $table->string('address')->nullable();
            $table->string('lga')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('dob')->nullable();
            $table->string('city')->nullable();
            $table->string('employmentstatus')->nullable();
            $table->string('gender')->nullable();
            $table->string('status')->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
