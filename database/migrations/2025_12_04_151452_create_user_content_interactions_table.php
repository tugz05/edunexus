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
        Schema::create('user_content_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('content_item_id')->constrained()->onDelete('cascade');
            $table->enum('action_type', ['viewed', 'saved', 'completed']);
            $table->timestamps();

            // Prevent duplicate interactions (user can only have one interaction per content item per action type)
            $table->unique(['user_id', 'content_item_id', 'action_type'], 'user_content_interaction_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_content_interactions');
    }
};
