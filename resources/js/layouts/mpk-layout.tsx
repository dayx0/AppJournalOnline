import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AppSidebar } from '@/components/app-sidebar';
import { SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';

/**
 * Layout khusus role MPK: sidebar tetap ada, tapi TANPA kartu
 * konten + bar breadcrumb ala AppLayout. Konten menempel langsung
 * ke permukaan agar tidak terjadi bingkai ganda.
 */
export default function MpkLayout({ children }: { children: ReactNode }) {
    const isOpen = usePage().props.sidebarOpen;

    return (
        <SidebarProvider defaultOpen={isOpen}>
            <AppSidebar />
            <main className="flex min-h-svh min-w-0 flex-1 flex-col bg-[#F6F8FC]">
                <div className="flex h-12 shrink-0 items-center px-4">
                    <SidebarTrigger className="-ml-1" />
                </div>
                {children}
            </main>
        </SidebarProvider>
    );
}
