<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Extend the enum to support additional Office document types
        DB::statement("
            ALTER TABLE content_items
            MODIFY COLUMN type ENUM('video', 'pdf', 'link', 'quiz', 'document', 'presentation', 'spreadsheet')
            NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to the original enum values
        DB::statement("
            ALTER TABLE content_items
            MODIFY COLUMN type ENUM('video', 'pdf', 'link', 'quiz')
            NOT NULL
        ");
    }
};

