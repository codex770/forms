<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_column_configs', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 255)->unique();
            $table->json('visible_columns')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_column_configs');
    }
};
