<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eligify_criteria', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->index();
            $table->string('name');
            $table->string('slug')->nullable()->index();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('eligify_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->index();
            $table->foreignId('criteria_id')->constrained('eligify_criteria')->cascadeOnDelete();
            $table->string('field');
            $table->string('operator');
            $table->json('value')->nullable();
            $table->integer('weight')->nullable();
            $table->integer('order')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('eligify_evaluations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->index();
            $table->foreignId('criteria_id')->constrained('eligify_criteria')->cascadeOnDelete();
            $table->string('evaluable_type')->nullable();
            $table->unsignedBigInteger('evaluable_id')->nullable();
            $table->string('slug')->nullable()->index();
            $table->boolean('passed')->default(false);
            $table->decimal('score', 8, 2)->nullable();
            $table->json('failed_rules')->nullable();
            $table->json('rule_results')->nullable();
            $table->string('decision')->nullable();
            $table->json('context')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('evaluated_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('eligify_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->index();
            $table->string('event');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('slug')->nullable()->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('context')->nullable();
            $table->string('user_type')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eligify_audit_logs');
        Schema::dropIfExists('eligify_evaluations');
        Schema::dropIfExists('eligify_rules');
        Schema::dropIfExists('eligify_criteria');
    }
};
