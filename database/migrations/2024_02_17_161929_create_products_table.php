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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('description')->nullable();
            $table->double('single_price');
            $table->double('jomla_price');
            $table->string('stock');
            $table->double("offer")->nullable();
            $table->date("offer_expired")->nullable();
            $table->uuid('brand_id');
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
        Schema::dropIfExists('products');
    }
};
