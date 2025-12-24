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
                'report' => $report->load('user'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
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
    public function update(UpdateReportRequest $request, int $id): JsonResponse
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
    public function destroy(Request $request, int $id): JsonResponse
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
}
