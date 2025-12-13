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
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('contest_id')->nullable()->constrained('contests')->onDelete('set null');
            $table->string('media_path')->nullable(); 
            $table->enum('media_type', ['image', 'video'])->default('image');
            $table->text('caption')->nullable();
            $table->timestamp('expires_at')->nullable(); 
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for better performance
            $table->index('user_id');
            $table->index('contest_id');
            $table->index('expires_at');
            $table->index(['is_active', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
