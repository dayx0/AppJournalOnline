import type { ReactNode } from 'react';
import ResponsiveLayout from '@/layouts/responsive-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Notifikasi',
        href: '/notifikasi',
    },
];

/**
 * Layout responsif untuk halaman Notifikasi.
 * Detail switching mobile/desktop ada di `ResponsiveLayout`.
 */
export default function NotifikasiLayout({
    children,
}: {
    children: ReactNode;
}) {
    return (
        <ResponsiveLayout breadcrumbs={breadcrumbs}>
            {children}
        </ResponsiveLayout>
    );
}
