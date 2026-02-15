<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\AvatarSize;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = $request->input('q', '');
        $limit = $request->input('limit', 10);

        if (empty($q)) {
            return response()->json([]);
        }

        $users = User::where('username', 'like', $q . '%')
            ->select('has_avatar', 'id', 'username')
            ->limit($limit)
            ->get();

        $results = $users->map(function (User $user) {
            return [
                'avatar' => $user->avatarUrl(AvatarSize::S->value),
                'id' => $user->id,
                'username' => $user->username,
            ];
        });

        return response()->json($results->values());
    }
}

