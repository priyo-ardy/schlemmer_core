<?php

namespace App\Http\Controllers\api\v1\User;

use App\Http\Controllers\Controller;
use App\Services\Users\UserServices;
use Illuminate\Http\Request;

class UserApiController extends Controller
{
    public function __construct(
        protected UserServices $userService
    ) {}

    public function dropDown(Request $request)
    {
        try {
            $search = $request->query('search');

            $users = $this->userService->searchUser($search);

            return response()->json([
                'data' => $users->items(),
                'has_more' => $users->hasMorePages()
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors('errors', "Internal server error: " . $e->getMessage());
        }
    }
}
