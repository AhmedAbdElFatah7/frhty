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
        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->onDelete('cascade')->comment('User who is following');
            $table->foreignId('following_id')->constrained('users')->onDelete('cascade')->comment('User being followed (celebrity)');
            $table->timestamps();

            // Ensure a user can't follow the same person twice
            $table->unique(['follower_id', 'following_id']);

            // Add indexes for better performance
            $table->index('follower_id');
            $table->index('following_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
