import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    CircleCheck,
    Minus,
    Plus,
    Save,
    ShieldCheck,
} from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { formatTanggal } from '@/lib/format';
import type { Journal } from '@/types';

interface Props {
    journal: Journal;
}

interface AbsensiForm {
    hadir: string;
    izin: string;
    sakit: string;
    alpha: string;
}

type Tab = 'siswa' | 'ringkasan';

const STATUS_FIELDS: {
    key: keyof AbsensiForm;
    label: string;
    iconBg: string;
    iconText: string;
}[] = [
    {
        key: 'hadir',
        label: 'Hadir',
        iconBg: 'bg-[#E8F7EE]',
        iconText: 'text-[#16A34A]',
    },
    {
        key: 'izin',
        label: 'Izin',
        iconBg: 'bg-[#FFF4E0]',
        iconText: 'text-[#D97706]',
    },
    {
        key: 'sakit',
        label: 'Sakit',
        iconBg: 'bg-[#FDECEC]',
        iconText: 'text-[#DC2626]',
    },
    {
        key: 'alpha',
        label: 'Alpha',
        iconBg: 'bg-[#F3F4F6]',
        iconText: 'text-[#6B7280]',
    },
];

function toNumber(value: string): number {
    const parsed = parseInt(value, 10);

    return Number.isNaN(parsed) ? 0 : Math.max(0, parsed);
}

function StepperField({
    label,
    value,
    error,
    iconBg,
    iconText,
    onChange,
}: {
    label: string;
    value: string;
    error?: string;
    iconBg: string;
    iconText: string;
    onChange: (value: string) => void;
}) {
    const decrease = () => onChange(String(toNumber(value) - 1));
    const increase = () => onChange(String(toNumber(value) + 1));

    const buttonClass =
        'flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#E5E9F2] bg-white text-[#1A1D26] transition hover:bg-[#F6F8FC]';

    return (
        <div className="flex flex-col gap-1.5">
            <div className="flex items-center gap-2.5 rounded-xl border border-[#E5E9F2] bg-white p-2.5">
                <span
                    className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-[13px] font-bold ${iconBg} ${iconText}`}
                >
                    {label.charAt(0)}
                </span>
                <span className="min-w-0 flex-1 truncate text-[13px] font-semibold text-[#1A1D26]">
                    {label}
                </span>
                <div className="flex shrink-0 items-center gap-2">
                    <button
                        type="button"
                        onClick={decrease}
                        aria-label={`Kurangi ${label}`}
                        className={buttonClass}
                    >
                        <Minus className="h-4 w-4" />
                    </button>
                    <input
                        type="number"
                        min={0}
                        value={value}
                        aria-label={`Jumlah ${label}`}
                        onChange={(e) => onChange(e.target.value)}
                        className="h-8 w-14 rounded-lg border border-[#E5E9F2] bg-white text-center text-[13px] font-bold text-[#1A1D26] outline-none focus:border-[#2563EB]"
                    />
                    <button
                        type="button"
                        onClick={increase}
                        aria-label={`Tambah ${label}`}
                        className={buttonClass}
                    >
                        <Plus className="h-4 w-4" />
                    </button>
                </div>
            </div>
            <InputError message={error} className="px-1 text-xs" />
        </div>
    );
}

export default function Absensi({ journal }: Props) {
    const { data, setData, put, processing, errors } = useForm<AbsensiForm>({
        hadir: journal.absensi?.hadir?.toString() ?? '',
        izin: journal.absensi?.izin?.toString() ?? '',
        sakit: journal.absensi?.sakit?.toString() ?? '',
        alpha: journal.absensi?.alpha?.toString() ?? '',
    });
    const [tab, setTab] = useState<Tab>('siswa');

    function submit(e: React.FormEvent) {
        e.preventDefault();

        put(`/jurnal/${journal.id}/absensi`);
    }

    const validationStatus = journal.status ?? 'pending';
    const isValidated = validationStatus === 'divalidasi';
    const needRevisi = validationStatus === 'revisi';

    const total = (Object.keys(data) as (keyof AbsensiForm)[]).reduce(
        (sum, key) => sum + toNumber(data[key]),
        0,
    );

    const tabButtonClass = (isActive: boolean) =>
        `flex-1 rounded-[18px] py-2 text-xs whitespace-nowrap ${
            isActive
                ? 'bg-[#2563EB] font-bold text-white'
                : 'border border-[#E5E9F2] bg-white font-normal text-[#6B7280]'
        }`;

    return (
        <>
            <Head title="Kelola Absensi" />

            <div className="mx-auto w-full max-w-2xl font-[Inter,system-ui,sans-serif] lg:max-w-6xl lg:rounded-2xl lg:bg-[#F6F8FC] lg:py-2">
                {/* Header */}
                <section className="flex items-center gap-3 px-4 pt-2 pb-1 lg:px-8 lg:pt-6">
                    <Link
                        href={`/jurnal/${journal.id}`}
                        aria-label="Kembali ke detail jurnal"
                        className="flex h-9 w-9 shrink-0 items-center justify-center lg:hidden"
                    >
                        <ArrowLeft className="h-[22px] w-[22px] text-[#1A1D26]" />
                    </Link>
                    <div className="min-w-0 flex-1 text-center lg:text-left">
                        <h1 className="text-base font-bold whitespace-nowrap text-[#1A1D26] lg:text-2xl">
                            Kelola Absensi
                        </h1>
                        <p className="mt-0.5 hidden text-sm font-normal text-[#6B7280] lg:block">
                            Atur rekap kehadiran siswa untuk jurnal ini.
                        </p>
                    </div>
                    <span
                        className="w-9 shrink-0 lg:hidden"
                        aria-hidden="true"
                    />
                </section>

                <form
                    onSubmit={submit}
                    className="grid grid-cols-1 gap-3 px-4 pt-3 pb-6 lg:grid-cols-5 lg:gap-4 lg:px-8 lg:py-6"
                >
                    <div className="flex flex-col gap-3 lg:col-span-2 lg:self-start">
                        {/* Info jurnal */}
                        <section className="flex flex-col gap-3 rounded-xl bg-[#EFF4FF] p-[18px]">
                            <p className="text-[13px] font-bold text-[#1E40AF] lg:text-sm">
                                {journal.kelas?.nama_kelas ?? '-'} -{' '}
                                {journal.mataPelajaran?.nama_mapel ?? '-'}
                            </p>
                            <p className="text-[11px] font-normal whitespace-nowrap text-[#6B7280] lg:text-xs">
                                {formatTanggal(journal.tanggal)} -{' '}
                                {journal.jam_mulai.slice(0, 5)} -{' '}
                                {journal.jam_selesai.slice(0, 5)}
                            </p>
                        </section>

                        {/* Status validasi */}
                        <section
                            className={`flex items-center gap-2.5 rounded-xl p-3 ${
                                needRevisi
                                    ? 'bg-[#FDECEC]'
                                    : isValidated
                                      ? 'bg-[#E8F7EE]'
                                      : 'bg-[#FFF4E0]'
                            }`}
                        >
                            <ShieldCheck
                                className={`h-6 w-6 shrink-0 ${needRevisi ? 'text-[#DC2626]' : isValidated ? 'text-[#16A34A]' : 'text-[#D97706]'}`}
                            />
                            <div className="flex min-w-0 flex-1 flex-col gap-2.5">
                                <span className="text-[11px] font-normal whitespace-nowrap text-[#6B7280]">
                                    Status validasi
                                </span>
                                <span className="text-xs font-bold text-[#1A1D26]">
                                    {needRevisi
                                        ? 'Perlu revisi'
                                        : isValidated
                                          ? 'Sudah divalidasi'
                                          : 'Menunggu validasi'}
                                </span>
                                {needRevisi && journal.validation_note && (
                                    <span className="text-xs font-normal whitespace-pre-line text-[#991B1B]">
                                        {journal.validation_note}
                                    </span>
                                )}
                            </div>
                            <CircleCheck
                                className={`h-[22px] w-[22px] shrink-0 ${needRevisi ? 'text-[#DC2626]' : isValidated ? 'text-[#16A34A]' : 'text-[#D97706]'}`}
                            />
                        </section>
                    </div>

                    <div className="flex flex-col gap-3 lg:col-span-3">
                        {/* Tabs */}
                        <div className="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                onClick={() => setTab('siswa')}
                                className={tabButtonClass(tab === 'siswa')}
                            >
                                Daftar Siswa
                            </button>
                            <button
                                type="button"
                                onClick={() => setTab('ringkasan')}
                                className={tabButtonClass(tab === 'ringkasan')}
                            >
                                Ringkasan
                            </button>
                        </div>

                        {tab === 'siswa' ? (
                            <>
                                <div className="rounded-xl border border-dashed border-[#C7D2FE] bg-[#EFF4FF] p-3">
                                    <p className="text-xs font-normal text-[#1E40AF]">
                                        Daftar per nama siswa akan tampil
                                        setelah modul data siswa tersedia. Untuk
                                        saat ini, kelola rekap angka kehadiran
                                        di bawah ini.
                                    </p>
                                </div>

                                <div className="flex flex-col gap-3">
                                    {STATUS_FIELDS.map((field) => (
                                        <StepperField
                                            key={field.key}
                                            label={field.label}
                                            value={data[field.key]}
                                            error={errors[field.key]}
                                            iconBg={field.iconBg}
                                            iconText={field.iconText}
                                            onChange={(value) =>
                                                setData(field.key, value)
                                            }
                                        />
                                    ))}
                                </div>

                                <div className="flex items-center justify-between rounded-xl border border-[#E5E9F2] bg-white px-3.5 py-3">
                                    <span className="text-[13px] font-semibold text-[#1A1D26]">
                                        Total siswa
                                    </span>
                                    <span className="text-[13px] font-extrabold text-[#2563EB]">
                                        {total} siswa
                                    </span>
                                </div>
                            </>
                        ) : (
                            <div className="grid grid-cols-2 gap-2.5 lg:grid-cols-4">
                                {STATUS_FIELDS.map((field) => (
                                    <div
                                        key={field.key}
                                        className="flex flex-col items-center gap-1 rounded-xl border border-[#E5E9F2] bg-white px-2 py-3"
                                    >
                                        <span
                                            className={`text-xl leading-7 font-extrabold ${field.iconText}`}
                                        >
                                            {data[field.key] === ''
                                                ? '–'
                                                : toNumber(data[field.key])}
                                        </span>
                                        <span className="text-[11px] font-medium text-[#6B7280] capitalize">
                                            {field.label}
                                        </span>
                                    </div>
                                ))}
                                <div className="col-span-2 flex items-center justify-between rounded-xl bg-[#2563EB] px-4 py-3 lg:col-span-4">
                                    <span className="text-[13px] font-semibold text-white">
                                        Total kehadiran tercatat
                                    </span>
                                    <span className="text-[13px] font-extrabold text-white">
                                        {total} siswa
                                    </span>
                                </div>
                            </div>
                        )}

                        <button
                            type="submit"
                            disabled={processing}
                            className="flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-[#2563EB] text-sm font-bold whitespace-nowrap text-white transition enabled:hover:bg-[#1D4ED8] disabled:opacity-60"
                        >
                            <Save className="h-4 w-4 text-white" />
                            {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                        </button>
                    </div>
                </form>
            </div>
        </>
    );
}
