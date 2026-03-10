<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_submission_id')
                ->constrained('contact_submissions')
                ->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('marked_at');
            $table->timestamps();

            $table->unique(['contact_submission_id', 'user_id']);
            $table->index('contact_submission_id');
            $table->index('user_id');
            $table->index('marked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_marks');
    }
};

