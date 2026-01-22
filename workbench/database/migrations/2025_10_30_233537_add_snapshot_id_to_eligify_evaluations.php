<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('eligify_evaluations', function (Blueprint $table) {
            $table->foreignId('snapshot_id')
                ->nullable()
                ->after('criteria_id')
                ->constrained('eligify_snapshots')
                ->nullOnDelete();

            $table->index('snapshot_id');
        });
    }

    public function down()
    {
        Schema::table('eligify_evaluations', function (Blueprint $table) {
            $table->dropForeign(['snapshot_id']);
            $table->dropColumn('snapshot_id');
        });
    }
};
