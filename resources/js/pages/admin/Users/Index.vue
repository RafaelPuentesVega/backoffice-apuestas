<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { 
    Card, 
    CardContent, 
    CardDescription, 
    CardHeader, 
    CardTitle 
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { 
    User,
    Mail,
    Calendar,
    Shield,
    Eye
} from 'lucide-vue-next';

interface User {
    id: number;
    name: string;
    email: string;
    created_at: string;
    email_verified_at: string | null;
    membership_id: number | null;
    membership?: {
        id: number;
        nombre: string;
    };
}

defineProps<{
    users: {
        data: User[];
        meta: {
            current_page: number;
            from: number;
            last_page: number;
            links: Array<{
                url: string | null;
                label: string;
                active: boolean;
            }>;
            path: string;
            per_page: number;
            to: number;
            total: number;
        };
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Panel de Administración',
        href: '/admin/dashboard',
    },
    {
        title: 'Usuarios',
        href: '/admin/users',
    }
];

// Formateador de fecha
const formatDate = (date: string | null) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

// Transformar HTML en texto seguro
const sanitizeLabel = (label: string) => {
    // Escapar HTML especial para prevención de XSS
    if (label === '&laquo; Previous') return '« Anterior';
    if (label === 'Next &raquo;') return 'Siguiente »';
    return label;
};
</script>

<template>
    <Head title="Gestión de Usuarios" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto p-4">
            <Card>
                <CardHeader>
                    <CardTitle>Gestión de Usuarios</CardTitle>
                    <CardDescription>
                        Administre los usuarios registrados en el sistema
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableCaption>Lista de usuarios del sistema</TableCaption>
                        <TableHeader>
                            <TableRow>
                                <TableHead>ID</TableHead>
                                <TableHead>Nombre</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Fecha de registro</TableHead>
                                <TableHead>Membresía</TableHead>
                                <TableHead>Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="user in users.data" :key="user.id">
                                <TableCell>{{ user.id }}</TableCell>
                                <TableCell>
                                    <div class="flex items-center">
                                        <User class="h-4 w-4 mr-2 text-gray-500" />
                                        {{ user.name }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center">
                                        <Mail class="h-4 w-4 mr-2 text-gray-500" />
                                        {{ user.email }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center">
                                        <Calendar class="h-4 w-4 mr-2 text-gray-500" />
                                        {{ formatDate(user.created_at) }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge v-if="user.membership" variant="outline" class="bg-blue-50 text-blue-700 border-blue-200">
                                        <Shield class="h-3 w-3 mr-1" />
                                        {{ user.membership.nombre }}
                                    </Badge>
                                    <Badge v-else variant="outline" class="bg-gray-50 text-gray-700 border-gray-200">
                                        Sin membresía
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Button variant="ghost" size="sm" :as="'a'" :href="route('admin.users.show', { id: user.id })">
                                        <Eye class="h-4 w-4 mr-1" />
                                        Ver detalles
                                    </Button>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="users.data.length === 0">
                                <TableCell colspan="6" class="text-center py-8 text-gray-500">
                                    No hay usuarios registrados en el sistema
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    
                    <!-- Paginación -->
                    <div class="mt-6 flex items-center justify-between">
                        <div class="text-sm text-gray-600" v-if="users.meta && users.meta.from && users.meta.to">
                            Mostrando {{ users.meta.from }} a {{ users.meta.to }} de {{ users.meta.total }} registros
                        </div>
                        <div class="text-sm text-gray-600" v-else>
                            No hay registros para mostrar
                        </div>
                        <div class="flex space-x-2" v-if="users.meta && users.meta.links">
                            <Button 
                                v-for="link in users.meta.links" 
                                :key="link.label"
                                :disabled="!link.url || link.active"
                                :variant="link.active ? 'default' : 'outline'"
                                size="sm"
                                :as="link.url ? 'a' : 'button'"
                                :href="link.url"
                            >
                                {{ sanitizeLabel(link.label) }}
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template> 