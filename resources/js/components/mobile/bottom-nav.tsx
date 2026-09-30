import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    CalendarDays,
    ClipboardCheck,
    House,
    Plus,
    User,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

export type BottomTabKey = 'beranda' | 'jurnal' | 'jadwal' | 'profil';

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

// MPK tidak boleh membuka /jurnal (403), jadi tabnya diganti Validasi.
const MPK_LEFT_TABS: BottomTab[] = [
    { key: 'beranda', href: '/dashboard', label: 'Beranda', icon: House },
    {
        key: 'jurnal',
        href: '/mpk/jurnal',
        label: 'Validasi',
        icon: ClipboardCheck,
    },
];

const RIGHT_TABS: BottomTab[] = [
    // Dulu tab ini "Absensi" tapi href-nya sama dengan Jurnal (duplikat).
    // Diganti Jadwal agar wali/guru bisa menemukan halaman jadwal di HP.
    // Untuk MPK disembunyikan (cek di BottomNav) karena MPK tidak punya akses.
    { key: 'jadwal', href: '/jadwal', label: 'Jadwal', icon: CalendarDays },
    { key: 'profil', href: '/profil', label: 'Profil', icon: User },
];

export function resolveBottomTab(url: string): BottomTabKey {
    if (url.startsWith('/jurnal') || url.startsWith('/mpk/jurnal')) {
        return 'jurnal';
    }

    if (url.startsWith('/jadwal')) {
        return 'jadwal';
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
 * Bottom navbar mobile (Beranda, Jurnal, +, Jadwal, Profil).
 *
 * Tab aktif dibaca otomatis dari URL, tapi bisa dioverride
 * lewat prop `active` bila dibutuhkan.
 */
export default function BottomNav({ active }: { active?: BottomTabKey }) {
    // Mengikuti pola app-sidebar: ambil lewat .props karena tipe Page
    // tidak mendeklarasikan auth secara langsung.
    const { url, props } = usePage<any>();
    const auth = props.auth as { user?: { role?: string } } | undefined;
    const isWali = (props.isWali ?? false) as boolean;
    const activeTab = active ?? resolveBottomTab(url);
    const isMpk = (auth?.user?.role ?? '') === 'mpk';
    const isAdmin = (auth?.user?.role ?? '') === 'admin';
    // MPK tidak punya akses /jadwal maupun /jurnal (403), jadi tab Jadwal
    // disembunyikan dan tombol tengah mengarah ke antrean validasi.
    // Guru non-wali juga tidak boleh membuka /jadwal (policy 403).
    const leftTabs = isMpk ? MPK_LEFT_TABS : LEFT_TABS;
    const rightTabs =
        isMpk || (!isAdmin && !isWali)
            ? RIGHT_TABS.filter((t) => t.key !== 'jadwal')
            : RIGHT_TABS;
    const fabHref = isMpk ? '/mpk/jurnal' : '/jurnal';
    const fabLabel = isMpk ? 'Validasi Jurnal' : 'Jurnal Hari Ini';

    return (
        <nav className="fixed inset-x-0 bottom-0 z-20 flex shrink-0 items-center justify-between gap-1 border-t border-[#E5E9F2] bg-white px-5 pt-2.5 pb-[calc(18px+env(safe-area-inset-bottom))]">
            {leftTabs.map((tab) => (
                <BottomTabLink
                    key={tab.key}
                    tab={tab}
                    isActive={activeTab === tab.key}
                />
            ))}
            <Link
                href={fabHref}
                aria-label={fabLabel}
                className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#2563EB] shadow-[0_8px_20px_-6px_rgba(37,99,235,0.6)]"
            >
                <Plus className="h-[22px] w-[22px] text-white" />
            </Link>
            {rightTabs.map((tab) => (
                <BottomTabLink
                    key={tab.key}
                    tab={tab}
                    isActive={activeTab === tab.key}
                />
            ))}
        </nav>
    );
}
