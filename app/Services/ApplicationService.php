<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApplicationService
{
    /**
     * Document types accepted from the form.
     */
    private const DOCUMENT_TYPES = [
        'photo',
        'id_proof',
        'address_proof',
        'hslc_admit',
        'marksheet',
        'offer_letter',
        'experience_certificate',
        'resume',
    ];

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create a new application along with education rows,
     * experience rows, and uploaded documents — all inside one transaction.
     */
    public function submit(array $data, array $files, int $userId): Application
    {
        return DB::transaction(function () use ($data, $files, $userId) {

            // 1. Create the core application record
            $application = Application::create([
				'applied_for' => $data['applied_for'],
                'user_id'  => $userId,
                'status'   => 'submitted',

                'name'   => $data['name'],
                'email'  => $data['email'],
                'mobile' => $data['mobile'],
                'dob'    => $data['dob'],

                'present_address'  => $data['present_address'],
                'present_district' => $data['present_district'],
                'present_pin'      => $data['present_pin'],

                'permanent_address'  => $data['permanent_address'],
                'permanent_district' => $data['permanent_district'],
                'permanent_pin'      => $data['permanent_pin'],

                'health_insurance_experience' => $data['health_insurance_experience'],
                'health_experience_years'     => $data['health_experience_years'] ?? null,
            ]);

            // 2. Insert education rows
            $this->syncEducation($application, $data['education'] ?? []);

            // 3. Insert experience rows
            $this->syncExperience($application, $data['experience'] ?? []);

            // 4. Store uploaded documents
            $this->storeDocuments($application, $files);

            return $application->load(['educations', 'experiences', 'documents']);
        });
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function syncEducation(Application $application, array $rows): void
    {
        $records = collect($rows)
            ->filter(function ($row) {
                // Skip "Others" row if all fillable fields are empty
                if ($row['qualification'] === 'Others') {
                    return !empty($row['stream'])
                        || !empty($row['board'])
                        || !empty($row['percentage'])
                        || !empty($row['division']);
                }
                return true; // always keep HSLC, HS, Graduation
            })
            ->map(fn ($row) => [
                'application_id' => $application->id,
                'qualification'  => $row['qualification'],
                'stream'         => $this->nullIfEmpty($row['stream']     ?? ''),
                'board'          => $this->nullIfEmpty($row['board']      ?? ''),
                'percentage'     => $this->nullIfEmpty($row['percentage'] ?? ''),
                'division'       => $this->nullIfEmpty($row['division']   ?? ''),
                'created_at'     => now(),
                'updated_at'     => now(),
            ])
            ->toArray();

        if (!empty($records)) {
            $application->educations()->insert($records);
        }
    }

    private function syncExperience(Application $application, array $rows): void
    {
        $records = collect($rows)
            ->filter(fn ($row) => !empty($row['organization'])) // skip blank rows
            ->map(fn ($row) => [
                'application_id' => $application->id,
                'organization'   => $row['organization'],
                'designation'    => $row['designation'],
                'from_date'      => $row['from'],
                'to_date'        => $row['to']    ?? null,
                'years'          => $row['years'] ?? null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ])->toArray();

        if (!empty($records)) {
            $application->experiences()->insert($records);
        }
    }

    private function storeDocuments(Application $application, array $files): void
    {
        foreach (self::DOCUMENT_TYPES as $type) {
            /** @var UploadedFile|null $file */
            $file = $files[$type] ?? null;

            if (!$file instanceof UploadedFile) {
                continue;
            }

            // Build a unique, collision-safe storage path:
            // applications/{app_id}/{type}/{uuid}.{ext}
            $extension  = $file->getClientOriginalExtension();
            $storedPath = sprintf(
                'applications/%d/%s/%s.%s',
                $application->id,
                $type,
                Str::uuid(),
                $extension
            );

            // Store on the configured disk (defaults to 'local'; swap to 's3' via config)
            $disk = config('filesystems.application_disk', 'local');
            Storage::disk($disk)->putFileAs(
                dirname($storedPath),
                $file,
                basename($storedPath)
            );

            // Upsert so re-submissions replace the previous file record
            ApplicationDocument::updateOrCreate(
                [
                    'application_id' => $application->id,
                    'document_type'  => $type,
                ],
                [
                    'original_name' => $file->getClientOriginalName(),
                    'stored_path'   => $storedPath,
                    'disk'          => $disk,
                    'mime_type'     => $file->getMimeType(),
                    'size_bytes'    => $file->getSize(),
                ]
            );
        }
    }

    private function nullIfEmpty(mixed $value): mixed
    {
        if ($value === '' || $value === null) return null;
        return $value;
    }
}
