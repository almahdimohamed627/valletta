<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_category_pivot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_category_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Ensure unique combination
            $table->unique(['product_id', 'product_category_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_category_pivot');
    }
};