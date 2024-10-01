<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemsTable extends Migration
{

    public function up()
    {
        Schema::create("items", function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->string('description')->nullable();
            $table->string('image_url')->nullable();
            $table->integer('quantity')->nullable();
            $table->timestamps();
        });

    }

    public function down()
    {
        Schema::dropIfExists('items');
    }
}
