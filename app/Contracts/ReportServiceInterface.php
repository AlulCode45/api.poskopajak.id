<?php

namespace App\Contracts;

interface ReportServiceInterface
{
    public function getAllReports(array $filters = [], ?int $userId = null);
    public function getReportById(int $id);
    public function createReport(array $data, int $userId);
    public function updateReport(int $id, array $data, int $userId, bool $isAdmin);
    public function deleteReport(int $id, int $userId, bool $isAdmin);
    public function getDashboardStats(?int $userId = null, bool $isAdmin = false);
}
