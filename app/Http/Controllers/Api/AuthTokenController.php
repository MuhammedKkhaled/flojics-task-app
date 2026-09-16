<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthTokenController extends BaseApiController
{
    /**
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:100'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The supplied credentials are invalid.'],
            ]);
        }

        return $this->addToResponse([
            'token' => $user->createToken($credentials['device_name'] ?? 'reviewer')->plainTextToken,
            'user' => $user->only(['id', 'name', 'email', 'role']),
        ])->toResponse();
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->setStatusCode(204)->toResponse();
    }
}
