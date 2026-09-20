import { Head, Link, useForm } from '@inertiajs/react';
import { ChevronRight, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import EmptyState from '@/components/empty-state';
import FilterChips from '@/components/filter-chips';
import PageShell from '@/components/page-shell';
import SearchBar from '@/components/search-bar';
import StatusBadge from '@/components/status-badge';
import { formatTanggal, parseTanggal } from '@/lib/format';
import type { Journal, ValidationStatus } from '@/types';

interface Props {
    journals: Journal[];
}

type DateFilter = 'semua' | 'hari-ini' | 'minggu-ini' | 'bulan-ini';

const FILTERS: { key: DateFilter; label: string }[] = [
    { key: 'semua', label: 'Semua' },
    { key: 'hari-ini', label: 'Hari Ini' },
    { key: 'minggu-ini', label: 'Minggu Ini' },
    { key: 'bulan-ini', label: 'Bulan Ini' },
];

function isSameDay(a: Date, b: Date): boolean {
    return (
        a.getFullYear() === b.getFullYear() &&
        a.getMonth() === b.getMonth() &&
        a.getDate() === b.getDate()
    );
}

function startOfWeek(value: Date): Date {
    const date = new Date(value);
    // Minggu dimulai hari Senin (standar Indonesia).
    const offset = (date.getDay() + 6) % 7;
    date.setDate(date.getDate() - offset);
    date.setHours(0, 0, 0, 0);

    return date;
}

function endOfWeek(value: Date): Date {
    const date = startOfWeek(value);
    date.setDate(date.getDate() + 6);
    date.setHours(23, 59, 59, 999);

    return date;
}

function matchDateFilter(tanggal: string, filter: DateFilter): boolean {
    if (filter === 'semua') {
        return true;
    }

    const date = parseTanggal(tanggal);

    if (!date) {
        return false;
    }

    const now = new Date();

    if (filter === 'hari-ini') {
        return isSameDay(date, now);
    }

    if (filter === 'minggu-ini') {
        return date >= startOfWeek(now) && date <= endOfWeek(now);
    }

    return (
        date.getFullYear() === now.getFullYear() &&
        date.getMonth() === now.getMonth()
    );
}

export default function Index({ journals = [] }: Props) {
    const { delete: destroy } = useForm();
    const [query, setQuery] = useState('');
    const [dateFilter, setDateFilter] = useState<DateFilter>('semua');

    function hapusJurnal(id: number) {
        if (confirm('Yakin ingin menghapus jurnal ini?')) {
            destroy(`/jurnal/${id}`);
        }
    }

    const filteredJournals = useMemo(() => {
        const keyword = query.trim().toLowerCase();

        return journals.filter((journal) => {
            if (!matchDateFilter(journal.tanggal, dateFilter)) {
                return false;
            }

            if (keyword === '') {
                return true;
            }

            const haystack = [
                journal.tanggal,
                formatTanggal(journal.tanggal),
                journal.kelas?.nama_kelas ?? '',
                journal.mataPelajaran?.nama_mapel ?? '',
                journal.materi,
                journal.kegiatan,
            ]
                .join(' ')
                .toLowerCase();

            return haystack.includes(keyword);
        });
    }, [journals, query, dateFilter]);

    return (
        <>
            <Head title="Jurnal" />

            <PageShell
                title="Jurnal"
                subtitle={`${journals.length} jurnal tercatat`}
                backHref="/dashboard"
                backLabel="Kembali ke Beranda"
                actions={
                    <Link
                        href="/jurnal/create"
                        className="inline-flex shrink-0 items-center gap-2 rounded-full bg-[#2563EB] px-4 py-2.5 text-sm font-semibold text-white shadow-[0_8px_20px_-6px_rgba(37,99,235,0.6)]"
                    >
                        <Plus className="h-4 w-4 text-white" />
                        Tambah Jurnal
                    </Link>
                }
            >
                <SearchBar
                    value={query}
                    onChange={setQuery}
                    placeholder="Cari jurnal, kelas, atau mata pelajaran..."
                />

                <FilterChips
                    items={FILTERS}
                    active={dateFilter}
                    onChange={setDateFilter}
                />

                {/* Daftar Jurnal */}
                <section className="flex flex-col gap-[19px] px-4 pt-3.5 pb-6 lg:px-8 lg:py-6">
                    {journals.length === 0 ? (
                        <EmptyState
                            title="Belum ada jurnal"
                            description="Mulai catat kegiatan mengajar hari ini."
                            action={
                                <Link
                                    href="/jurnal/create"
                                    className="mt-1 inline-flex items-center gap-2 rounded-full bg-[#2563EB] px-4 py-2 text-xs font-semibold text-white"
                                >
                                    <Plus className="h-4 w-4 text-white" />
                                    Buat Jurnal
                                </Link>
                            }
                        />
                    ) : filteredJournals.length === 0 ? (
                        <EmptyState
                            title="Tidak ada jurnal yang cocok"
                            description="Coba ubah kata kunci atau filter tanggal."
                            action={
                                <button
                                    type="button"
                                    onClick={() => {
                                        setQuery('');
                                        setDateFilter('semua');
                                    }}
                                    className="mt-1 rounded-full border border-[#E5E9F2] bg-white px-4 py-2 text-xs font-semibold text-[#2563EB]"
                                >
                                    Atur Ulang Filter
                                </button>
                            }
                        />
                    ) : (
                        <div className="grid grid-cols-1 gap-[19px] md:grid-cols-2 xl:grid-cols-3">
                            {filteredJournals.map((journal) => {
                                const status: ValidationStatus =
                                    journal.status ?? 'pending';

                                return (
                                    <article
                                        key={journal.id}
                                        className="flex items-center gap-2 rounded-xl bg-white p-[14px] shadow-[0px_2px_10px_0px_#0F172A0D]"
                                    >
                                        <Link
                                            href={`/jurnal/${journal.id}`}
                                            className="flex min-w-0 flex-1 items-center gap-2"
                                        >
                                            <div className="flex min-w-0 flex-1 flex-col gap-[15px]">
                                                <div className="flex items-center justify-between gap-2">
                                                    <span className="text-[13px] font-bold whitespace-nowrap text-[#1A1D26]">
                                                        {formatTanggal(
                                                            journal.tanggal,
                                                        )}
                                                    </span>
                                                    <StatusBadge
                                                        status={status}
                                                    />
                                                </div>
                                                <span className="truncate text-xs font-semibold text-[#1A1D26]">
                                                    {journal.kelas
                                                        ?.nama_kelas ??
                                                        '-'}{' '}
                                                    &bull;{' '}
                                                    {journal.mataPelajaran
                                                        ?.nama_mapel ?? '-'}
                                                </span>
                                                <span className="line-clamp-2 text-xs font-normal text-[#6B7280]">
                                                    Materi: {journal.materi}
                                                </span>
                                            </div>
                                            <ChevronRight className="h-5 w-5 shrink-0 text-[#6B7280]" />
                                        </Link>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                hapusJurnal(journal.id)
                                            }
                                            aria-label="Hapus jurnal"
                                            title="Hapus jurnal"
                                            className="flex h-8 w-8 shrink-0 items-center justify-center self-start rounded-lg text-[#DC2626] transition hover:bg-[#FDECEC]"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </button>
                                    </article>
                                );
                            })}
                        </div>
                    )}
                </section>
            </PageShell>

            {/* FAB Tambah: hanya di mobile, mengambang di atas bottom tab */}
            <Link
                href="/jurnal/create"
                aria-label="Tambah Jurnal"
                className="fixed right-5 bottom-24 z-10 flex h-14 w-14 items-center justify-center rounded-full bg-[#2563EB] shadow-[0_12px_24px_-6px_rgba(37,99,235,0.6)] lg:hidden"
            >
                <Plus className="h-[26px] w-[26px] text-white" />
            </Link>
        </>
    );
}
