<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationEducation extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'qualification',
        'stream',
        'board',
        'percentage',
        'division',
    ];

    protected $casts = [
        'percentage' => 'float',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
