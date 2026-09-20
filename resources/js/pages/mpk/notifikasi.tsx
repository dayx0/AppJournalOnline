import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, Bell, CircleCheck, Pencil } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useMemo, useState } from 'react';
import EmptyState from '@/components/empty-state';
import FilterChips from '@/components/filter-chips';
import PageShell from '@/components/page-shell';
import { UnreadPill } from '@/components/unread-dot';
import { formatRelatif, formatTanggal } from '@/lib/format';
import type { Journal } from '@/types';

interface Props {
    pending: Journal[];
    mendesakIds: number[];
    revisi: Journal[];
    terbaru: Journal[];
    counts: { pending: number; mendesak: number; revisi: number };
    unreadKeys?: string[];
}

type MpkKind = 'mendesak' | 'pending' | 'revisi' | 'info';
type MpkFilter = 'semua' | MpkKind;

interface MpkItem {
    id: string;
    kind: MpkKind;
    icon: LucideIcon;
    bubbleClass: string;
    iconClass: string;
    title: string;
    sub: string;
    iso: string;
    href: string;
    key?: string;
}

function buildItems(
    pending: Journal[],
    mendesakIds: number[],
    revisi: Journal[],
    terbaru: Journal[],
): MpkItem[] {
    const items: MpkItem[] = [];
    const mendesak = new Set(mendesakIds);

    pending.forEach((j) => {
        const guru = j.guru?.name ?? '-';
        const kelas = j.kelas?.nama_kelas ?? '-';
        const mapel = j.mataPelajaran?.nama_mapel ?? '-';
        const isMendesak = mendesak.has(j.id);

        items.push({
            id: `pending-${j.id}`,
            kind: isMendesak ? 'mendesak' : 'pending',
            icon: isMendesak ? AlertTriangle : Bell,
            bubbleClass: isMendesak ? 'bg-[#FDECEC]' : 'bg-[#FFF4E0]',
            iconClass: isMendesak ? 'text-[#DC2626]' : 'text-[#D97706]',
            title: isMendesak
                ? 'Mendesak: jurnal >24 jam belum divalidasi'
                : 'Jurnal baru menunggu validasi',
            sub: `${guru} • ${kelas} • ${mapel} (${formatTanggal(j.tanggal)})`,
            iso: j.created_at,
            href: `/mpk/jurnal/${j.id}`,
            key: `jurnal.${j.id}.pending`,
        });
    });

    revisi.forEach((j) => {
        const guru = j.guru?.name ?? '-';
        const kelas = j.kelas?.nama_kelas ?? '-';

        items.push({
            id: `revisi-${j.id}`,
            kind: 'revisi',
            icon: Pencil,
            bubbleClass: 'bg-[#FDECEC]',
            iconClass: 'text-[#DC2626]',
            title: 'Menunggu perbaikan guru (revisi)',
            sub: `${guru} • ${kelas} (${formatTanggal(j.tanggal)})`,
            iso: j.validated_at ?? j.updated_at,
            href: `/mpk/jurnal/${j.id}`,
        });
    });

    terbaru.forEach((j) => {
        const guru = j.guru?.name ?? '-';
        const kelas = j.kelas?.nama_kelas ?? '-';

        items.push({
            id: `ok-${j.id}`,
            kind: 'info',
            icon: CircleCheck,
            bubbleClass: 'bg-[#E8F7EE]',
            iconClass: 'text-[#16A34A]',
            title: 'Baru saja divalidasi',
            sub: `${guru} • ${kelas}`,
            iso: j.validated_at ?? j.updated_at,
            href: `/mpk/jurnal/${j.id}`,
        });
    });

    const rank = (k: MpkKind) =>
        k === 'mendesak' ? 0 : k === 'pending' ? 1 : k === 'revisi' ? 2 : 3;

    return items
        .sort((a, b) => {
            const d = rank(a.kind) - rank(b.kind);

            if (d !== 0) {
                return d;
            }

            return new Date(b.iso).getTime() - new Date(a.iso).getTime();
        })
        .slice(0, 30);
}

export default function MpkNotifikasi({
    pending,
    mendesakIds,
    revisi,
    terbaru,
    counts,
    unreadKeys = [],
}: Props) {
    const [filter, setFilter] = useState<MpkFilter>('semua');
    const [localRead, setLocalRead] = useState<string[]>([]);

    const items = useMemo(
        () => buildItems(pending, mendesakIds, revisi, terbaru),
        [pending, mendesakIds, revisi, terbaru],
    );

    const isUnread = (key?: string): boolean =>
        !!key && unreadKeys.includes(key) && !localRead.includes(key);

    const unreadCount = items.filter((i) => isUnread(i.key)).length;

    const sorted = useMemo(
        () =>
            [...items].sort(
                (a, b) => Number(isUnread(b.key)) - Number(isUnread(a.key)),
            ),
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [items, unreadKeys, localRead],
    );

    const filtered = useMemo(
        () =>
            filter === 'semua'
                ? sorted
                : sorted.filter((i) => i.kind === filter),
        [sorted, filter],
    );

    function openItem(e: React.MouseEvent, item: MpkItem) {
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
        const keys = items
            .map((i) => i.key)
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

    const filters: { key: MpkFilter; label: string }[] = [
        { key: 'semua', label: `Semua (${items.length})` },
        { key: 'mendesak', label: `Mendesak (${counts.mendesak})` },
        { key: 'pending', label: `Menunggu (${counts.pending})` },
        { key: 'revisi', label: `Revisi (${counts.revisi})` },
    ];

    return (
        <>
            <Head title="Notifikasi MPK" />
            <PageShell
                framed={false}
                title="Notifikasi MPK"
                subtitle={
                    counts.mendesak > 0
                        ? `${counts.mendesak} jurnal mendesak perlu validasi`
                        : counts.pending > 0
                          ? `${counts.pending} jurnal menunggu validasi`
                          : 'Tidak ada antrean, kerja bagus!'
                }
                actions={
                    unreadCount > 0 ? (
                        <button
                            type="button"
                            onClick={readAll}
                            className="shrink-0 rounded-full bg-[#2563EB] px-4 py-2.5 text-sm font-semibold whitespace-nowrap text-white"
                        >
                            Tandai semua dibaca
                        </button>
                    ) : undefined
                }
            >
                {unreadCount > 0 && (
                    <p className="px-4 pt-1 text-xs font-normal text-[#6B7280] lg:hidden lg:px-8">
                        {unreadCount} belum dibaca
                    </p>
                )}

                <FilterChips<MpkFilter>
                    items={filters}
                    active={filter}
                    onChange={setFilter}
                />

                <section className="flex flex-col gap-[18px] px-4 pt-3.5 pb-6 lg:px-8 lg:py-6">
                    {filtered.length === 0 ? (
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
            </PageShell>
        </>
    );
}
