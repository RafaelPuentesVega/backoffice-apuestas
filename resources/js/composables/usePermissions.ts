import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { type NavItem, type SharedData } from '@/types';

export function usePermissions() {
    const page = usePage<SharedData>();
    const storedPermissions = localStorage.getItem('userPermissions');
    const storedRoles = localStorage.getItem('userRoles');

    const permissions = computed(() => {

        if (storedPermissions) {
            try {

                const decodedPermissions = atob(storedPermissions);
                return JSON.parse(decodedPermissions);
            } catch (error) {
                console.error('Error decoding permissions from localStorage:', error);
                return [];
            }
        }
        return  JSON.parse(atob(page.props.auth.permissions));
    });

    const roles = computed(() => {
        if (storedRoles) {
            try {
                const decodedRoles = atob(storedRoles);
                return JSON.parse(decodedRoles);
            } catch (error) {
                console.error('Error decoding roles from localStorage:', error);
                return [];
            }
        }
        return JSON.parse(atob(page.props.auth.roles));
    });

    const hasPermission = (permission: string) => {
        return permissions.value.includes(permission);
    };

    const hasRole = (role: string) => {
        return roles.value.includes(role);
    };

    const filterNavItems = (navItems: NavItem[]) => {
        return computed(() => {
            return navItems.filter((item) => {
                // Verificar si el elemento actual tiene permiso
                const hasItemPermission = !item.permission || hasPermission(item.permission);
                
                // Si el elemento tiene hijos (submenús)
                if (item.children && item.children.length > 0) {
                    // Filtrar los hijos según permisos
                    const filteredChildren = item.children.filter(child => 
                        !child.permission || hasPermission(child.permission)
                    );
                    
                    // Actualizar los hijos del elemento
                    item.children = filteredChildren;
                    
                    // Mostrar el elemento padre si tiene permiso o si tiene al menos un hijo con permiso
                    return hasItemPermission || filteredChildren.length > 0;
                }
                
                // Para elementos sin hijos, solo verificar su propio permiso
                return hasItemPermission;
            });
        });
    };

    return {
        permissions,
        roles,
        hasPermission,
        hasRole,
        filterNavItems,
    };
}