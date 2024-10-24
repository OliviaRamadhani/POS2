<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // Tambahkan UUID
            $table->string('name');
            $table->string('category');
            $table->double('price');
            // $table->integer('quantity');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_sold_out')->default(false); // Tambahkan kolom is_sold_out
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
