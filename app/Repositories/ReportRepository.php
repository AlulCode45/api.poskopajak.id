<?php

namespace App\Repositories;

use App\Contracts\ReportRepositoryInterface;
use App\Models\Report;
use Illuminate\Support\Facades\DB;

class ReportRepository implements ReportRepositoryInterface
{
    public function __construct(
        protected Report $model
    ) {
    }

    /**
     * Get all reports with optional filters
     */
    public function all(array $filters = [])
    {
        $query = $this->model->with(['user', 'attachments', 'updatedBy']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $filters['per_page'] ?? 10;
        return $query->latest()->paginate($perPage);
    }

    /**
     * Find a report by ID
     */
    public function find(string $id)
    {
        return $this->model->with(['user', 'attachments', 'updatedBy'])->findOrFail($id);
    }

    /**
     * Create a new report
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * Update a report
     */
    public function update(string $id, array $data)
    {
        $report = $this->find($id);
        $report->update($data);
        return $report->fresh('user');
    }

    /**
     * Delete a report
     */
    public function delete(string $id)
    {
        $report = $this->find($id);
        return $report->delete();
    }

    /**
     * Get user's reports
     */
    public function getUserReports(int $userId, array $filters = [])
    {
        $query = $this->model->where('user_id', $userId)->with(['user', 'attachments']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $perPage = $filters['per_page'] ?? 10;
        return $query->latest()->paginate($perPage);
    }

    /**
     * Get dashboard statistics
     */
    public function getStats(?int $userId = null)
    {
        $query = $this->model;

        if ($userId) {
            $query = $query->where('user_id', $userId);
        }

        return [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'reviewed' => (clone $query)->where('status', 'reviewed')->count(),
            'resolved' => (clone $query)->where('status', 'resolved')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
        ];
    }

    /**
     * Get recent reports
     */
    public function getRecentReports(int $limit = 5)
    {
        return $this->model->with('user')
            ->latest()
            ->take($limit)
            ->get();
    }
}
