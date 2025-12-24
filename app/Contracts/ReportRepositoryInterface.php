<?php

namespace App\Contracts;

interface ReportRepositoryInterface extends RepositoryInterface
{
    public function getUserReports(int $userId, array $filters = []);
    public function getStats(?int $userId = null);
    public function getRecentReports(int $limit = 5);
}
