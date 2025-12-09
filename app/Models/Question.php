<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'contest_id',
        'question_text',
        'option_1',
        'option_2',
        'option_3',
        'correct_answer',
        'order',
    ];

    /**
     * Get the contest this question belongs to.
     */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /**
     * Get the user answers for this question.
     */
    public function userAnswers(): HasMany
    {
        return $this->hasMany(UserAnswer::class);
    }

    /**
     * Check if the given answer is correct.
     */
    public function isCorrectAnswer(string $answer): bool
    {
        return $this->correct_answer === $answer;
    }

    /**
     * Get options as array (for API response without correct answer).
     */
    public function getOptionsAttribute(): array
    {
        return [
            '1' => $this->option_1,
            '2' => $this->option_2,
            '3' => $this->option_3,
        ];
    }
}
