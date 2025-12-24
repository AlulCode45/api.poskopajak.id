<?php

namespace App\Services;

use App\Contracts\ReportRepositoryInterface;
use App\Contracts\ReportServiceInterface;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class ReportService implements ReportServiceInterface
{
    public function __construct(
        protected ReportRepositoryInterface $reportRepository
    ) {
    }

    /**
     * Get all reports with filters
     */
    public function getAllReports(array $filters = [], ?int $userId = null)
    {
        if ($userId && !isset($filters['user_specific'])) {
            // Regular users only see their own reports unless specified otherwise
            return $this->reportRepository->getUserReports($userId, $filters);
        }

        return $this->reportRepository->all($filters);
    }

    /**
     * Get single report by ID
     */
    public function getReportById(int $id)
    {
        return $this->reportRepository->find($id);
    }

    /**
     * Create a new report
     */
    public function createReport(array $data, int $userId)
    {
        $data['user_id'] = $userId;
        $data['status'] = 'pending';

        // Handle image upload if exists
        if (isset($data['image']) && $data['image']) {
            $data['image_path'] = $this->handleImageUpload($data['image']);
            unset($data['image']);
        }

        return $this->reportRepository->create($data);
    }

    /**
     * Update existing report
     */
    public function updateReport(int $id, array $data, int $userId, bool $isAdmin)
    {
        $report = $this->reportRepository->find($id);

        // Authorization check
        if (!$isAdmin && $report->user_id !== $userId) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Unauthorized to update this report.');
        }

        // Handle image upload if exists
        if (isset($data['image']) && $data['image']) {
            // Delete old image
            if ($report->image_path) {
                $this->deleteImage($report->image_path);
            }
            $data['image_path'] = $this->handleImageUpload($data['image']);
            unset($data['image']);
        }

        return $this->reportRepository->update($id, $data);
    }

    /**
     * Delete a report
     */
    public function deleteReport(int $id, int $userId, bool $isAdmin)
    {
        $report = $this->reportRepository->find($id);

        // Authorization check
        if (!$isAdmin && $report->user_id !== $userId) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Unauthorized to delete this report.');
        }

        // Delete image if exists
        if ($report->image_path) {
            $this->deleteImage($report->image_path);
        }

        return $this->reportRepository->delete($id);
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(?int $userId = null, bool $isAdmin = false)
    {
        $stats = $this->reportRepository->getStats($isAdmin ? null : $userId);

        if (!$isAdmin && $userId) {
            $stats['my_reports'] = $this->reportRepository->getStats($userId)['total'];
        }

        $recentReports = $this->reportRepository->getRecentReports(5);

        return [
            'stats' => $stats,
            'recent_reports' => $recentReports,
        ];
    }

    /**
     * Handle image upload
     */
    protected function handleImageUpload($image): string
    {
        return $image->store('reports', 'public');
    }

    /**
     * Delete image from storage
     */
    protected function deleteImage(string $path): void
    {
        Storage::disk('public')->delete($path);
    }
}
