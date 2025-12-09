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
        Schema::create('preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('coffee_type', ['arabica', 'robusta', 'liberica'])->nullable();
            $table->unsignedInteger('coffee_allowance')->nullable();
            $table->enum('serving_temp', ['hot', 'iced', 'both'])->nullable();
            $table->boolean('lactose')->default(false);
            $table->boolean('nuts_allergy')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->unique('user_id'); 

            $table->index('coffee_type');
            $table->index('coffee_allowance');
            $table->index('serving_temp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preferences');
    }
};
