import type { Page, PageProps } from '@inertiajs/vue3';

declare module '@inertiajs/vue3' {
    interface PageProps {
        auth: {
            user: any;
            permissions: string[];
        };
    }
}