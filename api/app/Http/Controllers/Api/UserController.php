<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkStoreUsersRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private const DEFAULT_PER_PAGE = 50;
    private const MAX_PER_PAGE = 200;

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', self::DEFAULT_PER_PAGE);

        return max(1, min($perPage, self::MAX_PER_PAGE));
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            User::orderBy('id')->paginate($this->perPage($request))
        );
    }

    public function emails(Request $request): JsonResponse
    {
        return response()->json(
            User::select('id', 'email')->orderBy('id')->paginate($this->perPage($request))
        );
    }

    public function overTwenty(Request $request): JsonResponse
    {
        $cutoff = Carbon::now()->subYears(20)->startOfDay();

        $page = User::where('birth_date', '<=', $cutoff->toDateString())
            ->orderBy('id')
            ->paginate($this->perPage($request))
            ->toArray();

        return response()->json(['cutoff_date' => $cutoff->toDateString()] + $page);
    }

    public function bulkStore(BulkStoreUsersRequest $request): JsonResponse
    {
        $created = [];

        foreach ($request->validated()['users'] as $userData) {
            $created[] = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'birth_date' => $userData['birth_date'],
                'password' => Hash::make($userData['password'] ?? 'password'),
            ]);
        }

        return response()->json([
            'message' => 'Se crearon 3 usuarios correctamente.',
            'users' => $created,
        ], 201);
    }
}