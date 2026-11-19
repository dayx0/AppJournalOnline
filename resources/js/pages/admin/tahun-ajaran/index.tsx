import { Head, router, useForm } from '@inertiajs/react';
import { CalendarDays, ChevronDown, KeyRound, Printer } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import FormField, { INPUT_CLASS } from '@/components/form-field';
import PageShell from '@/components/page-shell';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tahun Ajaran', href: '/admin/tahun-ajaran' },
];

type Tahun = {
    id: number;
    nama: string;
    semester_aktif: string;
    is_aktif: boolean;
    locked_at: string | null;
};

type Kredensial = {
    id: number;
    name: string;
    email: string;
    password: string;
};

export default function TahunAjaranIndex({
    tahuns,
    aktif,
    stats,
    kredensial,
}: {
    tahuns: Tahun[];
    aktif: Tahun | null;
    stats: { mpk: number; jurnal_terkunci: number };
    kredensial?: Kredensial[] | null;
}) {
    const { data, setData, post, errors, processing } = useForm({
        nama: '',
        semester: 'ganjil',
    });

    function bukaTahunBaru(e: React.FormEvent) {
        e.preventDefault();

        if (
            !confirm(
                `Buka tahun ${data.nama} (${data.semester})? Tahun ${aktif?.nama ?? '-'} akan DIKUNCI TOTAL dan semua slot jadwal aktif dinonaktifkan. Tidak bisa dibatalkan dari UI.`,
            )
        ) {
            return;
        }

        post('/admin/tahun-ajaran');
    }

    function resetMpk() {
        if (
            !confirm(
                `Reset password ${stats.mpk} akun MPK? Password baru hanya tampil SEKALI — cetak/segera bagikan sebelum pindah halaman.`,
            )
        ) {
            return;
        }

        router.post('/admin/tahun-ajaran/reset-mpk', {});
    }

    return (
        <>
            <Head title="Tahun Ajaran" />
            <PageShell
                title="Tahun Ajaran"
                subtitle={
                    aktif
                        ? `Berjalan: ${aktif.nama} • semester ${aktif.semester_aktif}`
                        : 'Belum ada tahun aktif'
                }
            >
                {/* Status berjalan */}
                <section className="px-4 pt-3 lg:px-8">
                    <div className="grid grid-cols-2 gap-3 lg:grid-cols-3">
                        <div className="flex items-center gap-2.5 rounded-xl bg-white p-[14px] shadow-[0px_2px_10px_0px_#0F172A0D]">
                            <span className="flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-full bg-[#EFF4FF] text-[#2563EB]">
                                <CalendarDays className="h-4 w-4" />
                            </span>
                            <div className="flex min-w-0 flex-col">
                                <span className="truncate text-[13px] font-semibold text-[#1A1D26]">
                                    {aktif?.nama ?? '-'}
                                </span>
                                <span className="text-[11px] text-[#6B7280]">
                                    Tahun aktif
                                </span>
                            </div>
                        </div>
                        <div className="flex items-center gap-2.5 rounded-xl bg-white p-[14px] shadow-[0px_2px_10px_0px_#0F172A0D]">
                            <span className="flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-full bg-[#EFF4FF] text-[15px] font-bold text-[#2563EB]">
                                {stats.jurnal_terkunci}
                            </span>
                            <div className="flex min-w-0 flex-col">
                                <span className="truncate text-[13px] font-semibold text-[#1A1D26]">
                                    Jurnal terkunci
                                </span>
                                <span className="text-[11px] text-[#6B7280]">
                                    Arsip read-only
                                </span>
                            </div>
                        </div>
                        <div className="flex items-center gap-2.5 rounded-xl bg-white p-[14px] shadow-[0px_2px_10px_0px_#0F172A0D]">
                            <span className="flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-full bg-[#EFF4FF] text-[15px] font-bold text-[#2563EB]">
                                {stats.mpk}
                            </span>
                            <div className="flex min-w-0 flex-col">
                                <span className="truncate text-[13px] font-semibold text-[#1A1D26]">
                                    Akun MPK
                                </span>
                                <span className="text-[11px] text-[#6B7280]">
                                    1 akun per rombel
                                </span>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Buka tahun baru */}
                <section className="px-4 pt-3 lg:px-8">
                    <div className="flex flex-col gap-2.5 rounded-2xl border border-[#E5E9F2] bg-white p-3.5">
                        <h2 className="text-[15px] font-bold text-[#1A1D26]">
                            Proses Tahun Ajaran Baru
                        </h2>
                        <p className="text-xs text-[#6B7280]">
                            Mengunci total tahun {aktif?.nama ?? '-'} (jurnal
                            read-only, jadwal dinonaktifkan) dan membuka tahun
                            baru. Kelas & akun tidak di-rename/dipindah.
                        </p>
                        <form
                            onSubmit={bukaTahunBaru}
                            className="grid grid-cols-1 gap-3 sm:grid-cols-3"
                        >
                            <FormField
                                label="Tahun baru *"
                                htmlFor="nama"
                                error={errors.nama}
                            >
                                <input
                                    id="nama"
                                    value={data.nama}
                                    onChange={(e) =>
                                        setData('nama', e.target.value)
                                    }
                                    placeholder="Contoh: 2027/2028"
                                    className={INPUT_CLASS}
                                />
                            </FormField>
                            <FormField
                                label="Semester awal *"
                                htmlFor="semester"
                                error={errors.semester}
                            >
                                <div className="relative">
                                    <select
                                        id="semester"
                                        value={data.semester}
                                        onChange={(e) =>
                                            setData('semester', e.target.value)
                                        }
                                        className={`${INPUT_CLASS} appearance-none pr-9`}
                                    >
                                        <option value="ganjil">Ganjil</option>
                                        <option value="genap">Genap</option>
                                    </select>
                                    <ChevronDown className="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-[#9CA3AF]" />
                                </div>
                            </FormField>
                            <div className="flex items-end">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="flex h-11 w-full items-center justify-center rounded-[10px] bg-[#2563EB] text-sm font-bold whitespace-nowrap text-white transition enabled:hover:bg-[#1D4ED8] disabled:opacity-60"
                                >
                                    {processing
                                        ? 'Memproses...'
                                        : 'Buka & Kunci'}
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

                {/* Reset MPK */}
                <section className="px-4 pt-3 lg:px-8">
                    <div className="flex flex-col gap-2.5 rounded-2xl border border-[#E5E9F2] bg-white p-3.5">
                        <h2 className="text-[15px] font-bold text-[#1A1D26]">
                            Serah Terima MPK
                        </h2>
                        <p className="text-xs text-[#6B7280]">
                            Reset password acak untuk {stats.mpk} akun MPK.
                            Pengurus baru wajib ganti password saat login
                            pertama. Password sementara hanya tampil sekali —
                            cetak & segel per amplop kelas.
                        </p>
                        <button
                            type="button"
                            onClick={resetMpk}
                            className="inline-flex h-11 shrink-0 items-center justify-center gap-2 self-start rounded-[10px] bg-[#1A1D26] px-4 text-xs font-semibold whitespace-nowrap text-white transition hover:bg-black"
                        >
                            <KeyRound className="h-4 w-4" />
                            Reset Password {stats.mpk} Akun MPK
                        </button>

                        {kredensial && kredensial.length > 0 && (
                            <div className="mt-1 rounded-xl border border-amber-200 bg-amber-50 p-3">
                                <div className="flex items-center justify-between gap-2 print:hidden">
                                    <p className="text-xs font-bold text-amber-800">
                                        {kredensial.length} password sementara —
                                        cetak sekarang, hilang saat pindah
                                        halaman.
                                    </p>
                                    <button
                                        type="button"
                                        onClick={() => window.print()}
                                        className="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-[#2563EB] px-3 py-1.5 text-xs font-semibold text-white"
                                    >
                                        <Printer className="h-3.5 w-3.5" />
                                        Cetak
                                    </button>
                                </div>
                                <div className="mt-2 overflow-x-auto">
                                    <table className="w-full text-left text-xs">
                                        <thead>
                                            <tr className="text-[#6B7280]">
                                                <th className="py-1 pr-3">
                                                    Akun
                                                </th>
                                                <th className="py-1 pr-3">
                                                    Email
                                                </th>
                                                <th className="py-1">
                                                    Password sementara
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {kredensial.map((k) => (
                                                <tr
                                                    key={k.id}
                                                    className="border-t border-amber-100 font-mono"
                                                >
                                                    <td className="py-1 pr-3">
                                                        {k.name}
                                                    </td>
                                                    <td className="py-1 pr-3">
                                                        {k.email}
                                                    </td>
                                                    <td className="py-1 font-bold">
                                                        {k.password}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}
                    </div>
                </section>

                {/* Daftar tahun */}
                <section className="flex flex-col gap-[19px] px-4 pt-3.5 pb-6 lg:px-8 lg:py-6">
                    <h2 className="text-[15px] font-bold whitespace-nowrap text-[#1A1D26] lg:text-lg">
                        Riwayat Tahun Ajaran
                    </h2>
                    {tahuns.length === 0 ? (
                        <EmptyState
                            title="Belum ada tahun ajaran"
                            description="Jalankan seeder atau buka tahun baru."
                        />
                    ) : (
                        <div className="grid grid-cols-1 gap-[19px] md:grid-cols-2">
                            {tahuns.map((t) => (
                                <article
                                    key={t.id}
                                    className="flex items-center gap-2.5 rounded-xl bg-white p-[14px] shadow-[0px_2px_10px_0px_#0F172A0D]"
                                >
                                    <span className="flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-full bg-[#EFF4FF] text-[15px] font-bold text-[#2563EB]">
                                        {t.nama.slice(2, 4)}
                                    </span>
                                    <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                                        <span className="truncate text-[13px] font-semibold text-[#1A1D26]">
                                            {t.nama} • {t.semester_aktif}
                                        </span>
                                        <span className="truncate text-[11px] font-normal text-[#6B7280]">
                                            {t.is_aktif
                                                ? 'Berjalan (bisa diubah)'
                                                : t.locked_at
                                                  ? 'Dikunci (read-only)'
                                                  : 'Nonaktif'}
                                        </span>
                                    </div>
                                    <span
                                        className={`shrink-0 rounded-full px-3 py-1 text-[11px] font-bold whitespace-nowrap ${
                                            t.is_aktif
                                                ? 'bg-[#E8F7EE] text-[#16A34A]'
                                                : 'bg-[#F3F4F6] text-[#6B7280]'
                                        }`}
                                    >
                                        {t.is_aktif ? 'Aktif' : 'Arsip'}
                                    </span>
                                </article>
                            ))}
                        </div>
                    )}
                </section>
            </PageShell>
        </>
    );
}

TahunAjaranIndex.layout = {
    breadcrumbs,
};
