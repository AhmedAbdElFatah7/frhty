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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contest_id')->constrained('contests')->onDelete('cascade');
            $table->text('question_text');
            $table->string('option_1');
            $table->string('option_2');
            $table->string('option_3');
            $table->enum('correct_answer', ['1', '2', '3']); // Which option is correct
            $table->integer('order')->default(0); // Order of questions in the contest
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
