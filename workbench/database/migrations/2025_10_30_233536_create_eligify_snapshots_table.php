<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('eligify_snapshots', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->string('snapshotable_type'); // Polymorphic relation (User, Application, etc.)
            $table->unsignedBigInteger('snapshotable_id');
            $table->json('data'); // The actual snapshot data
            $table->string('checksum', 64)->index(); // SHA-256 hash for data integrity
            $table->json('meta')->nullable(); // Additional metadata
            $table->timestamp('captured_at')->index();
            $table->timestamps();

            $table->index(['snapshotable_type', 'snapshotable_id']);
            $table->index(['checksum', 'snapshotable_type', 'snapshotable_id'], 'eligify_snapshots_dedup_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('eligify_snapshots');
    }
};
