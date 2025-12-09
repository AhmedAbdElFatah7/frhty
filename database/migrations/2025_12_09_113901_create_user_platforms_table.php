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
        Schema::create('user_platforms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('platform_id')->constrained('platforms')->onDelete('cascade');
            $table->string('account_url')->nullable();
            $table->unsignedBigInteger('followers_count')->nullable();
            $table->timestamps();

            // Ensure user can't have duplicate platforms
            $table->unique(['user_id', 'platform_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_platforms');
    }
};
