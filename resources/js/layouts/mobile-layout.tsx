import { Link, usePage } from '@inertiajs/react';
import { Bell, BookOpen } from 'lucide-react';
import type { ReactNode } from 'react';
import BottomNav from '@/components/mobile/bottom-nav';
import { CountBadge } from '@/components/unread-dot';

/**
 * Layout mobile ala mockup "Phone - Beranda": header brand,
 * konten, dan bottom tab dalam satu kolom full-screen.
 *
 * Layout ini hanya dipakai di layar kecil (< 768px).
 * Untuk layar besar, BerandaLayout memakai AppLayout (sidebar).
 * Responsivitasnya ditangani di `BerandaLayout`, bukan di sini.
 *
 * Status bar (jam 9:41, sinyal, wifi, baterai) dari file desain
 * tidak disertakan karena itu chrome milik tools desain, bukan aplikasi.
 */
export default function MobileLayout({ children }: { children: ReactNode }) {
    const { auth, unreadCount } = usePage<any>().props;
    const isMpk = auth?.user?.role === 'mpk';
    const bellHref = isMpk ? '/mpk/notifikasi' : '/notifikasi';
    const badge = (unreadCount ?? 0) as number;

    return (
        <div className="flex min-h-screen flex-col bg-[#F6F8FC] font-[Inter,system-ui,sans-serif]">
            {/* App Header */}
            <header className="flex shrink-0 items-center justify-between px-5 py-2">
                <div className="flex items-center gap-2">
                    <BookOpen className="h-5 w-5 text-[#2563EB]" />
                    <span className="text-[15px] font-bold whitespace-nowrap text-[#1A1D26]">
                        AppJurnalOnline
                    </span>
                </div>
                <Link
                    href={bellHref}
                    aria-label="Notifikasi"
                    className="relative flex h-9 w-9 items-center justify-center rounded-full bg-white"
                >
                    <Bell className="h-[18px] w-[18px] text-[#1A1D26]" />
                    {!!badge && badge > 0 && (
                        <span className="absolute -top-1 -right-1">
                            <CountBadge count={badge} />
                        </span>
                    )}
                </Link>
            </header>

            {/* Konten halaman.
                pb-24 sebagai spacer agar konten terbawah
                tidak tertutup bottom navbar yang fixed. */}
            <main className="flex-1 pb-24">{children}</main>

            <BottomNav />
        </div>
    );
}
