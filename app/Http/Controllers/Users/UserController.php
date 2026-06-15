<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Services\Users\UserServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserController extends Controller
{
    protected UserServices $userService;

    public function __construct(UserServices $userServices)
    {
        $this->userService = $userServices;
    }

    public function index()
    {
        return Inertia::render('Users/Users', [
            'users' => $this->userService->getAllUsers(),
            'current_user_id' => Auth::id()
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $validatedData = $request->validated();

        $this->userService->registerUser($validatedData);

        return redirect()->back();
    }

    public function update(Request $request, $id)
    {
        // Validasi input form secara dinamis, avatar dibuat nullable & wajib file gambar
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|string|email|max:150',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'login_attempts' => 'required|integer|min:0',
            'is_locked' => 'required|string',
            'is_active' => 'required|string',
        ]);

        $validated['is_locked'] = filter_var($request->is_locked, FILTER_VALIDATE_BOOLEAN);
        $validated['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar');
        }

        $this->userService->updateSecuritySettings($id, $validated);
        return redirect()->back();
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:users,id'
        ]);

        try {
            $this->userService->bulkDeleteUsers($request->ids, Auth::id());

            return redirect()->back();
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'bulk_error' => $e->getMessage()
            ]);
        }
    }
}
