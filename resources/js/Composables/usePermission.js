import { usePage } from "@inertiajs/vue3";

export function usePermission(){
    const page = usePage();

    const hasPermission = (permissionName) => {
        const permissions = page.props.auth.permissions || [];
        return permissions.includes(permissionName);
    }

    const hasRole = (roleName) => {
        const roles = page.props.auth.roles || [];
        return roles.includes(roleName);
    }

    return { hasPermission, hasRole };
}
