import type { ReactNode } from 'react';
import ResponsiveLayout from '@/layouts/responsive-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Beranda',
        href: '/dashboard',
    },
];

/**
 * Layout responsif untuk halaman Beranda.
 * Detail switching mobile/desktop ada di `ResponsiveLayout`.
 */
export default function BerandaLayout({ children }: { children: ReactNode }) {
    return (
        <ResponsiveLayout breadcrumbs={breadcrumbs}>
            {children}
        </ResponsiveLayout>
    );
}
