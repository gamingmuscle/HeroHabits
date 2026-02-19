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
        Schema::create('child_traits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained()->onDelete('cascade');
            $table->foreignId('trait_id')->constrained()->onDelete('cascade');
            $table->integer('level')->default(1);
            $table->integer('experience_points')->default(0);
            $table->timestamps();

            // Ensure each child has only one record per trait
            $table->unique(['child_id', 'trait_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('child_traits');
    }
};
