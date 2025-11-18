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
        Schema::create('coffees', function (Blueprint $table) {
            $table->id();
            $table->string('coffee_name');
            $table->string('image_url')->nullable();
            $table->text('description')->nullable();
            $table->string('ingredients')->nullable();
            $table->enum('coffee_type', ['strong', 'balanced', 'sweet']);
            $table->string('lactose')->default('no');
            $table->string('nuts')->default('no');
            $table->decimal('minimum_price', 5, 2);
            $table->decimal('maximum_price', 5, 2);
            $table->smallInteger('rating')->unsigned()->default(0);
            $table->integer('likes')->unsigned()->default(0);
            $table->integer('favorites')->unsigned()->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coffees');
    }
};
