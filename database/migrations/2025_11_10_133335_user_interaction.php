<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coffee_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('coffee_id');
            $table->foreign('coffee_id')->references('coffee_id')->on('coffees')->onDelete('cascade'); 
            $table->timestamps();
            $table->unique(['user_id', 'coffee_id']); 

            $table->index('coffee_id');
            $table->index('user_id');
        });

        Schema::create('coffee_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('coffee_id');
            $table->foreign('coffee_id')->references('coffee_id')->on('coffees')->onDelete('cascade'); 
            $table->timestamps();
            $table->unique(['user_id', 'coffee_id']); 

            $table->index('coffee_id');
            $table->index('user_id');
        });

        Schema::create('coffee_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('coffee_id');
            $table->foreign('coffee_id')->references('coffee_id')->on('coffees')->onDelete('cascade'); 
            $table->integer('rating')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'coffee_id']);

            $table->index('coffee_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coffee_likes');
        Schema::dropIfExists('coffee_favorites');
        Schema::dropIfExists('coffee_ratings');
    }
};
