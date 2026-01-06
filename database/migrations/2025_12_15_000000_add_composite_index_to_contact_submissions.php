<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds composite index on (webform_id, created_at) for efficient sorting
     * when filtering by webform_id. This prevents "Out of sort memory" errors.
     */
    public function up(): void
    {
        Schema::table('contact_submissions', function (Blueprint $table) {
            // Add composite index for webform_id + created_at queries
            // This allows efficient sorting when filtering by webform_id
            $table->index(['webform_id', 'created_at'], 'idx_webform_created');
            
            // Also add index on station + created_at for type-level queries
            $table->index(['station', 'created_at'], 'idx_station_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_submissions', function (Blueprint $table) {
            $table->dropIndex('idx_webform_created');
            $table->dropIndex('idx_station_created');
        });
    }
};

