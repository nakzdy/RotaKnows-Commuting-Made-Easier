<?php

namespace App\Http\Controllers\Api; 

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    // READ (List all users)
    public function index()
    {
        
        return response()->json(User::all());
    }

    // CREATE 
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    // READ (Show a specific user)
    public function show(User $user) // Laravel's route model binding injects the User model
    {
        return response()->json($user);
    }

    // UPDATE
    public function update(Request $request, User $user)
    {
        try {
            // Validate update data
            $rules = [
                'name' => ['sometimes', 'string', 'max:255'],
                'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
                'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
            ];

            // Only require password_confirmation if password is present
            if ($request->has('password')) {
                $rules['password_confirmation'] = ['required_with:password'];
            }

            $validatedData = $request->validate($rules);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors(),
            ], 422);
        }

        // Update name and email if present in request
        if ($request->has('name')) {
            $user->name = $validatedData['name'];
        }
        if ($request->has('email')) {
            $user->email = $validatedData['email'];
        }

        // Update password if present in request
        if ($request->has('password')) {
            $user->password = Hash::make($validatedData['password']);
        }

        $user->save();

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user,
        ]);
    }

    // DELETE
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 204); // 204 No Content for successful deletion
    }
}