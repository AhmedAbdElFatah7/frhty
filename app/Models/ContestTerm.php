<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContestTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'contest_id',
        'term',
        'order',
    ];

    /**
     * Get the contest this term belongs to.
     */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }
}
