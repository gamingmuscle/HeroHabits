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
        Schema::table('quest_completions', function (Blueprint $table) {
            $table->integer('experience_points_awarded')->default(0)->after('gold_earned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quest_completions', function (Blueprint $table) {
            $table->dropColumn('experience_points_awarded');
        });
    }
};
