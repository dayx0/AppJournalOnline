import { Head, Link, router } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { useState } from 'react';
import EmptyState from '@/components/empty-state';
import FilterChips from '@/components/filter-chips';
import PageShell from '@/components/page-shell';
import SearchBar from '@/components/search-bar';
import SimplePager from '@/components/simple-pager';
import StatusBadge from '@/components/status-badge';
import { formatTanggal } from '@/lib/format';
import type { Journal, ValidationStatus } from '@/types';

type PaginatedJurnal = {
    data: (Journal & { validator: { name: string } | null })[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
};

type StatusFilter = 'semua' | ValidationStatus;

const STATUS_FILTERS: { key: StatusFilter; label: string }[] = [
    { key: 'semua', label: 'Semua' },
    { key: 'menunggu', label: 'Belum diisi' },
    { key: 'pending', label: 'Menunggu' },
    { key: 'divalidasi', label: 'Divalidasi' },
    { key: 'ditolak', label: 'Ditolak' },
    { key: 'jam_kosong', label: 'Jam kosong' },
    { key: 'izin', label: 'Izin' },
    { key: 'terlambat', label: 'Terlambat' },
];

export default function ValidasiIndex({
    jurnals,
    filters,
    assignedKelas,
}: {
    jurnals: PaginatedJurnal;
    filters: { status?: string; search?: string };
    assignedKelas?: { id: number; nama_kelas: string } | null;
}) {
    const [search, setSearch] = useState(filters.search ?? '');
    const activeStatus =
        (filters.status as ValidationStatus | undefined) ?? 'semua';

    function apply(next: { status?: string; search?: string }) {
        router.get('/mpk/jurnal', next, { preserveState: true, replace: true });
    }

    function submitSearch() {
        apply({
            status: filters.status,
            search: search.trim() === '' ? undefined : search.trim(),
        });
    }

    return (
        <>
            <Head title="Validasi Jurnal" />
            <PageShell
                framed={false}
                title="Validasi Jurnal Guru"
                subtitle={
                    assignedKelas
                        ? `Kelas binaan: ${assignedKelas.nama_kelas} • ${jurnals.total} jurnal tercatat`
                        : `Belum ditempati di kelas • ${jurnals.total} jurnal tercatat`
                }
            >
                {!assignedKelas && (
                    <p className="px-4 pt-3 text-xs font-medium text-[#D97706] lg:px-8">
                        Akun MPK ini belum ditempati di kelas manapun. Minta
                        admin menempatkan kamu via Kelola User agar bisa
                        memvalidasi jurnal.
                    </p>
                )}
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        submitSearch();
                    }}
                >
                    <SearchBar
                        value={search}
                        onChange={setSearch}
                        placeholder="Cari materi, guru, kelas, mapel..."
                    />
                </form>

                <FilterChips<StatusFilter>
                    items={STATUS_FILTERS}
                    active={activeStatus as StatusFilter}
                    onChange={(key) =>
                        apply({
                            search:
                                search.trim() === ''
                                    ? undefined
                                    : search.trim(),
                            status: key === 'semua' ? undefined : key,
                        })
                    }
                />

                <section className="flex flex-col gap-[19px] px-4 pt-3.5 pb-6 lg:px-8 lg:py-6">
                    {jurnals.data.length === 0 ? (
                        <EmptyState
                            title="Belum ada jurnal"
                            description="Jurnal guru yang masuk akan tampil di sini."
                        />
                    ) : (
                        <div className="grid grid-cols-1 gap-[19px] md:grid-cols-2 xl:grid-cols-3">
                            {jurnals.data.map((j) => {
                                const status: ValidationStatus =
                                    j.status ?? 'pending';

                                return (
                                    <article
                                        key={j.id}
                                        className="flex items-center gap-2 rounded-xl bg-white p-[14px] shadow-[0px_2px_10px_0px_#0F172A0D]"
                                    >
                                        <Link
                                            href={`/mpk/jurnal/${j.id}`}
                                            className="flex min-w-0 flex-1 items-center gap-2"
                                        >
                                            <div className="flex min-w-0 flex-1 flex-col gap-[15px]">
                                                <div className="flex items-center justify-between gap-2">
                                                    <span className="text-[13px] font-bold whitespace-nowrap text-[#1A1D26]">
                                                        {formatTanggal(
                                                            j.tanggal,
                                                        )}
                                                    </span>
                                                    <StatusBadge
                                                        status={status}
                                                    />
                                                </div>
                                                <span className="truncate text-xs font-semibold text-[#1A1D26]">
                                                    {j.guru?.name ?? '-'} &bull;{' '}
                                                    {j.kelas?.nama_kelas ?? '-'}
                                                </span>
                                                <span className="line-clamp-2 text-xs font-normal text-[#6B7280]">
                                                    {j.mataPelajaran
                                                        ?.nama_mapel ??
                                                        '-'}{' '}
                                                    &bull; {j.materi}
                                                </span>
                                            </div>
                                            <ChevronRight className="h-5 w-5 shrink-0 text-[#6B7280]" />
                                        </Link>
                                    </article>
                                );
                            })}
                        </div>
                    )}
                </section>

                <div className="pb-6">
                    <SimplePager links={jurnals.links} />
                </div>
            </PageShell>
        </>
    );
}
