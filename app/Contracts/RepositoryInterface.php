<?php

namespace App\Contracts;

interface RepositoryInterface
{
    public function all(array $filters = []);
    public function find(string $id);
    public function create(array $data);
    public function update(string $id, array $data);
    public function delete(string $id);
}
