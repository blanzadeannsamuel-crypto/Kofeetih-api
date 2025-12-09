<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('must_try_coffee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('coffee_id');
            $table->foreign('coffee_id')->references('coffee_id')->on('coffees')->onDelete('cascade');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'coffee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('must_try_coffee');
    }
};
