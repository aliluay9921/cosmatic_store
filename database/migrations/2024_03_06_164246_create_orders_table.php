<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->uuid("user_id");
            $table->string("address");
            $table->string("phone_number")->nullable();
            $table->double("total_cost");
            $table->integer("order_type"); // 0 = zain cash, 1 = on delivered
            $table->integer("status")->default(0); // 0 = pending, 1 = preperd, 2 = delivered , 3 =  rejected 
            $table->softDeletes();
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
        Schema::dropIfExists('orders');
    }
};