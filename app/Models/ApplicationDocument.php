<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ApplicationDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'document_type',
        'original_name',
        'stored_path',
        'disk',
        'mime_type',
        'size_bytes',
    ];

    protected $appends = ['url'];

    // ─── Relations ────────────────────────────────────────────────────────────

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    /** Temporary signed URL (S3) or local URL. */
    public function getUrlAttribute(): string
    {
        if ($this->disk === 's3') {
            return Storage::disk('s3')->temporaryUrl($this->stored_path, now()->addMinutes(30));
        }

        return Storage::disk('local')->url($this->stored_path);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public static function humanTypes(): array
    {
        return [
            'photo'                  => 'Passport Photo',
            'id_proof'               => 'ID Proof',
            'address_proof'          => 'Address Proof',
            'hslc_admit'             => 'HSLC Admit Card',
            'marksheet'              => 'Marksheet',
            'offer_letter'           => 'Offer Letter',
            'experience_certificate' => 'Experience Certificate',
            'resume'                 => 'Resume / CV',
        ];
    }
}
