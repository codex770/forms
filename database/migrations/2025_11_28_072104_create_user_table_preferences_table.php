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
        Schema::create('user_table_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('category')->nullable(); // null = global, or specific form category
            $table->string('preference_name'); // e.g., 'default', 'my-custom-view'
            $table->json('visible_columns')->nullable(); // ['name', 'email', 'created_at']
            $table->json('sort_config')->nullable(); // {column: 'created_at', direction: 'desc'}
            $table->json('saved_filters')->nullable(); // {date_from: '2024-01-01', gender: 'm'}
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            
            // Ensure unique combination of user, category, and preference name
            $table->unique(['user_id', 'category', 'preference_name']);
            
            // Add indexes for better performance
            $table->index('user_id');
            $table->index('category');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_table_preferences');
    }
};
