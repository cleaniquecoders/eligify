<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eligify_criteriables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criteria_id')->constrained('eligify_criteria')->onDelete('cascade');
            $table->string('criteriable_type');
            $table->unsignedBigInteger('criteriable_id');
            $table->timestamps();

            $table->unique(['criteria_id', 'criteriable_type', 'criteriable_id'], 'eligify_criteriables_unique');
            $table->index(['criteriable_type', 'criteriable_id'], 'eligify_criteriables_morph_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eligify_criteriables');
    }
};
