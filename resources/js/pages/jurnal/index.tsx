import { Head, Link, router, useForm } from '@inertiajs/react';
import { ChevronRight, ClipboardList, Trash2 } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import EmptyState from '@/components/empty-state';
import FilterChips from '@/components/filter-chips';
import PageShell from '@/components/page-shell';
import SearchBar from '@/components/search-bar';
import StatusBadge from '@/components/status-badge';
import { formatTanggal, parseTanggal } from '@/lib/format';
import type { Journal, ValidationStatus } from '@/types';

interface Props {
    journals: Journal[];
    slotHariIni: Journal[];
    sekarang: string;
    labelHari: string;
    tanggalAktif: string;
    tanggalHariIni: string;
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

function keMenit(waktu: string): number {
    const [h, m] = waktu.slice(0, 5).split(':').map(Number);

    return h * 60 + m;
}

/**
 * Jam "sekarang" versi server (WIB) yang ikut berdetak di browser.
 * Basisnya props `sekarang` agar tidak tergantung jam HP user
 * (yang bisa salah zona/setting). State menit naik tiap 60 detik;
 * tidak ada Date.now() di badan render (aturan react-hooks/purity).
 */
function useJamServer(sekarang: string): string {
    // Nilai awal dari props; halaman me-remount tiap navigasi Inertia
    // sehingga tidak perlu sinkronisasi ulang di dalam effect.
    const [menit, setMenit] = useState(() => keMenit(sekarang));

    useEffect(() => {
        const id = setInterval(() => {
            setMenit((v) => (v + 1) % 1440);
        }, 60000);

        return () => clearInterval(id);
    }, []);

    const hh = String(Math.floor(menit / 60) % 24).padStart(2, '0');
    const mm = String(menit % 60).padStart(2, '0');

    return `${hh}:${mm}`;
}

type PosisiSlot = 'belum' | 'jalan' | 'lewat';

function posisiSlot(slot: Journal, jamSekarang: string): PosisiSlot {
    const now = keMenit(jamSekarang);

    if (now < keMenit(slot.jam_mulai)) {
        return 'belum';
    }

    if (now <= keMenit(slot.jam_selesai)) {
        return 'jalan';
    }

    return 'lewat';
}

const NAMA_HARI_PENDEK = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

/** Geser tanggal Y-m-d sejauh offset hari (untuk slider Kemarin/Besok). */
function geserTanggal(iso: string, offset: number): string {
    const [y, m, d] = iso.split('-').map(Number);
    const t = new Date(y, m - 1, d);
    t.setDate(t.getDate() + offset);
    const mm = String(t.getMonth() + 1).padStart(2, '0');
    const dd = String(t.getDate()).padStart(2, '0');

    return `${t.getFullYear()}-${mm}-${dd}`;
}

function namaHariPendek(iso: string): string {
    const [y, m, d] = iso.split('-').map(Number);

    return NAMA_HARI_PENDEK[new Date(y, m - 1, d).getDay()];
}

export default function Index({
    journals = [],
    slotHariIni = [],
    sekarang,
    labelHari,
    tanggalAktif,
    tanggalHariIni,
}: Props) {
    const { delete: destroy } = useForm();
    const [query, setQuery] = useState('');
    const [dateFilter, setDateFilter] = useState<DateFilter>('semua');
    // Jam real-time berbasis waktu server (WIB), bukan jam HP.
    const jamSekarang = useJamServer(sekarang);
    // Tombol Isi/Edit hanya aktif di tanggal hari ini (server yang menegakkan).
    const bisaIsi = tanggalAktif === tanggalHariIni;

    function pindahTanggal(iso: string) {
        router.get(
            '/jurnal',
            { tanggal: iso },
            { preserveState: true, replace: true },
        );
    }

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
                journal.materi ?? '',
                journal.kegiatan ?? '',
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
            >
                {/* Slider tanggal: Kemarin | Hari Ini | Besok.
                    Melihat boleh, mengisi hanya hari ini (bisaIsi). */}
                <section className="px-4 pt-3.5 lg:px-8 lg:pt-6">
                    <div className="grid grid-cols-3 gap-2">
                        {(
                            [
                                {
                                    label: `Kemarin (${namaHariPendek(geserTanggal(tanggalAktif, -1))})`,
                                    iso: geserTanggal(tanggalAktif, -1),
                                },
                                {
                                    label: `Hari Ini (${namaHariPendek(tanggalAktif)})`,
                                    iso: tanggalHariIni,
                                },
                                {
                                    label: `Besok (${namaHariPendek(geserTanggal(tanggalAktif, 1))})`,
                                    iso: geserTanggal(tanggalAktif, 1),
                                },
                            ] as const
                        ).map((item) => {
                            const aktif = tanggalAktif === item.iso;

                            return (
                                <button
                                    key={item.label}
                                    type="button"
                                    onClick={() => pindahTanggal(item.iso)}
                                    className={`flex items-center justify-center gap-1 rounded-xl px-2 py-2.5 text-xs font-semibold whitespace-nowrap ${
                                        aktif
                                            ? 'bg-[#2563EB] text-white'
                                            : 'border border-[#E5E9F2] bg-white text-[#6B7280]'
                                    }`}
                                >
                                    {aktif && item.iso === tanggalHariIni ? (
                                        <span className="relative flex h-2 w-2">
                                            <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-white opacity-75" />
                                            <span className="relative inline-flex h-2 w-2 rounded-full bg-white" />
                                        </span>
                                    ) : null}
                                    {item.label}
                                </button>
                            );
                        })}
                    </div>
                    <div className="flex items-center justify-between gap-2 pt-2">
                        <p className="text-xs font-normal text-[#6B7280]">
                            {labelHari}
                        </p>
                        <span
                            className="rounded-full bg-[#1A1D26] px-3 py-1 text-xs font-bold whitespace-nowrap text-white tabular-nums"
                            title="Jam server (WIB), bukan jam HP"
                        >
                            {jamSekarang} WIB
                        </span>
                    </div>
                    {!bisaIsi && (
                        <p className="pt-1 text-[11px] font-medium text-[#D97706]">
                            Mode lihat saja — pengisian hanya bisa dilakukan
                            pada hari ini.
                        </p>
                    )}
                    {slotHariIni.length === 0 ? (
                        <p className="pt-1 text-xs font-normal text-[#6B7280]">
                            Tidak ada jadwal mengajar pada tanggal ini. Slot
                            dibuat otomatis dari jadwal setiap jam 00:00.
                        </p>
                    ) : (
                        <div className="flex flex-col gap-2 pt-3">
                            {slotHariIni.map((slot) => {
                                const posisi = posisiSlot(slot, jamSekarang);
                                const status = slot.status ?? 'menunggu';
                                const sedangJalan =
                                    bisaIsi &&
                                    posisi === 'jalan' &&
                                    (status === 'menunggu' ||
                                        status === 'pending');
                                const terkunci =
                                    status === 'divalidasi' ||
                                    status === 'ditolak' ||
                                    status === 'terlambat' ||
                                    status === 'izin';

                                return (
                                    <article
                                        key={slot.id}
                                        className={`flex items-center gap-2 rounded-xl border bg-white p-[14px] ${
                                            sedangJalan
                                                ? 'border-[#16A34A] shadow-[0px_2px_12px_0px_#16A34A33]'
                                                : 'border-[#E5E9F2]'
                                        }`}
                                    >
                                        <span className="flex w-[86px] shrink-0 flex-col rounded-lg bg-[#F6F8FC] px-2 py-1.5 text-center">
                                            <span className="text-[13px] font-bold whitespace-nowrap text-[#1A1D26]">
                                                {slot.jam_mulai.slice(0, 5)}
                                            </span>
                                            <span className="text-[10px] font-normal text-[#9CA3AF]">
                                                s/d{' '}
                                                {slot.jam_selesai.slice(0, 5)}
                                            </span>
                                        </span>
                                        <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                                            <span className="truncate text-xs font-semibold text-[#1A1D26]">
                                                {slot.mataPelajaran
                                                    ?.nama_mapel ?? '-'}{' '}
                                                •{' '}
                                                {slot.kelas?.nama_kelas ?? '-'}
                                            </span>
                                            {sedangJalan ? (
                                                <span className="inline-flex items-center gap-1 text-[11px] font-bold text-[#16A34A]">
                                                    <span className="relative flex h-2 w-2">
                                                        <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#16A34A] opacity-75" />
                                                        <span className="relative inline-flex h-2 w-2 rounded-full bg-[#16A34A]" />
                                                    </span>
                                                    SEDANG BERLANGSUNG
                                                </span>
                                            ) : status === 'menunggu' ? (
                                                <span className="text-[11px] font-semibold text-[#6B7280]">
                                                    {posisi === 'lewat' &&
                                                    bisaIsi
                                                        ? 'Terlewat — segera isi susulan'
                                                        : 'Belum Diisi'}
                                                </span>
                                            ) : status === 'pending' ? (
                                                <span className="text-[11px] font-semibold text-[#D97706]">
                                                    🟡 Menunggu Validasi MPK
                                                </span>
                                            ) : (
                                                <StatusBadge
                                                    status={
                                                        status as ValidationStatus
                                                    }
                                                />
                                            )}
                                        </div>
                                        {/* Varian 1 (belum diisi) & susulan: tombol Isi.
                                            Varian 2 (menunggu validasi): tombol Edit.
                                            Varian 3 (final): terkunci, hanya Lihat. */}
                                        {status === 'menunggu' && bisaIsi && (
                                            <Link
                                                href={`/jurnal/${slot.id}/edit`}
                                                className="inline-flex shrink-0 items-center gap-2 rounded-full bg-[#2563EB] px-4 py-2 text-xs font-semibold whitespace-nowrap text-white"
                                            >
                                                <ClipboardList className="h-4 w-4 text-white" />
                                                Isi Jurnal
                                            </Link>
                                        )}
                                        {status === 'jam_kosong' && bisaIsi && (
                                            <Link
                                                href={`/jurnal/${slot.id}/edit`}
                                                className="inline-flex shrink-0 items-center gap-2 rounded-full bg-[#2563EB] px-4 py-2 text-xs font-semibold whitespace-nowrap text-white"
                                            >
                                                <ClipboardList className="h-4 w-4 text-white" />
                                                Isi Susulan
                                            </Link>
                                        )}
                                        {status === 'pending' && bisaIsi && (
                                            <Link
                                                href={`/jurnal/${slot.id}/edit`}
                                                className="inline-flex shrink-0 items-center gap-2 rounded-full border border-[#E5E9F2] bg-white px-4 py-2 text-xs font-semibold whitespace-nowrap text-[#2563EB]"
                                            >
                                                Edit Jurnal
                                            </Link>
                                        )}
                                        {(terkunci || !bisaIsi) && (
                                            <Link
                                                href={`/jurnal/${slot.id}`}
                                                className="inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-2 text-xs font-semibold whitespace-nowrap text-[#6B7280]"
                                            >
                                                Lihat
                                                <ChevronRight className="h-4 w-4" />
                                            </Link>
                                        )}
                                    </article>
                                );
                            })}
                        </div>
                    )}
                </section>

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
                            description="Slot jurnal dibuat otomatis dari jadwal mengajar setiap hari."
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
                                                    Materi:{' '}
                                                    {journal.materi ??
                                                        '(belum diisi)'}
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
        </>
    );
}
