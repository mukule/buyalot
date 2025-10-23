import type { Auth } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermissions() {
    const page = usePage();

    const auth = computed(() => page.props.auth as Auth);
    const roles = computed(() => auth.value.roles as string[]);
    const permissions = computed(() => auth.value.permissions as string[]);

    const hasPermission = (permission: string): boolean => {
        return permissions.value.includes(permission);
    };

    const hasAnyPermission = (requiredPermissions: string[]): boolean => {
        if (requiredPermissions.length === 0) return true;
        return requiredPermissions.some((permission) => permissions.value.includes(permission));
    };

    const hasAllPermissions = (requiredPermissions: string[]): boolean => {
        if (requiredPermissions.length === 0) return true;
        return requiredPermissions.every((permission) => permissions.value.includes(permission));
    };

    const hasRole = (role: string): boolean => {
        return roles.value.includes(role);
    };

    const hasAnyRole = (requiredRoles: string[]): boolean => {
        if (requiredRoles.length === 0) return true;
        return requiredRoles.some((role) => roles.value.includes(role));
    };

    const hasAllRoles = (requiredRoles: string[]): boolean => {
        if (requiredRoles.length === 0) return true;
        return requiredRoles.every((role) => roles.value.includes(role));
    };

    // Enhanced permission check that supports both permissions and roles
    const canAccess = (item: {
        permissions?: string[];
        roles?: string[];
        requireAllPermissions?: boolean;
        customCheck?: (auth: Auth) => boolean;
    }): boolean => {
        // Custom check takes precedence
        if (item.customCheck) {
            return item.customCheck(auth.value);
        }

        // Check roles if specified
        if (item.roles && item.roles.length > 0) {
            if (!hasAnyRole(item.roles)) {
                return false;
            }
        }

        // Check permissions if specified
        if (item.permissions && item.permissions.length > 0) {
            if (item.requireAllPermissions) {
                return hasAllPermissions(item.permissions);
            } else {
                return hasAnyPermission(item.permissions);
            }
        }
        return true;
    };

    return {
        auth: auth.value,
        roles: roles.value,
        permissions: permissions.value,
        hasPermission,
        hasAnyPermission,
        hasAllPermissions,
        hasRole,
        hasAnyRole,
        hasAllRoles,
        canAccess,
    };
}
