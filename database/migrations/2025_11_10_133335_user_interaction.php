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
        Schema::create('coffee_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('coffee_id')->constrained('coffees')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['user_id', 'coffee_id']); // prevents multiple likes by same user
        });

        Schema::create('coffee_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('coffee_id')->constrained('coffees')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'coffee_id']); // prevents multiple favorites
        });
        Schema::create('coffee_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('coffee_id')->constrained('coffees')->onDelete('cascade');
            $table->tinyInteger('rating'); // 1-5
            $table->timestamps();

            $table->unique(['user_id', 'coffee_id']); // 1 rating per user per coffee
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coffee_user_likes');
        Schema::dropIfExists('coffee_user_favorites');
        Schema::dropIfExists('coffee_ratings');
    }
};
