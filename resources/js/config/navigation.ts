import { Folder, LayoutGrid, Settings, FileText, BarChart, Wallet, MessageCircleReply, Users, Receipt, LineChart, Network, Cog, DollarSign, Award, ChartBar, CreditCard } from 'lucide-vue-next';
import { type NavItem } from '@/types';

export const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    // {
    //     title: 'Profile',
    //     href: '/profile',
    //     icon: User,
    //     permission: 'ver_usuarios'
    // },
    {
        title: 'Red',
        href: '/network',
        icon: Users,
    },
    {
        title: 'Membresías',
        href: '/membership',
        icon: Award,
        children: [
            {
                title: 'Seleccionar Membresía',
                href: '/membership/select',
                icon: Award,
            },
            {
                title: 'Historial de Membresías',
                href: '/membership/history',
                icon: FileText,
            }
        ]
    },
    {
        title: 'Transacciones',
        href: '/transactions',
        icon: Receipt,
        children: [
            {
                title: 'Historial',
                href: '/transactions',
                icon: Receipt,
            },
            {
                title: 'Comisiones de Red',
                href: '/transactions/network-commissions',
                icon: Network,
            },
            {
                title: 'Reporte Consolidado',
                href: '/transactions/report',
                icon: LineChart,
            }
        ]
    },
    {
        title: 'Administración',
        href: '/admin',
        icon: Cog,
        children: [
            {
                title: 'Panel de Administración',
                href: '/admin/dashboard',
                icon: LayoutGrid,
            },
            {
                title: 'Gestión de Usuarios',
                href: '/admin/users',
                icon: Users,
            },
            {
                title: 'Gestión de Depósitos',
                href: '/admin/deposits',
                icon: DollarSign,
            },
            {
                title: 'Gestión de Retiros',
                href: '/admin/withdrawals',
                icon: Wallet,
            },
            {
                title: 'Membresías Pendientes',
                href: '/admin/memberships/pending',
                icon: Award,
            },
            {
                title: 'Gestión de Membresías',
                href: '/admin/memberships',
                icon: Users,
            },
            {
                title: 'Estadísticas de Membresías',
                href: '/admin/memberships/stats',
                icon: ChartBar,
            },
            {
                title: 'Pagos de Membresías',
                href: '/admin/memberships/payments',
                icon: CreditCard,
            },
            {
                title: 'Configuración de Depósitos',
                href: '/admin/settings/deposits',
                icon: DollarSign,
            },
            {
                title: 'Configuración de Retiros',
                href: '/admin/settings/withdrawals',
                icon: Wallet,
            },
            // Aquí se pueden agregar más opciones de administración en el futuro
        ]
    },
    {
        title: 'Configuración',
        href: '/settings',
        icon: Settings,
        children: [
            {
                title: 'General',
                href: '/settings/profile',
                icon: Settings,
            },
            {
                title: 'Billetera',
                href: '/settings/wallet',
                icon: Wallet,
            },
            {
                title: 'WhatsApp',
                href: '/settings/whatsapp',
                icon: MessageCircleReply,
            }
        ]
    },
    {
        title: 'Depósito',
        href: '/deposit',
        icon: DollarSign,
        children: [
            {
                title: 'Depositar',
                href: '/deposit',
                icon: DollarSign,
            },
            {
                title: 'Historial de depósitos',
                href: '/deposit/history',
                icon: FileText,
            }
        ]
    },
    {
        title: 'Retiro',
        href: '/withdrawal',
        icon: Wallet,
        //permission: 'module-withdrawal',
        children: [
            {
                title: 'Retirar',
                href: '/withdrawal/create',
                icon: Wallet,
               // permission: 'send-withdrawal'
            },
            {
                title: 'Histórico de retiros',
                href: '/withdrawal/history',
                icon: FileText,
                //permission: 'historical-withdrawal'
            }
        ]
    },
    {
        title: 'Reports',
        href: '/reports',
        icon: BarChart,
        permission: 'ver-reportes',
        children: [
            {
                title: 'Sales',
                href: '/reports/sales',
                icon: FileText,
            },
            {
                title: 'Analytics',
                href: '/reports/analytics',
                icon: BarChart,
            }
        ]
    }
];

export const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    }
];
