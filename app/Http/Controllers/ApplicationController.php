<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitApplicationRequest;
use App\Models\Application;
use App\Services\ApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class ApplicationController extends Controller
{
    public function __construct(private readonly ApplicationService $service) {}

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /api/submit-application
     *
     * Accepts multipart/form-data with JSON-encoded education & experience arrays
     * plus individual uploaded document files.
     */
    public function store(SubmitApplicationRequest $request): JsonResponse
    {
        // Check the authenticated user hasn't already submitted
        $existing = Application::where('user_id', $request->user()->id)
            ->whereNotIn('status', ['rejected'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'You have already submitted an application.',
                'application_id' => $existing->id,
            ], 409);
        }

        $application = $this->service->submit(
            data:   $request->except(array_keys($request->allFiles())),
            files:  $request->allFiles(),
            userId: $request->user()->id,
        );

        return response()->json([
            'message'     => 'Application submitted successfully.',
            'application' => $application,
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/application
     *
     * Returns the authenticated user's application with all relations.
     * Frontend uses this on mount — if 404, redirect to form.
     */
    public function show(Request $request): JsonResponse
    {
        $application = Application::with(['educations', 'experiences', 'documents'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$application) {
            return response()->json(['message' => 'No application found.'], 404);
        }

        return response()->json(['application' => $application]);
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/application/document/{type}
     *
     * Returns a temporary signed URL for the given document type.
     * The frontend opens this URL in a new tab.
     */
    public function documentStream(Request $request, string $type): mixed
    {
        $allowedTypes = [
            'photo', 'id_proof', 'address_proof', 'hslc_admit',
            'marksheet', 'offer_letter', 'experience_certificate', 'resume',
        ];

        if (!in_array($type, $allowedTypes)) {
            return response()->json(['message' => 'Invalid document type.'], 422);
        }

        $application = Application::where('user_id', $request->user()->id)->first();

        if (!$application) {
            return response()->json(['message' => 'Application not found.'], 404);
        }

        $document = $application->documents()->where('document_type', $type)->first();

        if (!$document) {
            return response()->json(['message' => 'Document not found.'], 404);
        }

        $disk = Storage::disk($document->disk);

        if (!$disk->exists($document->stored_path)) {
            return response()->json(['message' => 'File not found on disk.'], 404);
        }

        return response()->stream(function () use ($disk, $document) {
            $stream = $disk->readStream($document->stored_path);
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type'        => $document->mime_type,
            'Content-Disposition' => 'inline; filename="' . $document->original_name . '"',
            'Cache-Control'       => 'no-store',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/admin/applications
     *
     * Admin-only: paginated list of all applications.
     */
    public function index(Request $request): JsonResponse
    {
        $applications = Application::with(['user', 'educations', 'experiences', 'documents'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json($applications);
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * PATCH /api/admin/applications/{application}/status
     *
     * Admin-only: update status of an application.
     */
    public function updateStatus(Request $request, Application $application): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:draft,submitted,under_review,accepted,rejected'],
        ]);

        $application->update(['status' => $request->status]);

        return response()->json([
            'message'     => 'Application status updated.',
            'application' => $application->fresh(),
        ]);
    }
}
