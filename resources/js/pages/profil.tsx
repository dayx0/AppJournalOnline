import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    BookOpen,
    Bookmark,
    GraduationCap,
    House,
    LayoutGrid,
    LogOut,
    Settings,
    User,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { logout } from '@/routes';
import type { Auth } from '@/types';

interface MenuItem {
    key: string;
    label: string;
    href: string;
    icon: LucideIcon;
    adminOnly?: boolean;
    match: (url: string) => boolean;
}

const MENU_ITEMS: MenuItem[] = [
    {
        key: 'beranda',
        label: 'Beranda',
        href: '/dashboard',
        icon: House,
        match: (url) => url === '/dashboard' || url === '/',
    },
    {
        key: 'jurnal',
        label: 'Jurnal',
        href: '/jurnal',
        icon: BookOpen,
        match: (url) => url.startsWith('/jurnal'),
    },
    {
        key: 'absensi',
        label: 'Absensi Siswa',
        href: '/jurnal',
        icon: Users,
        // Tidak pernah aktif: belum ada modul absensi mandiri,
        // tautan mengarah ke daftar jurnal (rekap H/I/S/A).
        match: () => false,
    },
    {
        key: 'kelas',
        label: 'Kelas',
        href: '/admin/kelas',
        icon: LayoutGrid,
        adminOnly: true,
        match: (url) => url.startsWith('/admin/kelas'),
    },
    {
        key: 'mapel',
        label: 'Mata Pelajaran',
        href: '/admin/mapel',
        icon: Bookmark,
        adminOnly: true,
        match: (url) => url.startsWith('/admin/mapel'),
    },
    {
        key: 'profil',
        label: 'Profil',
        href: '/settings/profile',
        icon: User,
        match: (url) => url.startsWith('/settings/profile'),
    },
    {
        key: 'pengaturan',
        label: 'Pengaturan',
        href: '/settings/appearance',
        icon: Settings,
        match: (url) =>
            url.startsWith('/settings') && !url.startsWith('/settings/profile'),
    },
];

function getInitials(name: string): string {
    const words = name.trim().split(/\s+/).slice(0, 2);

    return words.map((word) => word.charAt(0).toUpperCase()).join('');
}

export default function Profil() {
    const page = usePage();
    const { auth } = page.props as unknown as { auth: Auth };
    const { url } = page;
    const cleanup = useMobileNavigation();
    const user = auth.user;
    const isAdmin = user.role === 'admin';
    const roleLabel = isAdmin ? 'Admin' : 'Guru';

    function handleLogout() {
        if (!confirm('Yakin ingin keluar dari aplikasi?')) {
            return;
        }

        cleanup();
        router.flushAll();
    }

    return (
        <>
            <Head title="Profil" />

            <div className="mx-auto w-full max-w-2xl font-[Inter,system-ui,sans-serif] lg:max-w-4xl">
                {/* Hero */}
                <section className="flex items-center gap-3.5 bg-[#1E3A8A] p-5 lg:rounded-2xl lg:p-6">
                    <span className="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-[#3B82F6] text-xl font-bold whitespace-nowrap text-white">
                        {getInitials(user.name)}
                    </span>
                    <div className="flex min-w-0 flex-1 flex-col gap-[11px]">
                        <p className="truncate text-[17px] font-bold text-white lg:text-xl">
                            {user.name}
                        </p>
                        <p className="truncate text-xs font-normal text-[#BFDBFE]">
                            {roleLabel} &bull; AppJurnalOnline
                        </p>
                    </div>
                </section>

                {/* Menu */}
                <section className="grid grid-cols-1 gap-[11px] bg-[#F6F8FC] p-[22px] lg:grid-cols-2 lg:rounded-2xl lg:bg-[#F6F8FC] lg:p-6">
                    {MENU_ITEMS.filter(
                        (item) => !item.adminOnly || isAdmin,
                    ).map((item) => {
                        const Icon = item.icon;
                        const isActive = item.match(url);

                        return (
                            <Link
                                key={item.key}
                                href={item.href}
                                aria-current={isActive ? 'page' : undefined}
                                className={`flex items-center gap-3 rounded-xl p-3 ${
                                    isActive ? 'bg-[#EFF4FF]' : 'bg-white'
                                }`}
                            >
                                <Icon
                                    className={`h-5 w-5 shrink-0 ${isActive ? 'text-[#2563EB]' : 'text-[#6B7280]'}`}
                                />
                                <span
                                    className={`text-sm whitespace-nowrap ${
                                        isActive
                                            ? 'font-bold text-[#2563EB]'
                                            : 'font-medium text-[#1A1D26]'
                                    }`}
                                >
                                    {item.label}
                                </span>
                            </Link>
                        );
                    })}

                    {/*
                     * Belum ada modul/tabel siswa di database,
                     * jadi menu ini nonaktif sampai tersedia.
                     */}
                    <div
                        aria-disabled="true"
                        title="Modul siswa belum tersedia"
                        className="flex items-center gap-3 rounded-xl bg-white p-3 opacity-60"
                    >
                        <GraduationCap className="h-5 w-5 shrink-0 text-[#6B7280]" />
                        <span className="min-w-0 flex-1 truncate text-sm font-medium text-[#1A1D26]">
                            Siswa
                        </span>
                        <span className="shrink-0 rounded-full bg-[#EFF4FF] px-2.5 py-1 text-[10px] font-semibold whitespace-nowrap text-[#2563EB]">
                            Segera
                        </span>
                    </div>

                    <Link
                        href={logout()}
                        as="button"
                        onClick={handleLogout}
                        className="flex items-center gap-3 rounded-xl bg-white p-3"
                    >
                        <LogOut className="h-5 w-5 shrink-0 text-[#DC2626]" />
                        <span className="text-sm font-medium whitespace-nowrap text-[#DC2626]">
                            Keluar
                        </span>
                    </Link>
                </section>

                {/* Footer */}
                <footer className="flex flex-col items-center gap-[11px] bg-[#F6F8FC] px-[18px] pt-2.5 pb-[26px] lg:rounded-2xl lg:bg-transparent lg:pb-6">
                    <p className="text-xs font-bold whitespace-nowrap text-[#2563EB]">
                        AppJurnalOnline
                    </p>
                    <p className="text-[11px] font-normal whitespace-nowrap text-[#9CA3AF]">
                        v1.0.0
                    </p>
                </footer>
            </div>
        </>
    );
}
