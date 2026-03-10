<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_retention_rules', function (Blueprint $table) {
            $table->id();
            // NULL = global default rule; a value = rule specific to that webform
            $table->string('webform_id')->nullable()->unique();
            // NULL = keep forever; a positive integer = delete after N days
            $table->unsignedInteger('retention_days')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_retention_rules');
    }
};
