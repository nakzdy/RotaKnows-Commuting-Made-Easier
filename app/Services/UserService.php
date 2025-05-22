<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * Register a new user.
     *
     * @param array $userData
     * @return User
     * @throws ValidationException
     */
    public function registerUser(array $userData): User
    {

        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
        ]);


        return $user;
    }
}