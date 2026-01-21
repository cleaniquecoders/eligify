<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eligify_criteria_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->index();
            $table->foreignId('criteria_id')->constrained('eligify_criteria')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->text('description')->nullable();
            $table->json('rules_snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['criteria_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eligify_criteria_versions');
    }
};
