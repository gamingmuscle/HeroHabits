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
        Schema::create('treasure_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasure_id')->constrained();
            $table->foreignId('child_id')->constrained()->onDelete('cascade');
            $table->integer('gold_spent');
            $table->timestamp('purchased_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasure_purchases');
    }
};
