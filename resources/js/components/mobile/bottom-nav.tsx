import { Link, usePage } from '@inertiajs/react';
import { BookOpen, House, Plus, User, Users } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

export type BottomTabKey = 'beranda' | 'jurnal' | 'absensi' | 'profil';

interface BottomTab {
    key: BottomTabKey;
    href: string;
    label: string;
    icon: LucideIcon;
}

const LEFT_TABS: BottomTab[] = [
    { key: 'beranda', href: '/dashboard', label: 'Beranda', icon: House },
    { key: 'jurnal', href: '/jurnal', label: 'Jurnal', icon: BookOpen },
];

const RIGHT_TABS: BottomTab[] = [
    /*
     * Modul absensi mandiri belum ada di project ini.
     * Sementara arahkan ke daftar jurnal yang memuat
     * rekap absensi (H/I/S/A) per jurnal.
     */
    { key: 'absensi', href: '/jurnal', label: 'Absensi', icon: Users },
    { key: 'profil', href: '/profil', label: 'Profil', icon: User },
];

export function resolveBottomTab(url: string): BottomTabKey {
    if (url.startsWith('/jurnal')) {
        return 'jurnal';
    }

    if (url.startsWith('/settings') || url.startsWith('/profil')) {
        return 'profil';
    }

    return 'beranda';
}

function BottomTabLink({
    tab,
    isActive,
}: {
    tab: BottomTab;
    isActive: boolean;
}) {
    const Icon = tab.icon;

    return (
        <Link
            href={tab.href}
            className="flex flex-1 flex-col items-center gap-3"
            aria-current={isActive ? 'page' : undefined}
        >
            <Icon
                className={`h-5 w-5 ${isActive ? 'text-[#2563EB]' : 'text-[#9CA3AF]'}`}
                strokeWidth={isActive ? 2.5 : 2}
            />
            <span
                className={`text-[11px] whitespace-nowrap ${
                    isActive
                        ? 'font-bold text-[#2563EB]'
                        : 'font-normal text-[#9CA3AF]'
                }`}
            >
                {tab.label}
            </span>
        </Link>
    );
}

/**
 * Bottom navbar mobile ala mockup (Beranda, Jurnal, +, Absensi, Profil).
 *
 * Tab aktif dibaca otomatis dari URL, tapi bisa dioverride
 * lewat prop `active` bila dibutuhkan.
 */
export default function BottomNav({ active }: { active?: BottomTabKey }) {
    const { url } = usePage();
    const activeTab = active ?? resolveBottomTab(url);

    return (
        <nav className="fixed inset-x-0 bottom-0 z-20 flex shrink-0 items-center justify-between gap-1 border-t border-[#E5E9F2] bg-white px-5 pt-2.5 pb-[calc(18px+env(safe-area-inset-bottom))]">
            {LEFT_TABS.map((tab) => (
                <BottomTabLink
                    key={tab.key}
                    tab={tab}
                    isActive={activeTab === tab.key}
                />
            ))}
            <Link
                href="/jurnal/create"
                aria-label="Tambah Jurnal"
                className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#2563EB] shadow-[0_8px_20px_-6px_rgba(37,99,235,0.6)]"
            >
                <Plus className="h-[22px] w-[22px] text-white" />
            </Link>
            {RIGHT_TABS.map((tab) => (
                <BottomTabLink
                    key={tab.key}
                    tab={tab}
                    isActive={activeTab === tab.key}
                />
            ))}
        </nav>
    );
}
