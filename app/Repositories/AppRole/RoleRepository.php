<?php

namespace App\Repositories\AppRole;

use App\Models\ChangeLogs;
use Spatie\Permission\Models\Role;

class RoleRepository
{
    public function getDataById(int $id)
    {
        return Role::with('permissions')
            ->where('id', $id)
            ->first();
    }

    public function getAllRoles($perPage = 10, $search = null)
    {
        $query = Role::with('permissions');

        // Jika ada request pencarian dari Vue
        if (! empty($search)) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        // Gunakan paginate(), BUKAN get()
        // withQueryString() agar pagination link tidak menghilangkan parameter search di URL
        return $query->paginate($perPage)->withQueryString();
    }

    public function storeRole(array $data)
    {
        $role = Role::create(['name' => $data['name']]);

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $role->load('permissions');
    }

    public function update(int $id, array $data)
    {
        $role = Role::findOrFail($id);

        $role->update(['name' => $data['name']]);

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        } else {
            $role->syncPermissions([]); // Kosongkan jika tidak ada permission yang dipilih
        }

        return $role->load('permissions');
    }

    public function delete(int $id)
    {
        $role = Role::findOrFail($id);

        // Opsional: Hapus relasi permission sebelum delete jika diperlukan
        $role->syncPermissions([]);

        return $role->delete();
    }

    public function massDelete(array $ids)
    {
        // Menggunakan loop atau query builder untuk hapus banyak data sekaligus
        $roles = Role::whereIn('id', $ids)->get();

        foreach ($roles as $role) {
            $role->syncPermissions([]);
            $role->delete();
        }

        return true;
    }

    public function getLogs(int $id)
    {
        return ChangeLogs::where('item_id', $id)->where('table_name', 'roles')->orderBy('created_at', 'desc')->get();
    }
}
