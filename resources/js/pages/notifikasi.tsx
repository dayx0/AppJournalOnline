import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Bell, BookOpen, CircleCheck, Pencil } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useMemo, useState } from 'react';
import EmptyState from '@/components/empty-state';
import FilterChips from '@/components/filter-chips';
import { UnreadPill } from '@/components/unread-dot';
import { formatRelatif, formatTanggal } from '@/lib/format';
import type { Journal } from '@/types';

interface Props {
    journals: Journal[];
    counts?: { pending: number; revisi: number; divalidasi: number };
    unreadKeys?: string[];
}

type NotifKind = 'validasi' | 'sistem' | 'revisi';
type NotifFilter = 'semua' | NotifKind;

interface NotifItem {
    id: string;
    kind: NotifKind;
    icon: LucideIcon;
    bubbleClass: string;
    iconClass: string;
    title: string;
    sub: string;
    iso: string;
    href: string;
    // Kunci status baca, cocok dengan `key` di tabel user_notifications.
    // Item sistem tidak punya key sehingga tidak ikut badge unread.
    key?: string;
}

const FILTERS: { key: NotifFilter; label: string }[] = [
    { key: 'semua', label: 'Semua' },
    { key: 'revisi', label: 'Revisi' },
    { key: 'validasi', label: 'Validasi' },
    { key: 'sistem', label: 'Sistem' },
];

function buildNotifications(journals: Journal[]): NotifItem[] {
    const items: NotifItem[] = [];

    journals.forEach((journal) => {
        const kelas = journal.kelas?.nama_kelas ?? '-';
        const mapel = journal.mataPelajaran?.nama_mapel ?? '-';
        const href = `/jurnal/${journal.id}`;
        const createdTime = new Date(journal.created_at).getTime();

        if (!Number.isNaN(createdTime)) {
            items.push({
                id: `baru-${journal.id}`,
                kind: 'sistem',
                icon: BookOpen,
                bubbleClass: 'bg-[#EFF4FF]',
                iconClass: 'text-[#2563EB]',
                title: 'Anda memiliki jurnal baru',
                sub: `${mapel} – ${kelas}`,
                iso: journal.created_at,
                href,
            });
        }

        const updatedTime = new Date(journal.updated_at).getTime();

        if (
            !Number.isNaN(updatedTime) &&
            !Number.isNaN(createdTime) &&
            updatedTime - createdTime > 60_000
        ) {
            items.push({
                id: `ubah-${journal.id}`,
                kind: 'sistem',
                icon: Pencil,
                bubbleClass: 'bg-[#F1EAFE]',
                iconClass: 'text-[#7C3AED]',
                title: 'Jurnal telah diperbaiki',
                sub: `${mapel} – ${kelas}`,
                iso: journal.updated_at,
                href,
            });
        }

        const status = journal.status ?? 'pending';
        const statusIso = journal.validated_at ?? journal.updated_at;
        const validatorName = journal.validator?.name;

        if (status === 'revisi') {
            items.push({
                id: `val-${journal.id}`,
                kind: 'revisi',
                icon: Pencil,
                bubbleClass: 'bg-[#FDECEC]',
                iconClass: 'text-[#DC2626]',
                title: 'Jurnal perlu revisi',
                sub: journal.validation_note
                    ? `${journal.validation_note.slice(0, 80)}${validatorName ? ` • ${validatorName}` : ''}`
                    : `${kelas} • ${mapel}${validatorName ? ` • ${validatorName}` : ''}`,
                iso: statusIso,
                href,
                key: `jurnal.${journal.id}.revisi`,
            });
        } else if (status === 'divalidasi') {
            items.push({
                id: `val-${journal.id}`,
                kind: 'validasi',
                icon: CircleCheck,
                bubbleClass: 'bg-[#E8F7EE]',
                iconClass: 'text-[#16A34A]',
                title: 'Jurnal telah divalidasi',
                sub: validatorName
                    ? `${kelas} • ${mapel} • ${validatorName}`
                    : `${kelas} • ${mapel}`,
                iso: statusIso,
                href,
                key: `jurnal.${journal.id}.divalidasi`,
            });
        } else {
            items.push({
                id: `val-${journal.id}`,
                kind: 'validasi',
                icon: Bell,
                bubbleClass: 'bg-[#FFF4E0]',
                iconClass: 'text-[#D97706]',
                title: 'Jurnal menunggu validasi',
                sub: `${kelas} (${formatTanggal(journal.tanggal)})`,
                iso: journal.created_at,
                href,
                key: `jurnal.${journal.id}.pending`,
            });
        }
    });

    return items
        .sort((a, b) => {
            // Revisi selalu paling atas, lalu terbaru
            const rank = (k: NotifKind) => (k === 'revisi' ? 0 : 1);
            const dr = rank(a.kind) - rank(b.kind);

            if (dr !== 0) {
                return dr;
            }

            return new Date(b.iso).getTime() - new Date(a.iso).getTime();
        })
        .slice(0, 20);
}

export default function Notifikasi({
    journals = [],
    counts,
    unreadKeys = [],
}: Props) {
    const [filter, setFilter] = useState<NotifFilter>('semua');
    // Optimistic: kunci yang baru saja dibaca di sesi ini langsung hilang
    // dari badge tanpa menunggu props reload.
    const [localRead, setLocalRead] = useState<string[]>([]);

    const notifications = useMemo(
        () => buildNotifications(journals),
        [journals],
    );

    const isUnread = (key?: string): boolean =>
        !!key && unreadKeys.includes(key) && !localRead.includes(key);

    const unreadCount = notifications.filter((item) =>
        isUnread(item.key),
    ).length;

    const sorted = useMemo(
        () =>
            [...notifications].sort(
                (a, b) => Number(isUnread(b.key)) - Number(isUnread(a.key)),
            ),
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [notifications, unreadKeys, localRead],
    );

    const filtered = useMemo(
        () =>
            filter === 'semua'
                ? sorted
                : sorted.filter((item) => item.kind === filter),
        [sorted, filter],
    );

    function openItem(e: React.MouseEvent, item: NotifItem) {
        if (!isUnread(item.key) || !item.key) {
            return;
        }

        e.preventDefault();
        const key = item.key;
        setLocalRead((prev) => (prev.includes(key) ? prev : [...prev, key]));
        router.post(
            '/notifikasi/baca',
            { keys: [key] },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => router.visit(item.href),
            },
        );
    }

    function readAll() {
        const keys = notifications
            .map((item) => item.key)
            .filter((k): k is string => !!k && !localRead.includes(k));

        if (keys.length === 0) {
            return;
        }

        setLocalRead((prev) => [...prev, ...keys]);
        router.post(
            '/notifikasi/baca-semua',
            {},
            { preserveState: true, preserveScroll: true },
        );
    }

    const countFor = (key: NotifFilter): number | null => {
        if (!counts) {
            return null;
        }

        if (key === 'revisi') {
            return counts.revisi;
        }

        if (key === 'validasi') {
            return counts.pending + counts.divalidasi;
        }

        if (key === 'sistem') {
            return null;
        }

        return notifications.length;
    };

    return (
        <>
            <Head title="Notifikasi" />

            <div className="mx-auto w-full max-w-2xl font-[Inter,system-ui,sans-serif] lg:max-w-4xl lg:rounded-2xl lg:bg-[#F6F8FC] lg:py-2">
                {/* Header */}
                <section className="flex items-center gap-3 px-4 pt-2 pb-1 lg:px-8 lg:pt-6">
                    <Link
                        href="/dashboard"
                        aria-label="Kembali ke Beranda"
                        className="flex h-9 w-9 shrink-0 items-center justify-center lg:hidden"
                    >
                        <ArrowLeft className="h-[22px] w-[22px] text-[#1A1D26]" />
                    </Link>
                    <div className="min-w-0 flex-1">
                        <h1 className="text-[17px] font-bold whitespace-nowrap text-[#1A1D26] lg:text-2xl">
                            Notifikasi
                        </h1>
                        <p className="mt-0.5 hidden text-sm font-normal text-[#6B7280] lg:block">
                            {counts && counts.revisi > 0 ? (
                                <span className="font-semibold text-[#DC2626]">
                                    {counts.revisi} jurnal perlu revisi
                                </span>
                            ) : (
                                <>
                                    {notifications.length} notifikasi dari
                                    aktivitas jurnal Anda
                                </>
                            )}
                        </p>
                        {counts && counts.revisi > 0 && (
                            <p className="mt-1 text-xs font-semibold text-[#DC2626] lg:hidden">
                                {counts.revisi} perlu revisi
                            </p>
                        )}
                        {unreadCount > 0 && (
                            <p className="mt-1 text-xs font-normal text-[#6B7280]">
                                {unreadCount} belum dibaca
                            </p>
                        )}
                    </div>
                    {unreadCount > 0 && (
                        <button
                            type="button"
                            onClick={readAll}
                            className="shrink-0 rounded-full border border-[#E5E9F2] bg-white px-4 py-2 text-xs font-semibold whitespace-nowrap text-[#2563EB]"
                        >
                            Tandai semua dibaca
                        </button>
                    )}
                </section>

                {/* Filter */}
                <FilterChips<NotifFilter>
                    items={FILTERS.map((item) => {
                        const n = countFor(item.key);

                        return {
                            key: item.key,
                            label:
                                n !== null && n > 0
                                    ? `${item.label} (${n})`
                                    : item.label,
                        };
                    })}
                    active={filter}
                    onChange={setFilter}
                />

                {/* Daftar notifikasi */}
                <section className="flex flex-col gap-[18px] px-4 pt-3.5 pb-6 lg:px-8 lg:py-6">
                    {notifications.length === 0 ? (
                        <EmptyState
                            title="Belum ada notifikasi"
                            description="Notifikasi dari aktivitas jurnal akan muncul di sini."
                            action={
                                <Link
                                    href="/jurnal/create"
                                    className="mt-1 rounded-full bg-[#2563EB] px-4 py-2 text-xs font-semibold text-white"
                                >
                                    + Buat Jurnal
                                </Link>
                            }
                        />
                    ) : filtered.length === 0 ? (
                        <EmptyState
                            title="Tidak ada notifikasi pada filter ini"
                            description="Coba pilih filter lain."
                        />
                    ) : (
                        <div className="grid grid-cols-1 gap-[18px] lg:grid-cols-2">
                            {filtered.map((item) => {
                                const Icon = item.icon;
                                const unread = isUnread(item.key);

                                return (
                                    <Link
                                        key={item.id}
                                        href={item.href}
                                        onClick={(e) => openItem(e, item)}
                                        className={`flex items-start gap-3 rounded-[14px] border bg-white p-3 ${
                                            unread
                                                ? 'border-[#93C5FD]'
                                                : 'border-[#E5E9F2]'
                                        }`}
                                    >
                                        <span
                                            className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full ${item.bubbleClass}`}
                                        >
                                            <Icon
                                                className={`h-[18px] w-[18px] ${item.iconClass}`}
                                            />
                                        </span>
                                        <span className="flex min-w-0 flex-1 flex-col gap-[11px]">
                                            <span className="text-[13px] font-semibold text-[#1A1D26]">
                                                {item.title}
                                            </span>
                                            <span className="text-xs font-normal text-[#6B7280]">
                                                {item.sub}
                                            </span>
                                        </span>
                                        <span className="flex shrink-0 flex-col items-end gap-1">
                                            {unread && <UnreadPill />}
                                            <span className="text-[10px] font-normal whitespace-nowrap text-[#9CA3AF]">
                                                {formatRelatif(item.iso)}
                                            </span>
                                        </span>
                                    </Link>
                                );
                            })}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}
