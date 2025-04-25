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
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@/components/ui/tabs';
import { 
    User as UserIcon,
    Mail,
    Calendar,
    Shield,
    CreditCard,
    ArrowLeft,
    Phone,
    MapPin,
    Users,
    Building,
    Network,
    Clock,
    CreditCard as CreditCardIcon,
    DollarSign
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { countries } from '@/models/countries.js'; 

interface User {
    id: number;
    name: string;
    email: string;
    created_at: string;
    membership_id: number | null;
    membership?: {
        id: number;
        nombre: string;
        precio: number;
        porcentaje_rendimiento: number;
        porcentaje_comision_sponsor: number;
        comision_directa: number;
    };
    capital_balance: number | string;
    earnings_balance: number | string;
    network_balance: number | string;
    country_code: string;
    phone: string;
    whatsapp_number: string;
    sponsor_id: number | null;
    sponsor?: {
        id: number;
        name: string;
        email: string;
    };
    referidos?: User[];
}

interface Deposit {
    id: number;
    amount: number;
    status: string;
    created_at: string;
}

interface Withdrawal {
    id: number;
    amount: number;
    status: string;
    created_at: string;
}

interface Transaction {
    id: number;
    amount: number;
    type: string;
    description: string;
    created_at: string;
}

interface MembershipHistory {
    id: number;
    membresia_id: number;
    membresia: {
        nombre: string;
    };
    precio_pagado: number;
    status: string;
    activated_at: string;
    expires_at: string | null;
}

const props = defineProps<{
    user: User;
    deposits: Deposit[];
    withdrawals: Withdrawal[];
    transactions: Transaction[];
    membershipHistory: MembershipHistory[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Panel de Administración',
        href: '/admin/dashboard',
    },
    {
        title: 'Usuarios',
        href: '/admin/users',
    },
    {
        title: props.user.name,
        href: `/admin/users/${props.user.id}`,
    }
];

const formatCountryCode = (code: string): string => { 
  const country = countries.find(c => c.code === code);
  return country ? country.name : code; 
};

// Formateador de fecha
const formatDate = (date: string | null) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Formateador de moneda
const formatCurrency = (amount: number | string | null | undefined) => {
    if (amount === null || amount === undefined) return '$0.00';
    
    // Convertir a número si es string
    const numericAmount = typeof amount === 'string' ? parseFloat(amount) : amount;
    
    // Verificar si es un número válido
    if (isNaN(numericAmount)) return '$0.00';
    
    return new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: 'USD'
    }).format(numericAmount);
};

// Calcular el balance total correctamente
const getTotalBalance = computed(() => {
    // Convertir a número si son strings y usar 0 si son null/undefined
    const capital = typeof props.user.capital_balance === 'string' 
        ? parseFloat(props.user.capital_balance) || 0 
        : props.user.capital_balance || 0;
    
    const earnings = typeof props.user.earnings_balance === 'string' 
        ? parseFloat(props.user.earnings_balance) || 0 
        : props.user.earnings_balance || 0;
    
    const network = typeof props.user.network_balance === 'string' 
        ? parseFloat(props.user.network_balance) || 0 
        : props.user.network_balance || 0;
    
    return capital + earnings + network;
});

// Función para formatear el número de teléfono con el código de país
const formatPhoneWithCountryCode = (countryCode: string, phone: string) => {
    if (!countryCode || !phone) return 'No especificado';
    return `${countryCode} ${phone}`;
};

// Estado para la pestaña activa
const activeTab = ref('referidos');
</script>

<template>
    <Head :title="`Usuario: ${user.name}`" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto p-4">
            <div class="mb-4">
                <Button variant="outline" size="sm" :as="'a'" :href="route('admin.users.all')">
                    <ArrowLeft class="h-4 w-4 mr-2" />
                    Volver a la lista
                </Button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Información del usuario -->
                <Card class="md:col-span-1">
                    <CardHeader>
                        <CardTitle>Información de usuario</CardTitle>
                        <CardDescription>
                            Datos básicos del usuario
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-col space-y-4">
                            <div class="flex items-center justify-center mb-6">
                                <div class="w-24 h-24 rounded-full bg-primary/10 flex items-center justify-center">
                                    <UserIcon class="h-12 w-12 text-primary" />
                                </div>
                            </div>
                            
                            <div>
                                <h3 class="text-lg font-medium">{{ user.name }}</h3>
                                <div class="flex items-center text-gray-600">
                                    <Mail class="h-4 w-4 mr-2" />
                                    {{ user.email }}
                                </div>
                            </div>
                            
                            <div class="pt-2">
                                <p class="text-sm text-gray-500 mb-1">Fecha de registro</p>
                                <div class="flex items-center">
                                    <Calendar class="h-4 w-4 mr-2 text-gray-600" />
                                    {{ formatDate(user.created_at) }}
                                </div>
                            </div>
                            
                            <div class="pt-2">
                                <p class="text-sm text-gray-500 mb-1">País</p>
                                <div class="flex items-center">
                                    <MapPin class="h-4 w-4 mr-2 text-gray-600" />
                                    {{formatCountryCode(user.country_code) || 'No especificado'}}
                                </div>
                            </div>
                                                        
                            <div class="pt-2">
                                <p class="text-sm text-gray-500 mb-1">WhatsApp</p>
                                <div class="flex items-center">
                                    <Phone class="h-4 w-4 mr-2 text-gray-600" />
                                    {{  formatPhoneWithCountryCode(user.country_code, user.whatsapp_number) || 'No especificado' }}
                                </div>
                            </div>
                            
                            <div class="pt-2">
                                <p class="text-sm text-gray-500 mb-1">Patrocinador</p>
                                <div class="flex items-center" v-if="user.sponsor">
                                    <Users class="h-4 w-4 mr-2 text-gray-600" />
                                    <a :href="route('admin.users.show', { id: user.sponsor.id })" class="text-primary hover:underline">
                                        {{ user.sponsor.name }}
                                    </a>
                                </div>
                                <div class="text-gray-600" v-else>
                                    <span>Sin patrocinador</span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                
                <!-- Información financiera y de membresía -->
                <div class="md:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Membresía</CardTitle>
                            <CardDescription>
                                Información de membresía actual
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="user.membership" class="bg-blue-50 p-4 rounded-lg flex items-start">
                                <Shield class="h-10 w-10 mr-4 text-blue-600" />
                                <div>
                                    <h3 class="text-lg font-medium text-blue-800">{{ user.membership.nombre }}</h3>
                                    <p class="text-blue-600 mb-2">{{ formatCurrency(user.membership.precio) }}</p>
                                    <div class="flex flex-wrap gap-3">
                                        <Badge variant="outline" class="bg-white">
                                            Rendimiento: {{ user.membership.porcentaje_rendimiento }}%
                                        </Badge>
                                        <Badge variant="outline" class="bg-white">
                                            Comisión patrocinador: {{ user.membership.porcentaje_comision_sponsor }}%
                                        </Badge>
                                        <Badge variant="outline" class="bg-white">
                                            Comisión directa: {{ formatCurrency(user.membership.comision_directa) }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="bg-gray-50 p-4 rounded-lg flex items-center">
                                <Building class="h-10 w-10 mr-4 text-gray-400" />
                                <div>
                                    <h3 class="text-lg font-medium text-gray-700">Sin membresía activa</h3>
                                    <p class="text-gray-500">El usuario no tiene una membresía activa actualmente</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader>
                            <CardTitle>Balances</CardTitle>
                            <CardDescription>
                                Estados financieros del usuario
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <h3 class="text-sm font-medium text-blue-700 mb-1">Balance de Capital</h3>
                                    <p class="text-2xl font-bold text-blue-900">{{ formatCurrency(user.capital_balance) }}</p>
                                </div>
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <h3 class="text-sm font-medium text-green-700 mb-1">Balance de Ganancias</h3>
                                    <p class="text-2xl font-bold text-green-900">{{ formatCurrency(user.earnings_balance) }}</p>
                                </div>
                                <div class="bg-purple-50 p-4 rounded-lg">
                                    <h3 class="text-sm font-medium text-purple-700 mb-1">Balance de Red</h3>
                                    <p class="text-2xl font-bold text-purple-900">{{ formatCurrency(user.network_balance) }}</p>
                                </div>
                                <div class="md:col-span-3 bg-gray-50 p-4 rounded-lg">
                                    <h3 class="text-sm font-medium text-gray-700 mb-1">Balance Total</h3>
                                    <p class="text-2xl font-bold text-gray-900">
                                        {{ formatCurrency(getTotalBalance) }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    
                    <!-- Referidos del usuario -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Red de Referidos</CardTitle>
                            <CardDescription>
                                Usuarios directamente referidos por este usuario
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableCaption>Lista de usuarios referidos</TableCaption>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>ID</TableHead>
                                        <TableHead>Nombre</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Fecha</TableHead>
                                        <TableHead>Membresía</TableHead>
                                        <TableHead>Acciones</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="referido in user.referidos" :key="referido.id">
                                        <TableCell>{{ referido.id }}</TableCell>
                                        <TableCell>
                                            <div class="flex items-center">
                                                <UserIcon class="h-4 w-4 mr-2 text-gray-500" />
                                                {{ referido.name }}
                                            </div>
                                        </TableCell>
                                        <TableCell>{{ referido.email }}</TableCell>
                                        <TableCell>{{ formatDate(referido.created_at) }}</TableCell>
                                        <TableCell>
                                            <Badge v-if="referido.membership" variant="outline" class="bg-blue-50 text-blue-700 border-blue-200">
                                                {{ referido.membership.nombre }}
                                            </Badge>
                                            <Badge v-else variant="outline" class="bg-gray-50 text-gray-500">
                                                Sin membresía
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <Button variant="ghost" size="sm" :as="'a'" :href="route('admin.users.show', { id: referido.id })">
                                                Ver perfil
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="!user.referidos || user.referidos.length === 0">
                                        <TableCell colspan="6" class="text-center py-8 text-gray-500">
                                            Este usuario no tiene referidos
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardHeader>
                            <CardTitle>Historial</CardTitle>
                            <CardDescription>
                                Actividad del usuario en la plataforma
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Tabs defaultValue="deposits">
                                <TabsList class="grid w-full grid-cols-4">
                                    <TabsTrigger value="deposits">
                                        <DollarSign class="h-4 w-4 mr-1" />
                                        Depósitos
                                    </TabsTrigger>
                                    <TabsTrigger value="withdrawals">
                                        <CreditCardIcon class="h-4 w-4 mr-1" />
                                        Retiros
                                    </TabsTrigger>
                                    <TabsTrigger value="transactions">
                                        <Network class="h-4 w-4 mr-1" />
                                        Transacciones
                                    </TabsTrigger>
                                    <TabsTrigger value="memberships">
                                        <Shield class="h-4 w-4 mr-1" />
                                        Membresías
                                    </TabsTrigger>
                                </TabsList>
                                
                                <!-- Depósitos -->
                                <TabsContent value="deposits" class="mt-2">
                                    <Table>
                                        <TableCaption>Historial de depósitos del usuario</TableCaption>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>ID</TableHead>
                                                <TableHead>Monto</TableHead>
                                                <TableHead>Estado</TableHead>
                                                <TableHead>Fecha</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="deposit in deposits" :key="deposit.id">
                                                <TableCell>{{ deposit.id }}</TableCell>
                                                <TableCell>{{ formatCurrency(deposit.amount) }}</TableCell>
                                                <TableCell>
                                                    <Badge 
                                                        :variant="deposit.status === 'aprobado' ? 'default' : 'outline'" 
                                                        :class="deposit.status === 'aprobado' ? 'bg-green-100 text-green-800 border-green-200' : 'bg-yellow-100 text-yellow-800 border-yellow-200'"
                                                    >
                                                        {{ deposit.status }}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>{{ formatDate(deposit.created_at) }}</TableCell>
                                            </TableRow>
                                            <TableRow v-if="deposits.length === 0">
                                                <TableCell colspan="4" class="text-center py-8 text-gray-500">
                                                    No hay depósitos registrados
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </TabsContent>
                                
                                <!-- Retiros -->
                                <TabsContent value="withdrawals" class="mt-2">
                                    <Table>
                                        <TableCaption>Historial de retiros del usuario</TableCaption>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>ID</TableHead>
                                                <TableHead>Monto</TableHead>
                                                <TableHead>Estado</TableHead>
                                                <TableHead>Fecha</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="withdrawal in withdrawals" :key="withdrawal.id">
                                                <TableCell>{{ withdrawal.id }}</TableCell>
                                                <TableCell>{{ formatCurrency(withdrawal.amount) }}</TableCell>
                                                <TableCell>
                                                    <Badge 
                                                        :variant="withdrawal.status === 'aprobado' ? 'default' : 'outline'" 
                                                        :class="withdrawal.status === 'aprobado' ? 'bg-green-100 text-green-800 border-green-200' : 'bg-yellow-100 text-yellow-800 border-yellow-200'"
                                                    >
                                                        {{ withdrawal.status }}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>{{ formatDate(withdrawal.created_at) }}</TableCell>
                                            </TableRow>
                                            <TableRow v-if="withdrawals.length === 0">
                                                <TableCell colspan="4" class="text-center py-8 text-gray-500">
                                                    No hay retiros registrados
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </TabsContent>
                                
                                <!-- Transacciones -->
                                <TabsContent value="transactions" class="mt-2">
                                    <Table>
                                        <TableCaption>Historial de transacciones del usuario</TableCaption>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>ID</TableHead>
                                                <TableHead>Monto</TableHead>
                                                <TableHead>Tipo</TableHead>
                                                <TableHead>Descripción</TableHead>
                                                <TableHead>Fecha</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="transaction in transactions" :key="transaction.id">
                                                <TableCell>{{ transaction.id }}</TableCell>
                                                <TableCell>{{ formatCurrency(transaction.amount) }}</TableCell>
                                                <TableCell>
                                                    <Badge variant="outline" class="bg-blue-100 text-blue-800 border-blue-200">
                                                        {{ transaction.type }}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>{{ transaction.description }}</TableCell>
                                                <TableCell>{{ formatDate(transaction.created_at) }}</TableCell>
                                            </TableRow>
                                            <TableRow v-if="transactions.length === 0">
                                                <TableCell colspan="5" class="text-center py-8 text-gray-500">
                                                    No hay transacciones registradas
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </TabsContent>
                                
                                <!-- Historial de Membresías -->
                                <TabsContent value="memberships" class="mt-2">
                                    <Table>
                                        <TableCaption>Historial de membresías del usuario</TableCaption>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>ID</TableHead>
                                                <TableHead>Membresía</TableHead>
                                                <TableHead>Precio Pagado</TableHead>
                                                <TableHead>Estado</TableHead>
                                                <TableHead>Activación</TableHead>
                                                <TableHead>Expiración</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="membership in membershipHistory" :key="membership.id">
                                                <TableCell>{{ membership.id }}</TableCell>
                                                <TableCell>{{ membership.membresia.nombre }}</TableCell>
                                                <TableCell>{{ formatCurrency(membership.precio_pagado) }}</TableCell>
                                                <TableCell>
                                                    <Badge 
                                                        :variant="membership.status === 'activa' ? 'default' : 'outline'" 
                                                        :class="membership.status === 'activa' ? 'bg-green-100 text-green-800 border-green-200' : 'bg-gray-100 text-gray-800 border-gray-200'"
                                                    >
                                                        {{ membership.status }}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>{{ formatDate(membership.activated_at) }}</TableCell>
                                                <TableCell>{{ membership.expires_at ? formatDate(membership.expires_at) : 'Sin expiración' }}</TableCell>
                                            </TableRow>
                                            <TableRow v-if="membershipHistory.length === 0">
                                                <TableCell colspan="6" class="text-center py-8 text-gray-500">
                                                    No hay historial de membresías registrado
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </TabsContent>
                            </Tabs>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template> 