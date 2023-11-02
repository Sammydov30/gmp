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
        Schema::create('logistics', function (Blueprint $table) {
            $table->id();
            $table->string('shipmenttype')->nullable()->comment('');
            $table->string('shipmentmode')->nullable();
            $table->string('gmpid')->nullable();
            $table->string('branch')->nullable();
            $table->string('rbranch')->nullable();
            $table->string('itemname')->nullable();
            $table->string('trackingid')->nullable();
            $table->string('orderid')->nullable();
            $table->string('pickuplocation')->nullable();
            $table->string('dropofflocation')->nullable();
            $table->string('anymoney')->nullable();
            $table->string('delivery_note')->nullable();
            $table->string('customer_number')->nullable();
            $table->string('package_destination')->nullable();
            $table->string('cname')->nullable();
            $table->string('cphone')->nullable();
            $table->string('caddress')->nullable();
            $table->string('rname')->nullable();
            $table->string('rphone')->nullable();
            $table->string('raddress')->nullable();
            $table->string('amount')->nullable();
            $table->string('shippingdate')->nullable();
            $table->string('collection_time')->nullable();
            $table->string('fromcountry')->nullable();
            $table->string('tocountry')->nullable();
            $table->string('fromregion')->nullable();
            $table->string('toregion')->nullable();
            $table->string('fromarea')->nullable();
            $table->string('toarea')->nullable();
            $table->string('totalweight')->nullable();
            $table->string('deliverytime')->nullable();
            $table->string('tripno')->nullable();
            $table->string('collectorphone')->nullable();
            $table->string('collectorname')->nullable();
            $table->string('timecollected')->nullable();
            $table->string('who')->nullable();
            $table->string('userguid')->nullable();
            $table->string('deleted')->default('0');
            $table->string('status')->default('0');
            $table->string('p_status')->nullable();
            $table->string('rider_id')->nullable();
            $table->string('d_status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics');
    }
};
