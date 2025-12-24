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
    public function getReportById(string $id)
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

        // Handle single image upload if exists (backward compatibility)
        if (isset($data['image']) && $data['image']) {
            $data['image_path'] = $this->handleImageUpload($data['image']);
            unset($data['image']);
        }

        // Handle multiple attachments if exists
        $attachments = null;
        if (isset($data['attachments']) && is_array($data['attachments'])) {
            $attachments = $data['attachments'];
            unset($data['attachments']);
        }

        $report = $this->reportRepository->create($data);

        // Upload and save attachments
        if ($attachments) {
            foreach ($attachments as $file) {
                $this->saveAttachment($report->id, $file);
            }
        }

        return $report->load('attachments');
    }

    /**
     * Update existing report
     */
    public function updateReport(string $id, array $data, int $userId, bool $isAdmin)
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
    public function deleteReport(string $id, int $userId, bool $isAdmin)
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
     * Save attachment to report
     */
    protected function saveAttachment(string $reportId, $file): void
    {
        $filePath = $file->store('reports/attachments', 'public');

        \App\Models\ReportAttachment::create([
            'report_id' => $reportId,
            'file_path' => $filePath,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);
    }

    /**
     * Delete image from storage
     */
    protected function deleteImage(string $path): void
    {
        Storage::disk('public')->delete($path);
    }

    /**
     * Update report status with admin notes
     */
    public function updateReportStatus(string $id, string $status, ?string $adminNotes, int $updatedBy)
    {
        $report = $this->reportRepository->find($id);

        $data = [
            'status' => $status,
            'updated_by' => $updatedBy,
        ];

        if ($adminNotes !== null) {
            $data['admin_notes'] = $adminNotes;
        }

        return $this->reportRepository->update($id, $data);
    }

    /**
     * Bulk update status for multiple reports
     */
    public function bulkUpdateStatus(array $reportIds, string $status, ?string $adminNotes, int $updatedBy): int
    {
        $count = 0;

        foreach ($reportIds as $id) {
            try {
                $this->updateReportStatus($id, $status, $adminNotes, $updatedBy);
                $count++;
            } catch (\Exception $e) {
                // Continue with other reports
                continue;
            }
        }

        return $count;
    }

    /**
     * Bulk delete reports
     */
    public function bulkDelete(array $reportIds): int
    {
        $count = 0;

        foreach ($reportIds as $id) {
            try {
                $report = $this->reportRepository->find($id);

                // Delete attachments
                if ($report->attachments) {
                    foreach ($report->attachments as $attachment) {
                        Storage::disk('public')->delete($attachment->file_path);
                    }
                }

                // Delete old image
                if ($report->image_path) {
                    $this->deleteImage($report->image_path);
                }

                $this->reportRepository->delete($id);
                $count++;
            } catch (\Exception $e) {
                continue;
            }
        }

        return $count;
    }
}
