<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Services\Users\UserServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

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
            'current_user_id' => Auth::id(),
            'page_title' => 'Application / User Management',
            'roles' => Role::all(),
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        try {
            $validatedData = $request->validated();

            $this->userService->registerUser($validatedData);

            return redirect()->back()->with('success', 'New user registered successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:150',
                'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore($id)],
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
                'login_attempts' => 'required|integer|min:0',
                'is_locked' => 'required|boolean',
                'is_active' => 'required|boolean',
                'password' => 'nullable|string|min:8',
                'role' => 'nullable|string|exists:roles,name',
            ]);

            $validated['is_locked'] = filter_var($request->is_locked, FILTER_VALIDATE_BOOLEAN);
            $validated['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);

            if ($request->hasFile('avatar')) {
                $validated['avatar'] = $request->file('avatar');
            }

            $this->userService->updateSecuritySettings($id, $validated);

            return redirect()->back()->with('success', 'User data updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function bulkDestroy(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer|exists:users,id',
                'remark' => 'required|string|min:5',
            ]);

            try {
                $this->userService->bulkDeleteUsers($request->ids, Auth::id(), $request->input('remark'));

                return redirect()->back();
            } catch (\Exception $e) {
                return redirect()->back()->withErrors([
                    'bulk_error' => $e->getMessage(),
                ]);
            }
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'error' => $e->getMessage(),
            ]);
        }
    }
}
