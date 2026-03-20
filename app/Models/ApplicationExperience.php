<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationExperience extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'organization',
        'designation',
        'from_date',
        'to_date',
        'years',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date'   => 'date',
        'years'     => 'float',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
