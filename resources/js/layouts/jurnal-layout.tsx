import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import ResponsiveLayout from '@/layouts/responsive-layout';
import type { BreadcrumbItem } from '@/types';

function resolveBreadcrumbs(url: string): BreadcrumbItem[] {
    const base: BreadcrumbItem[] = [
        {
            title: 'Jurnal',
            href: '/jurnal',
        },
    ];

    if (url.endsWith('/edit')) {
        return [
            ...base,
            {
                title: 'Edit Jurnal',
                href: url,
            },
        ];
    }

    if (url.endsWith('/absensi')) {
        return [
            ...base,
            {
                title: 'Detail Jurnal',
                href: url.replace(/\/absensi$/, ''),
            },
            {
                title: 'Kelola Absensi',
                href: url,
            },
        ];
    }

    if (/^\/jurnal\/[^/]+$/.test(url)) {
        return [
            ...base,
            {
                title: 'Detail Jurnal',
                href: url,
            },
        ];
    }

    return base;
}

/**
 * Layout responsif untuk halaman-halaman Jurnal.
 * Detail switching mobile/desktop ada di `ResponsiveLayout`.
 */
export default function JurnalLayout({ children }: { children: ReactNode }) {
    const { url } = usePage();

    return (
        <ResponsiveLayout breadcrumbs={resolveBreadcrumbs(url)}>
            {children}
        </ResponsiveLayout>
    );
}
