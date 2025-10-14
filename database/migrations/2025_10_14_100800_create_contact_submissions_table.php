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
        Schema::create('contact_submissions', function (Blueprint $table) {
            $table->id();
            $table->enum('category', ['bigfm', 'rpr1', 'regenbogen', 'rockfm', 'bigkarriere']);
            $table->json('data'); // Store all form data as JSON
            $table->string('ip_address')->nullable();
            $table->timestamps();
            
            // Add indexes for better performance
            $table->index('category');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_submissions');
    }
};
