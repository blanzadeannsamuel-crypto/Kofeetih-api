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
            $table->id('coffee_id');
            $table->string('coffee_name');
            $table->string('coffee_image')->nullable();
            $table->text('description')->nullable();
            $table->string('ingredients')->nullable();
            $table->enum('coffee_type', ['arabica', 'robusta', 'liberica']);
            $table->enum('serving_temp', ['hot', 'cold', 'both'])->default('hot');
            $table->boolean('lactose')->default(false);
            $table->boolean('nuts')->default(false);
            $table->decimal('price', 5, 2)->default(0.00);
           $table->float('rating', 3, 2)->default(0.00);
            $table->integer('likes')->unsigned()->default(0);
            $table->integer('favorites')->unsigned()->default(0);
            $table->timestamps();


            $table->index('coffee_name');
            $table->index('coffee_type');
            $table->index('rating');
            $table->index('likes');
            $table->index('favorites');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('coffees');
    }
};
