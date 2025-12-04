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
        Schema::create('learning_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('grade_level')->nullable();
            $table->json('subjects')->nullable(); // Store as JSON array
            $table->enum('preferred_difficulty', ['Beginner', 'Intermediate', 'Advanced'])->nullable();
            $table->enum('learning_style', ['visual', 'reading', 'practice', 'mixed'])->nullable();
            $table->text('goals')->nullable();
            $table->timestamps();

            // Ensure one preference per user
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_preferences');
    }
};
