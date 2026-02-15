<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->input('query', '');

        if (empty($query)) {
            return response()->json([]);
        }

        $users = User::where('username', 'like', $query . '%')
            ->select('id', 'username')
            ->limit(10)
            ->get();

        $results = $users->map(function ($user) {
            return [
                'username' => $user->username,
                'avatar' => $user->avatarUrl(),
            ];
        });

        return response()->json($results->values());
    }
}





















