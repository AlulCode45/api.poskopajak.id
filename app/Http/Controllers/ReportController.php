<?php

namespace App\Http\Controllers;

use App\Contracts\ReportServiceInterface;
use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\UpdateReportRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportServiceInterface $reportService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['status', 'category', 'search', 'per_page']);
            $user = $request->user();
            $isAdmin = $user->hasRole('admin');

            $reports = $this->reportService->getAllReports(
                $filters,
                $isAdmin ? null : $user->id
            );

            return response()->json($reports);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch reports',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReportRequest $request): JsonResponse
    {
        try {
            $report = $this->reportService->createReport(
                $request->validated(),
                $request->user()->id
            );

            return response()->json([
                'message' => 'Report created successfully',
                'data' => $report->load('user'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a public report (no authentication required).
     */
    public function storePublic(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'category' => 'required|string|max:100',
                'location' => 'required|string|max:255',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'image' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf|max:5120', // Single file (backward compatibility)
                'attachments' => 'nullable|array|max:5', // Max 5 files
                'attachments.*' => 'file|mimes:jpeg,png,jpg,gif,pdf|max:5120', // Each file max 5MB
            ]);

            // Create anonymous/guest user or use a system user
            $systemUserId = 1;

            $report = $this->reportService->createReport(
                $validated,
                $systemUserId
            );

            return response()->json([
                'message' => 'Laporan berhasil dikirim',
                'data' => [
                    'id' => $report->id,
                    'title' => $report->title,
                    'status' => $report->status,
                    'image_url' => $report->image_url,
                    'attachments' => $report->attachments,
                    'created_at' => $report->created_at,
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengirim laporan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $report = $this->reportService->getReportById($id);
            return response()->json($report);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Report not found',
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReportRequest $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            $isAdmin = $user->hasRole('admin');

            $report = $this->reportService->updateReport(
                $id,
                $request->validated(),
                $user->id,
                $isAdmin
            );

            return response()->json([
                'message' => 'Report updated successfully',
                'report' => $report,
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            $isAdmin = $user->hasRole('admin');

            $this->reportService->deleteReport($id, $user->id, $isAdmin);

            return response()->json([
                'message' => 'Report deleted successfully',
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get dashboard statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $isAdmin = $user->hasRole('admin');

            $data = $this->reportService->getDashboardStats(
                $user->id,
                $isAdmin
            );

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update report status with admin notes.
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if user has admin or moderator role
            if (!$user->hasRole(['admin', 'moderator'])) {
                return response()->json([
                    'message' => 'Unauthorized. Admin or moderator role required.',
                ], 403);
            }

            $validated = $request->validate([
                'status' => 'required|in:pending,reviewed,resolved,rejected',
                'admin_notes' => 'nullable|string',
            ]);

            $report = $this->reportService->updateReportStatus(
                $id,
                $validated['status'],
                $validated['admin_notes'] ?? null,
                $user->id
            );

            return response()->json([
                'message' => 'Status berhasil diperbarui',
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memperbarui status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk update status for multiple reports.
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if user has admin role
            if (!$user->hasRole('admin')) {
                return response()->json([
                    'message' => 'Unauthorized. Admin role required.',
                ], 403);
            }

            $validated = $request->validate([
                'report_ids' => 'required|array',
                'report_ids.*' => 'integer|exists:reports,id',
                'status' => 'required|in:pending,reviewed,resolved,rejected',
                'admin_notes' => 'nullable|string',
            ]);

            $updated = $this->reportService->bulkUpdateStatus(
                $validated['report_ids'],
                $validated['status'],
                $validated['admin_notes'] ?? null,
                $user->id
            );

            return response()->json([
                'message' => "Berhasil memperbarui {$updated} laporan",
                'updated_count' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memperbarui status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk delete reports.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if user has admin role
            if (!$user->hasRole('admin')) {
                return response()->json([
                    'message' => 'Unauthorized. Admin role required.',
                ], 403);
            }

            $validated = $request->validate([
                'report_ids' => 'required|array',
                'report_ids.*' => 'integer|exists:reports,id',
            ]);

            $deleted = $this->reportService->bulkDelete($validated['report_ids']);

            return response()->json([
                'message' => "Berhasil menghapus {$deleted} laporan",
                'deleted_count' => $deleted,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus laporan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
