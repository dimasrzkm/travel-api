<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first(); // mengecek apakah user ada berdasarkan email

        if (! $user || ! Hash::check($request->password, $user->password)) { // mengecek user ditemukan atau user password sama dengan password yang diinputkan
            return response()->json([
                    'error' => 'The Provide credential are incorrect.',
                ], 422);
        }

        // mengenerate token
        $device = substr($request->userAgent() ?? '', 0, 255);

        return response()->json([
            'access_token' => $user->createToken($device)->plainTextToken,
        ]);
    }
}
