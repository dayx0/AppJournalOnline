import type { ReactNode } from 'react';
import ResponsiveLayout from '@/layouts/responsive-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profil',
        href: '/profil',
    },
];

/**
 * Layout responsif untuk halaman Profil Menu.
 * Detail switching mobile/desktop ada di `ResponsiveLayout`.
 */
export default function ProfilLayout({ children }: { children: ReactNode }) {
    return (
        <ResponsiveLayout breadcrumbs={breadcrumbs}>
            {children}
        </ResponsiveLayout>
    );
}
