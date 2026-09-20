import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    BookOpenText,
    ChevronDown,
    ChevronRight,
    CircleCheck,
    Clock,
} from 'lucide-react';
import { useState } from 'react';
import EmptyState from '@/components/empty-state';
import PageShell from '@/components/page-shell';
import SimplePager from '@/components/simple-pager';
import StatCard from '@/components/stat-card';
import StatusBadge from '@/components/status-badge';
import { formatTanggal } from '@/lib/format';
import type { Journal, ValidationStatus } from '@/types';

type PaginatedJurnal = {
    data: (Journal & { validator: { name: string } | null })[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
};

type Filters = {
    dari?: string;
    sampai?: string;
    kelas_id?: string;
    mapel_id?: string;
    guru_id?: string;
    status?: string;
};

type Options = {
    kelas: { id: number; nama_kelas: string }[];
    mapel: { id: number; nama_mapel: string }[];
    guru: { id: number; name: string }[];
};

type Summary = {
    total: number;
    pending: number;
    divalidasi: number;
    revisi: number;
};

const ALL = 'semua';

const INPUT_CLASS =
    'h-11 w-full rounded-[10px] border border-[#E5E9F2] bg-white px-3 text-[13px] font-normal text-[#1A1D26] outline-none placeholder:text-[#9CA3AF] focus:border-[#2563EB]';

function FilterSelect({
    label,
    value,
    onChange,
    placeholder,
    children,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    placeholder: string;
    children: React.ReactNode;
}) {
    return (
        <div className="flex flex-col gap-1.5">
            <span className="text-xs font-semibold whitespace-nowrap text-[#1A1D26]">
                {label}
            </span>
            <div className="relative">
                <select
                    aria-label={label}
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    className={`${INPUT_CLASS} appearance-none pr-9 ${value === ALL || value === '' ? 'text-[#9CA3AF]' : ''}`}
                >
                    <option value={ALL}>{placeholder}</option>
                    {children}
                </select>
                <ChevronDown className="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-[#9CA3AF]" />
            </div>
        </div>
    );
}

export default function Rekap({
    jurnals,
    filters,
    options,
    summary,
}: {
    jurnals: PaginatedJurnal;
    filters: Filters;
    options: Options;
    summary: Summary;
}) {
    const [dari, setDari] = useState(filters.dari ?? '');
    const [sampai, setSampai] = useState(filters.sampai ?? '');
    const [kelasId, setKelasId] = useState(filters.kelas_id ?? ALL);
    const [mapelId, setMapelId] = useState(filters.mapel_id ?? ALL);
    const [guruId, setGuruId] = useState(filters.guru_id ?? ALL);
    const [status, setStatus] = useState(filters.status ?? ALL);

    function cleanParams(): Record<string, string> {
        const params: Record<string, string> = {};

        if (dari) {
            params.dari = dari;
        }

        if (sampai) {
            params.sampai = sampai;
        }

        if (kelasId !== ALL) {
            params.kelas_id = kelasId;
        }

        if (mapelId !== ALL) {
            params.mapel_id = mapelId;
        }

        if (guruId !== ALL) {
            params.guru_id = guruId;
        }

        if (status !== ALL) {
            params.status = status;
        }

        return params;
    }

    function apply() {
        router.get('/mpk/rekap', cleanParams(), {
            preserveState: true,
            replace: true,
        });
    }

    function reset() {
        setDari('');
        setSampai('');
        setKelasId(ALL);
        setMapelId(ALL);
        setGuruId(ALL);
        setStatus(ALL);
        router.get('/mpk/rekap', {}, { preserveState: true, replace: true });
    }

    const exportHref = `/mpk/rekap/export?${new URLSearchParams(cleanParams()).toString()}`;

    return (
        <>
            <Head title="Rekap Jurnal" />
            <PageShell
                framed={false}
                title="Rekap Jurnal"
                subtitle={`${jurnals.total} jurnal sesuai filter`}
                actions={
                    <div className="flex gap-2 print:hidden">
                        <a
                            href={exportHref}
                            className="inline-flex shrink-0 items-center gap-2 rounded-full bg-[#2563EB] px-4 py-2.5 text-sm font-semibold text-white"
                        >
                            Export CSV
                        </a>
                        <button
                            type="button"
                            onClick={() => window.print()}
                            className="inline-flex shrink-0 items-center gap-2 rounded-full border border-[#E5E9F2] bg-white px-4 py-2.5 text-sm font-semibold text-[#1A1D26]"
                        >
                            Cetak
                        </button>
                    </div>
                }
            >
                <div className="flex gap-2 px-4 pt-2 lg:hidden lg:px-8">
                    <a
                        href={exportHref}
                        className="inline-flex flex-1 items-center justify-center gap-2 rounded-full bg-[#2563EB] px-4 py-2.5 text-sm font-semibold text-white"
                    >
                        Export CSV
                    </a>
                    <button
                        type="button"
                        onClick={() => window.print()}
                        className="inline-flex flex-1 items-center justify-center gap-2 rounded-full border border-[#E5E9F2] bg-white px-4 py-2.5 text-sm font-semibold text-[#1A1D26]"
                    >
                        Cetak
                    </button>
                </div>

                <section className="grid grid-cols-2 gap-3 px-5 pt-2.5 pb-1 lg:grid-cols-4 lg:gap-4 lg:px-8 lg:pt-5">
                    <StatCard
                        label="Menunggu Validasi"
                        value={String(summary.pending)}
                        sub="Perlu diperiksa"
                        icon={Clock}
                        iconWrapperClass="bg-[#FFF4E0]"
                        iconClass="text-[#D97706]"
                    />
                    <StatCard
                        label="Sudah Divalidasi"
                        value={String(summary.divalidasi)}
                        sub="Selesai diperiksa"
                        icon={CircleCheck}
                        iconWrapperClass="bg-[#E8F7EE]"
                        iconClass="text-[#16A34A]"
                    />
                    <StatCard
                        label="Perlu Revisi"
                        value={String(summary.revisi)}
                        sub="Dikembalikan ke guru"
                        icon={AlertTriangle}
                        iconWrapperClass="bg-[#FDECEC]"
                        iconClass="text-[#DC2626]"
                    />
                    <StatCard
                        label="Total Jurnal"
                        value={String(summary.total)}
                        sub="Sesuai filter aktif"
                        icon={BookOpenText}
                        iconWrapperClass="bg-[#EFF4FF]"
                        iconClass="text-[#2563EB]"
                    />
                </section>

                <section className="px-4 pt-3 pb-1 lg:px-8 print:hidden">
                    <div className="grid grid-cols-2 gap-3 lg:grid-cols-3 lg:gap-4">
                        <div className="flex flex-col gap-1.5">
                            <span className="text-xs font-semibold whitespace-nowrap text-[#1A1D26]">
                                Dari tanggal
                            </span>
                            <input
                                type="date"
                                aria-label="Dari tanggal"
                                value={dari}
                                onChange={(e) => setDari(e.target.value)}
                                className={INPUT_CLASS}
                            />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <span className="text-xs font-semibold whitespace-nowrap text-[#1A1D26]">
                                Sampai tanggal
                            </span>
                            <input
                                type="date"
                                aria-label="Sampai tanggal"
                                value={sampai}
                                min={dari || undefined}
                                onChange={(e) => setSampai(e.target.value)}
                                className={INPUT_CLASS}
                            />
                        </div>
                        <FilterSelect
                            label="Kelas"
                            value={kelasId}
                            onChange={setKelasId}
                            placeholder="Semua kelas"
                        >
                            {options.kelas.map((k) => (
                                <option key={k.id} value={String(k.id)}>
                                    {k.nama_kelas}
                                </option>
                            ))}
                        </FilterSelect>
                        <FilterSelect
                            label="Mata pelajaran"
                            value={mapelId}
                            onChange={setMapelId}
                            placeholder="Semua mapel"
                        >
                            {options.mapel.map((m) => (
                                <option key={m.id} value={String(m.id)}>
                                    {m.nama_mapel}
                                </option>
                            ))}
                        </FilterSelect>
                        <FilterSelect
                            label="Guru"
                            value={guruId}
                            onChange={setGuruId}
                            placeholder="Semua guru"
                        >
                            {options.guru.map((g) => (
                                <option key={g.id} value={String(g.id)}>
                                    {g.name}
                                </option>
                            ))}
                        </FilterSelect>
                        <FilterSelect
                            label="Status"
                            value={status}
                            onChange={setStatus}
                            placeholder="Semua status"
                        >
                            <option value="pending">Menunggu</option>
                            <option value="divalidasi">Divalidasi</option>
                            <option value="revisi">Revisi</option>
                        </FilterSelect>
                    </div>
                    <div className="mt-3 flex gap-2">
                        <button
                            type="button"
                            onClick={apply}
                            className="rounded-full bg-[#2563EB] px-4 py-2 text-xs font-semibold whitespace-nowrap text-white"
                        >
                            Tampilkan
                        </button>
                        <button
                            type="button"
                            onClick={reset}
                            className="rounded-full border border-[#E5E9F2] bg-white px-4 py-2 text-xs font-semibold whitespace-nowrap text-[#2563EB]"
                        >
                            Reset
                        </button>
                    </div>
                </section>

                <section className="flex flex-col gap-[19px] px-4 pt-3.5 pb-6 lg:px-8 lg:py-6">
                    <h2 className="text-[15px] font-bold whitespace-nowrap text-[#1A1D26] lg:text-lg">
                        Hasil Rekap
                    </h2>
                    {jurnals.data.length === 0 ? (
                        <EmptyState
                            title="Tidak ada data sesuai filter"
                            description="Coba ubah rentang tanggal atau filter lain."
                        />
                    ) : (
                        <div className="grid grid-cols-1 gap-[19px] md:grid-cols-2 xl:grid-cols-3">
                            {jurnals.data.map((j) => {
                                const st: ValidationStatus =
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
                                                    <StatusBadge status={st} />
                                                </div>
                                                <span className="truncate text-xs font-semibold text-[#1A1D26]">
                                                    {j.guru?.name ?? '-'} &bull;{' '}
                                                    {j.kelas?.nama_kelas ?? '-'}
                                                </span>
                                                <span className="line-clamp-2 text-xs font-normal text-[#6B7280]">
                                                    {j.mataPelajaran
                                                        ?.nama_mapel ??
                                                        '-'}{' '}
                                                    &bull; Validator:{' '}
                                                    {j.validator?.name ?? '-'}
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

                <div className="pb-6 print:hidden">
                    <SimplePager links={jurnals.links} />
                </div>
            </PageShell>
        </>
    );
}
