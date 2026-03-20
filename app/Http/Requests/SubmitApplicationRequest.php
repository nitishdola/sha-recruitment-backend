<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Must be authenticated
        return $this->user() !== null;
    }

    // ─── Rules ────────────────────────────────────────────────────────────────

    public function rules(): array
    {
        return [
            // ── Personal ──────────────────────────────────────────────────────
            'applied_for' => ['required', 'string', 'max:255'],
            'name'   => ['required', 'string', 'max:255'],
            'email'  => ['required', 'email', 'max:255'],
            'mobile' => ['required', 'digits:10'],
            'dob'    => ['required', 'date', 'before:today'],

            // ── Present Address ───────────────────────────────────────────────
            'present_address'  => ['required', 'string', 'max:500'],
            'present_district' => ['required', 'string', 'max:100'],
            'present_pin'      => ['required', 'digits:6'],

            // ── Permanent Address ─────────────────────────────────────────────
            'permanent_address'  => ['required', 'string', 'max:500'],
            'permanent_district' => ['required', 'string', 'max:100'],
            'permanent_pin'      => ['required', 'digits:6'],

            // ── Health Insurance ──────────────────────────────────────────────
            'health_insurance_experience' => ['required', 'in:yes,no'],
            'health_experience_years'     => ['nullable', 'numeric', 'min:0', 'max:60'],

            // ── Education (JSON-encoded array) ────────────────────────────────
            'education.*.stream'      => ['nullable', 'string', 'max:100'],
            'education.*.board'       => ['nullable', 'string', 'max:150'],
            'education.*.percentage'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'education.*.division'    => ['nullable', 'in:1st,2nd,3rd,Pass'],

            // ── Experience (JSON-encoded array) ───────────────────────────────
            'experience'                     => ['required', 'array'],
            'experience.*.organization'      => ['required', 'string', 'max:255'],
            'experience.*.designation'       => ['required', 'string', 'max:255'],
            'experience.*.from'              => ['required', 'date'],
            'experience.*.to'               => ['nullable', 'date', 'after_or_equal:experience.*.from'],
            'experience.*.years'             => ['nullable', 'numeric', 'min:0', 'max:60'],

            // ── Documents ─────────────────────────────────────────────────────
            'photo'                  => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'id_proof'               => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'address_proof'          => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'hslc_admit'             => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'marksheet'              => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'offer_letter'           => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'experience_certificate' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'resume'                 => ['required', 'file', 'mimes:pdf',             'max:2048'],
        ];
    }

    // ─── Custom messages ──────────────────────────────────────────────────────

    public function messages(): array
    {
        return [
            'mobile.digits'                  => 'Mobile number must be exactly 10 digits.',
            'present_pin.digits'             => 'Present PIN code must be 6 digits.',
            'permanent_pin.digits'           => 'Permanent PIN code must be 6 digits.',
            'health_insurance_experience.in' => 'Please select Yes or No for health insurance experience.',
            'resume.mimes'                   => 'Resume must be uploaded as a PDF file.',
            'dob.before'                     => 'Date of birth must be in the past.',
            // removed: education.json and experience.json
        ];
    }

    // ─── Prepare / decode JSON fields before validation ───────────────────────

    protected function prepareForValidation(): void
    {
        if ($this->has('education') && is_string($this->education)) {
            $decoded = json_decode($this->education, true);
            if (is_array($decoded)) {
                $this->merge(['education' => $decoded]);
            }
        }

        if ($this->has('experience') && is_string($this->experience)) {
            $decoded = json_decode($this->experience, true);
            if (is_array($decoded)) {
                // Convert each row's from/to dates from dd-mm-yyyy → yyyy-mm-dd
                $decoded = array_map(function ($row) {
                    if (!empty($row['from'])) {
                        $row['from'] = $this->convertDate($row['from']);
                    }
                    if (!empty($row['to'])) {
                        $row['to'] = $this->convertDate($row['to']);
                    }
                    return $row;
                }, $decoded);

                $this->merge(['experience' => $decoded]);
            }
        }

        // Convert top-level dob field too
        if ($this->has('dob') && !empty($this->dob)) {
            $this->merge(['dob' => $this->convertDate($this->dob)]);
        }
    }

    // **
    // * Converts dd-mm-yyyy → yyyy-mm-dd.
    // * If already in yyyy-mm-dd or unparseable, returns as-is.
    // */
    private function convertDate(string $date): string
    {
        // Already ISO format yyyy-mm-dd — leave it alone
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        // dd-mm-yyyy or dd/mm/yyyy
        if (preg_match('/^(\d{2})[-\/](\d{2})[-\/](\d{4})$/', $date, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        // Fallback: let strtotime try (handles natural language dates etc.)
        $timestamp = strtotime($date);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        // Return original — validation will catch it as invalid
        return $date;
    }
}
