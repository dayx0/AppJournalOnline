import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ChevronDown, Save } from 'lucide-react';
import type { ReactNode } from 'react';
import InputError from '@/components/input-error';

interface Kelas {
    id: number;
    nama_kelas: string;
}

interface MataPelajaran {
    id: number;
    nama_mapel: string;
}

interface Props {
    kelas: Kelas[];
    mataPelajaran: MataPelajaran[];
}

interface FormData {
    kelas_id: string;
    mapel_id: string;
    tanggal: string;
    jam_mulai: string;
    jam_selesai: string;
    materi: string;
    kegiatan: string;
    catatan: string;
}

const INPUT_CLASS =
    'h-11 w-full rounded-[10px] border border-[#E5E9F2] bg-white px-3 text-[13px] font-normal text-[#1A1D26] outline-none placeholder:text-[#9CA3AF] focus:border-[#2563EB]';

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

function Counter({ value, max = 500 }: { value: string; max?: number }) {
    return (
        <div className="flex justify-end">
            <span className="text-[10px] font-normal whitespace-nowrap text-[#9CA3AF]">
                {value.length}/{max}
            </span>
        </div>
    );
}

export default function Create({ kelas, mataPelajaran }: Props) {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        kelas_id: '',
        mapel_id: '',
        tanggal: '',
        jam_mulai: '',
        jam_selesai: '',
        materi: '',
        kegiatan: '',
        catatan: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();

        post('/jurnal');
    }

    return (
        <>
            <Head title="Tambah Jurnal" />

            <div className="mx-auto w-full max-w-2xl font-[Inter,system-ui,sans-serif] lg:max-w-4xl lg:rounded-2xl lg:bg-[#F6F8FC] lg:py-2">
                {/* Header: kembali + judul tengah di mobile,
                    judul + subjudul rata kiri di desktop */}
                <section className="flex items-center justify-between gap-3 px-4 pt-2 pb-1 lg:justify-start lg:px-8 lg:pt-6">
                    <Link
                        href="/jurnal"
                        aria-label="Kembali ke daftar jurnal"
                        className="flex h-9 w-9 shrink-0 items-center justify-center lg:hidden"
                    >
                        <ArrowLeft className="h-[22px] w-[22px] text-[#1A1D26]" />
                    </Link>
                    <div className="min-w-0 flex-1 text-center lg:text-left">
                        <h1 className="text-base font-bold whitespace-nowrap text-[#1A1D26] lg:text-2xl">
                            Tambah Jurnal
                        </h1>
                        <p className="mt-0.5 hidden text-sm font-normal text-[#6B7280] lg:block">
                            Lengkapi detail jurnal mengajar di bawah ini.
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
                    {/* Kolom kiri: Kelas, Mapel, Tanggal, Jam */}
                    <div className="flex flex-col gap-3 lg:gap-4">
                        <Field
                            label="Kelas *"
                            htmlFor="kelas_id"
                            error={errors.kelas_id}
                        >
                            <div className="relative">
                                <select
                                    id="kelas_id"
                                    value={data.kelas_id}
                                    onChange={(e) =>
                                        setData('kelas_id', e.target.value)
                                    }
                                    className={`${INPUT_CLASS} appearance-none pr-9 ${data.kelas_id === '' ? 'text-[#9CA3AF]' : ''}`}
                                >
                                    <option value="">Pilih Kelas</option>
                                    {kelas.map((item) => (
                                        <option key={item.id} value={item.id}>
                                            {item.nama_kelas}
                                        </option>
                                    ))}
                                </select>
                                <ChevronDown className="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-[#9CA3AF]" />
                            </div>
                        </Field>

                        <Field
                            label="Mata Pelajaran *"
                            htmlFor="mapel_id"
                            error={errors.mapel_id}
                        >
                            <div className="relative">
                                <select
                                    id="mapel_id"
                                    value={data.mapel_id}
                                    onChange={(e) =>
                                        setData('mapel_id', e.target.value)
                                    }
                                    className={`${INPUT_CLASS} appearance-none pr-9 ${data.mapel_id === '' ? 'text-[#9CA3AF]' : ''}`}
                                >
                                    <option value="">
                                        Pilih Mata Pelajaran
                                    </option>
                                    {mataPelajaran.map((item) => (
                                        <option key={item.id} value={item.id}>
                                            {item.nama_mapel}
                                        </option>
                                    ))}
                                </select>
                                <ChevronDown className="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-[#9CA3AF]" />
                            </div>
                        </Field>

                        <Field
                            label="Tanggal *"
                            htmlFor="tanggal"
                            error={errors.tanggal}
                        >
                            <input
                                id="tanggal"
                                type="date"
                                value={data.tanggal}
                                onChange={(e) =>
                                    setData('tanggal', e.target.value)
                                }
                                className={INPUT_CLASS}
                            />
                        </Field>

                        <div className="grid grid-cols-2 gap-3">
                            <Field
                                label="Jam Mulai *"
                                htmlFor="jam_mulai"
                                error={errors.jam_mulai}
                            >
                                <input
                                    id="jam_mulai"
                                    type="time"
                                    value={data.jam_mulai}
                                    onChange={(e) =>
                                        setData('jam_mulai', e.target.value)
                                    }
                                    className={INPUT_CLASS}
                                />
                            </Field>

                            <Field
                                label="Jam Selesai *"
                                htmlFor="jam_selesai"
                                error={errors.jam_selesai}
                            >
                                <input
                                    id="jam_selesai"
                                    type="time"
                                    value={data.jam_selesai}
                                    onChange={(e) =>
                                        setData('jam_selesai', e.target.value)
                                    }
                                    className={INPUT_CLASS}
                                />
                            </Field>
                        </div>
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
                        {processing ? 'Menyimpan...' : 'Simpan'}
                    </button>
                </form>
            </div>
        </>
    );
}
