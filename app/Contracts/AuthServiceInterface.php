<?php

namespace App\Contracts;

interface AuthServiceInterface
{
    public function register(array $data);
    public function login(string $email, string $password);
    public function logout();
    public function getCurrentUser();
}
