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
            $table->string('coffee_type')->nullable();
            $table->unsignedInteger('coffee_allowance')->nullable();
            $table->enum('temp', ['hot', 'cold'])->nullable();
            $table->boolean('lactose')->default(false);
            $table->boolean('nuts_allergy')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->unique('user_id'); // ensures only one preference per user
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
