<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContestPrize extends Model
{
    use HasFactory;

    protected $fillable = [
        'contest_id',
        'prize',
        'rank',
        'order',
    ];

    /**
     * Get the contest this prize belongs to.
     */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }
}
