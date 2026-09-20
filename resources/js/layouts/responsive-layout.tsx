import type { ReactNode } from 'react';
import { useIsMobile } from '@/hooks/use-mobile';
import AppLayout from '@/layouts/app-layout';
import MobileLayout from '@/layouts/mobile-layout';
import type { BreadcrumbItem } from '@/types';

/**
 * Layout responsif bersama.
 *
 * - Layar kecil (< 768px): MobileLayout (header brand + bottom tab).
 * - Layar besar: AppLayout (sidebar + breadcrumb).
 */
export default function ResponsiveLayout({
    children,
    breadcrumbs = [],
}: {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}) {
    const isMobile = useIsMobile();

    if (isMobile) {
        return <MobileLayout>{children}</MobileLayout>;
    }

    return <AppLayout breadcrumbs={breadcrumbs}>{children}</AppLayout>;
}
