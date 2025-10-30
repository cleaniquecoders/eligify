<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eligify_criteria', function (Blueprint $table) {
            $table->string('type')->nullable()->index()->after('is_active');
            $table->string('group')->nullable()->index()->after('type');
            $table->string('category')->nullable()->index()->after('group');
            $table->json('tags')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('eligify_criteria', function (Blueprint $table) {
            $table->dropColumn(['type', 'group', 'category', 'tags']);
        });
    }
};
