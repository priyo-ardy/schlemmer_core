<?php

namespace App\Http\Controllers\AppRole;

use App\Http\Controllers\Controller;
use App\Services\AppRole\RoleService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function __construct(
        protected RoleService $roleService
    ) {}

    public function index(Request $request)
    {
        // Tangkap parameter dari request Vue, set default per_page ke 10
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        return Inertia::render('AppRole/Index', [
            // Kirim data yang sudah di-paginate dan difilter
            'roles' => $this->roleService->getAllRoles($perPage, $search),
            'permissions' => Permission::all(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $this->roleService->storeRole($request->only(['name', 'permissions']));

        return redirect()->back()->with('success', 'Role berhasil dibuat.');
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name,'.$id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $this->roleService->update($id, $request->only(['name', 'permissions']));

        return redirect()->back()->with('success', 'Role berhasil diperbarui.');
    }

    public function delete(Request $request, int $id)
    {
        $this->roleService->delete($id);

        return redirect()->back()->with('success', 'Role berhasil dihapus.');
    }

    public function massDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:roles,id',
        ]);

        $this->roleService->massDelete($request->ids);

        return redirect()->back()->with('success', 'Role terpilih berhasil dihapus.');
    }

    public function getLogs(Request $request, int $id)
    {
        $logs = $this->roleService->getLogs($id);

        return response()->json($logs);
    }
}
