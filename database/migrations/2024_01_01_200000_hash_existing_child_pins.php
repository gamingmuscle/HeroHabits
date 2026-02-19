<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration hashes existing plaintext PINs in the children table.
     * This is required after implementing PIN hashing security feature.
     */
    public function up(): void
    {
        // Get all children with plaintext PINs
        $children = DB::table('children')->get();

        foreach ($children as $child) {
            // Check if PIN is already hashed (starts with $2y$ for bcrypt)
            if (!str_starts_with($child->pin, '$2y$')) {
                // Hash the plaintext PIN
                DB::table('children')
                    ->where('id', $child->id)
                    ->update(['pin' => Hash::make($child->pin)]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * Note: This cannot truly reverse the hashing since the original
     * plaintext PINs are lost. This is intentional for security.
     */
    public function down(): void
    {
        // Cannot unhash PINs - this is a one-way migration for security
        // If you need to rollback, you'll need to reset PINs manually
    }
};
