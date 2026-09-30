import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, SquarePen } from 'lucide-react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';

interface Journal {
    id: number;
    guru_id: number;
    kelas_id: number;
    mapel_id: number;
    tanggal: string;
    jam_mulai: string;
    jam_selesai: string;
    // Nullable: slot menunggu belum punya isi (lihat migration
    // make_jurnal_materi_nullable). Form harus tahan terhadap null.
    materi: string | null;
    kegiatan: string | null;
    catatan: string | null;
    status: string;
    kelas?: { nama_kelas: string } | null;
    mataPelajaran?: { nama_mapel: string } | null;
}

interface Props {
    journal: Journal;
}

interface FormData {
    materi: string;
    kegiatan: string;
    catatan: string;
}

function Field({
    label,
    htmlFor,
    error,
    children,
}: {
    label: string;
    htmlFor: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div className="flex flex-col gap-1.5">
            <label
                htmlFor={htmlFor}
                className="text-xs font-semibold whitespace-nowrap text-[#1A1D26]"
            >
                {label}
            </label>
            {children}
            <InputError message={error} className="text-xs" />
        </div>
    );
}

function Counter({ value, max = 500 }: { value: string | null; max?: number }) {
    return (
        <div className="flex justify-end">
            <span className="text-[10px] font-normal whitespace-nowrap text-[#9CA3AF]">
                {(value ?? '').length}/{max}
            </span>
        </div>
    );
}

export default function Edit({ journal }: Props) {
    const isSlotKosong = journal.materi === null && journal.kegiatan === null;
    const { data, setData, put, processing, errors } = useForm<FormData>({
        // ?? '' = pengaman utama: null dari DB tidak boleh masuk ke state.
        materi: journal.materi ?? '',
        kegiatan: journal.kegiatan ?? '',
        catatan: journal.catatan ?? '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();

        put(`/jurnal/${journal.id}`);
    }

    return (
        <>
            <Head title="Edit Jurnal" />

            <div className="mx-auto w-full max-w-2xl font-[Inter,system-ui,sans-serif] lg:max-w-4xl lg:rounded-2xl lg:bg-[#F6F8FC] lg:py-2">
                {/* Header: kembali + judul tengah di mobile,
                    judul + subjudul rata kiri di desktop */}
                <section className="flex items-center justify-between gap-3 px-4 pt-2 pb-1 lg:justify-start lg:px-8 lg:pt-6">
                    <Link
                        href={`/jurnal/${journal.id}`}
                        aria-label="Kembali ke detail jurnal"
                        className="flex h-9 w-9 shrink-0 items-center justify-center lg:hidden"
                    >
                        <ArrowLeft className="h-[22px] w-[22px] text-[#1A1D26]" />
                    </Link>
                    <div className="min-w-0 flex-1 text-center lg:text-left">
                        <h1 className="text-base font-bold whitespace-nowrap text-[#1A1D26] lg:text-2xl">
                            {isSlotKosong ? 'Isi Jurnal' : 'Edit Jurnal'}
                        </h1>
                        <p className="mt-0.5 hidden text-sm font-normal text-[#6B7280] lg:block">
                            {isSlotKosong
                                ? 'Isi slot jadwal mengajar di bawah ini.'
                                : 'Perbarui detail jurnal mengajar di bawah ini.'}
                        </p>
                    </div>
                    <span
                        className="w-9 shrink-0 lg:hidden"
                        aria-hidden="true"
                    />
                </section>

                <form
                    onSubmit={submit}
                    className="grid grid-cols-1 gap-3 px-4 pt-2 pb-6 lg:grid-cols-2 lg:gap-4 lg:px-8 lg:py-6"
                >
                    {/* Kolom kiri: identitas slot (read-only, dari jadwal) + absensi */}
                    <div className="flex flex-col gap-3 lg:gap-4">
                        <div className="flex flex-col gap-1.5 rounded-[10px] border border-[#E5E9F2] bg-[#F6F8FC] p-3">
                            <span className="text-xs font-semibold whitespace-nowrap text-[#1A1D26]">
                                {journal.kelas?.nama_kelas ?? '-'} &bull;{' '}
                                {journal.mataPelajaran?.nama_mapel ?? '-'}
                            </span>
                            <span className="text-xs font-normal text-[#6B7280]">
                                {journal.tanggal} &bull;{' '}
                                {journal.jam_mulai.slice(0, 5)} –{' '}
                                {journal.jam_selesai.slice(0, 5)}
                            </span>
                            <span className="text-[11px] font-normal text-[#9CA3AF]">
                                Kelas, mapel, dan jam berasal dari jadwal dan
                                tidak dapat diubah di sini.
                            </span>
                        </div>

                        <Link
                            href={`/jurnal/${journal.id}/absensi`}
                            className="flex items-center justify-center gap-2 rounded-[10px] border border-[#E5E9F2] bg-white p-3.5 text-sm font-semibold whitespace-nowrap text-[#2563EB]"
                        >
                            <SquarePen className="h-4 w-4" />
                            Kelola Absensi terpisah
                        </Link>
                    </div>

                    {/* Kolom kanan: Materi, Kegiatan, Catatan */}
                    <div className="flex flex-col gap-3 lg:gap-4">
                        <Field
                            label="Materi *"
                            htmlFor="materi"
                            error={errors.materi}
                        >
                            <textarea
                                id="materi"
                                value={data.materi}
                                maxLength={500}
                                rows={3}
                                placeholder="Contoh: Penggunaan Laravel Blade..."
                                onChange={(e) =>
                                    setData('materi', e.target.value)
                                }
                                className="h-[84px] w-full resize-none rounded-[10px] border border-[#E5E9F2] bg-white p-3 text-[13px] font-normal text-[#1A1D26] outline-none placeholder:text-[#9CA3AF] focus:border-[#2563EB]"
                            />
                            <Counter value={data.materi} />
                        </Field>

                        <Field
                            label="Kegiatan *"
                            htmlFor="kegiatan"
                            error={errors.kegiatan}
                        >
                            <textarea
                                id="kegiatan"
                                value={data.kegiatan}
                                maxLength={500}
                                rows={3}
                                placeholder="Contoh: Pemaparan materi, diskusi, ..."
                                onChange={(e) =>
                                    setData('kegiatan', e.target.value)
                                }
                                className="h-[84px] w-full resize-none rounded-[10px] border border-[#E5E9F2] bg-white p-3 text-[13px] font-normal text-[#1A1D26] outline-none placeholder:text-[#9CA3AF] focus:border-[#2563EB]"
                            />
                            <Counter value={data.kegiatan} />
                        </Field>

                        <Field
                            label="Catatan (opsional)"
                            htmlFor="catatan"
                            error={errors.catatan}
                        >
                            <textarea
                                id="catatan"
                                value={data.catatan}
                                maxLength={500}
                                rows={3}
                                placeholder="Catatan tambahan..."
                                onChange={(e) =>
                                    setData('catatan', e.target.value)
                                }
                                className="h-[84px] w-full resize-none rounded-[10px] border border-[#E5E9F2] bg-white p-3 text-[13px] font-normal text-[#1A1D26] outline-none placeholder:text-[#9CA3AF] focus:border-[#2563EB]"
                            />
                            <Counter value={data.catatan} />
                        </Field>
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="flex h-12 w-full items-center justify-center gap-2 rounded-[10px] bg-[#2563EB] text-sm font-bold whitespace-nowrap text-white transition enabled:hover:bg-[#1D4ED8] disabled:opacity-60 lg:col-span-2"
                    >
                        <Save className="h-[18px] w-[18px] text-white" />
                        {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                    </button>
                </form>
            </div>
        </>
    );
}
