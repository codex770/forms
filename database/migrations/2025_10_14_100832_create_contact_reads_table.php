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
        Schema::create('contact_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_submission_id')->constrained('contact_submissions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('read_at');
            $table->timestamps();
            
            // Ensure unique combination of user and submission (one read per user per submission)
            $table->unique(['contact_submission_id', 'user_id']);
            
            // Add indexes for better performance
            $table->index('contact_submission_id');
            $table->index('user_id');
            $table->index('read_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_reads');
    }
};
