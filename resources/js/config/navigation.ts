import { Folder, LayoutGrid, Settings, User, FileText, BarChart, Wallet } from 'lucide-vue-next';
import { type NavItem } from '@/types';

export const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Profile',
        href: '/profile',
        icon: User,
        permission: 'ver_usuarios'
    },
    {
        title: 'Settings',
        href: '/settings',
        icon: Settings,
        children: [
            {
                title: 'General',
                href: '/settings/general',
                icon: Settings,
            },
            {
                title: 'Security',
                href: '/settings/security',
                icon: Settings,
            },
            {
                title: 'Notifications',
                href: '/settings/notifications',
                icon: Settings,
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
