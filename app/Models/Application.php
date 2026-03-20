<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Application extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'applied_for',
        'user_id',
        'name',
        'email',
        'mobile',
        'dob',

        'present_address',
        'present_district',
        'present_pin',

        'permanent_address',
        'permanent_district',
        'permanent_pin',

        'health_insurance_experience',
        'health_experience_years',

        'status',
    ];

    protected $casts = [
        'dob'                     => 'date',
        'health_experience_years' => 'float',
    ];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(ApplicationEducation::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(ApplicationExperience::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /** Return a specific document by type, or null. */
    public function document(string $type): ?ApplicationDocument
    {
        return $this->documents->firstWhere('document_type', $type);
    }
}
