<?php

namespace App\Services;

use App\Contracts\AuthServiceInterface;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        protected User $userModel
    ) {
    }

    /**
     * Register a new user
     */
    public function register(array $data)
    {
        $user = $this->userModel->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Assign default role
        $user->assignRole('user');

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user->load('roles'),
            'token' => $token,
        ];
    }

    /**
     * Login user
     */
    public function login(string $email, string $password)
    {
        $user = $this->userModel->where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user->load('roles', 'permissions'),
            'token' => $token,
        ];
    }

    /**
     * Logout current user
     */
    public function logout()
    {
        $user = auth()->user();
        $user->currentAccessToken()->delete();

        return true;
    }

    /**
     * Get current authenticated user
     */
    public function getCurrentUser()
    {
        return auth()->user()->load('roles', 'permissions');
    }
}
