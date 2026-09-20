import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    BookOpen,
    Calendar,
    EllipsisVertical,
    Pencil,
    Search,
    SquarePen,
    Trash2,
    User,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import DetailRow from '@/components/detail-row';
import JournalHistoryTimeline from '@/components/journal-history-timeline';
import StatusBadge from '@/components/status-badge';
import { formatTanggal } from '@/lib/format';
import type {
    Journal,
    JournalHistory,
    StudentAttendance,
    StudentStatus,
    ValidationStatus,
} from '@/types';

interface Props {
    journal: Journal;
    students?: StudentAttendance[];
    histories?: JournalHistory[];
}

type Tab = 'absensi' | 'detail';
type StatusFilter = 'semua' | StudentStatus;

const STATUS_FILTERS: { key: StatusFilter; label: string }[] = [
    { key: 'semua', label: 'Semua' },
    { key: 'hadir', label: 'Hadir' },
    { key: 'izin', label: 'Izin' },
    { key: 'sakit', label: 'Sakit' },
    { key: 'alpha', label: 'Alpha' },
];

const STATUS_META: Record<
    StudentStatus,
    { label: string; badgeClass: string }
> = {
    hadir: {
        label: 'Hadir',
        badgeClass: 'bg-[#E8F7EE] text-[#16A34A]',
    },
    izin: {
        label: 'Izin',
        badgeClass: 'bg-[#FFF4E0] text-[#D97706]',
    },
    sakit: {
        label: 'Sakit',
        badgeClass: 'bg-[#FDECEC] text-[#DC2626]',
    },
    alpha: {
        label: 'Alpha',
        badgeClass: 'bg-[#F3F4F6] text-[#6B7280]',
    },
};

const AVATAR_COLORS = [
    'bg-[#DCE6FF] text-[#254EDB]',
    'bg-[#D8F0E0] text-[#15803D]',
    'bg-[#FFF0C8] text-[#B45309]',
    'bg-[#FDE0E0] text-[#DC2626]',
    'bg-[#E2E8FF] text-[#4338CA]',
    'bg-[#FFE8D6] text-[#C2410C]',
];

function avatarColorClass(nama: string): string {
    let hash = 0;

    for (let i = 0; i < nama.length; i++) {
        hash += nama.charCodeAt(i);
    }

    return AVATAR_COLORS[hash % AVATAR_COLORS.length];
}

export default function Show({
    journal,
    students = [],
    histories = [],
}: Props) {
    const { delete: destroy } = useForm();
    const [tab, setTab] = useState<Tab>('absensi');
    const [menuOpen, setMenuOpen] = useState(false);
    const [studentQuery, setStudentQuery] = useState('');
    const [statusFilter, setStatusFilter] = useState<StatusFilter>('semua');

    function hapusJurnal() {
        setMenuOpen(false);

        if (confirm('Yakin ingin menghapus jurnal ini?')) {
            destroy(`/jurnal/${journal.id}`);
        }
    }

    const validationStatus: ValidationStatus = journal.status ?? 'pending';

    const absensi = journal.absensi;
    const recap = [
        {
            label: 'Hadir',
            value: absensi?.hadir ?? '–',
            valueClass: 'text-[#16A34A]',
        },
        {
            label: 'Izin',
            value: absensi?.izin ?? '–',
            valueClass: 'text-[#D97706]',
        },
        {
            label: 'Sakit',
            value: absensi?.sakit ?? '–',
            valueClass: 'text-[#DC2626]',
        },
        {
            label: 'Alpha',
            value: absensi?.alpha ?? '–',
            valueClass: 'text-[#6B7280]',
        },
    ];

    const filteredStudents = useMemo(() => {
        const keyword = studentQuery.trim().toLowerCase();

        return students.filter((student) => {
            if (statusFilter !== 'semua' && student.status !== statusFilter) {
                return false;
            }

            if (keyword === '') {
                return true;
            }

            return `${student.nama} ${student.nis}`
                .toLowerCase()
                .includes(keyword);
        });
    }, [students, studentQuery, statusFilter]);

    const tabButtonClass = (isActive: boolean) =>
        `flex-1 rounded-lg p-2 text-[13px] whitespace-nowrap ${
            isActive
                ? 'bg-[#2563EB] font-semibold text-white'
                : 'bg-white font-medium text-[#6B7280]'
        }`;

    return (
        <>
            <Head title="Detail Jurnal" />

            <div className="mx-auto w-full max-w-2xl font-[Inter,system-ui,sans-serif] lg:max-w-6xl lg:rounded-2xl lg:bg-[#F6F8FC] lg:py-2">
                {/* Header */}
                <section className="flex items-center justify-between gap-3 px-4 pt-2 pb-1 lg:px-8 lg:pt-6">
                    <Link
                        href="/jurnal"
                        aria-label="Kembali ke daftar jurnal"
                        className="flex h-9 w-9 shrink-0 items-center justify-center lg:hidden"
                    >
                        <ArrowLeft className="h-[22px] w-[22px] text-[#1A1D26]" />
                    </Link>
                    <h1 className="min-w-0 flex-1 text-center text-[17px] font-semibold whitespace-nowrap text-[#1A1D26] lg:text-left lg:text-2xl lg:font-bold">
                        Detail Jurnal
                    </h1>
                    <div className="relative shrink-0">
                        <button
                            type="button"
                            onClick={() => setMenuOpen((open) => !open)}
                            aria-label="Menu jurnal"
                            aria-expanded={menuOpen}
                            className="flex h-9 w-9 items-center justify-center rounded-full transition hover:bg-white lg:bg-white"
                        >
                            <EllipsisVertical className="h-[22px] w-[22px] text-[#1A1D26]" />
                        </button>
                        {menuOpen && (
                            <>
                                <button
                                    type="button"
                                    aria-label="Tutup menu"
                                    onClick={() => setMenuOpen(false)}
                                    className="fixed inset-0 z-10 cursor-default"
                                />
                                <div className="absolute top-full right-0 z-20 mt-1 w-44 overflow-hidden rounded-xl border border-[#E5E9F2] bg-white py-1 shadow-[0px_8px_24px_0px_#0F172A1A]">
                                    <Link
                                        href={`/jurnal/${journal.id}/edit`}
                                        onClick={() => setMenuOpen(false)}
                                        className="flex items-center gap-2 px-4 py-2.5 text-[13px] font-medium text-[#1A1D26] transition hover:bg-[#F6F8FC]"
                                    >
                                        <Pencil className="h-4 w-4 text-[#6B7280]" />
                                        Edit Jurnal
                                    </Link>
                                    <button
                                        type="button"
                                        onClick={hapusJurnal}
                                        className="flex w-full items-center gap-2 px-4 py-2.5 text-left text-[13px] font-medium text-[#DC2626] transition hover:bg-[#FDECEC]"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                        Hapus Jurnal
                                    </button>
                                </div>
                            </>
                        )}
                    </div>
                </section>

                <div className="grid grid-cols-1 gap-3 px-4 pt-3 pb-6 lg:grid-cols-5 lg:gap-4 lg:px-8 lg:py-6">
                    {journal.status === 'revisi' && journal.validation_note && (
                        <div className="rounded-2xl border border-[#FECACA] bg-[#FEF2F2] p-3.5 lg:col-span-5">
                            <p className="text-[11px] font-semibold text-[#DC2626]">
                                Catatan revisi dari MPK
                                {journal.validator?.name
                                    ? ` • ${journal.validator.name}`
                                    : ''}
                            </p>
                            <p className="mt-1 text-[13px] font-normal whitespace-pre-line text-[#1A1D26]">
                                {journal.validation_note}
                            </p>
                        </div>
                    )}
                    {journal.status === 'divalidasi' &&
                        journal.validation_note && (
                            <div className="rounded-2xl border border-[#BBF7D0] bg-[#F0FDF4] p-3.5 lg:col-span-5">
                                <p className="text-[11px] font-semibold text-[#16A34A]">
                                    Catatan validasi dari MPK
                                    {journal.validator?.name
                                        ? ` • ${journal.validator.name}`
                                        : ''}
                                </p>
                                <p className="mt-1 text-[13px] font-normal whitespace-pre-line text-[#1A1D26]">
                                    {journal.validation_note}
                                </p>
                            </div>
                        )}
                    {/* Kartu ringkasan jurnal + tab */}
                    <section className="flex flex-col gap-2.5 rounded-2xl border border-[#E5E9F2] bg-white p-3.5 lg:col-span-2 lg:self-start">
                        <div className="flex items-center justify-between gap-2">
                            <span className="flex shrink-0 items-center gap-1.5 rounded-full bg-[#F6F8FC] px-2.5 py-1.5">
                                <Calendar className="h-3.5 w-3.5 text-[#6B7280]" />
                                <span className="text-xs font-semibold whitespace-nowrap text-[#1A1D26]">
                                    {formatTanggal(journal.tanggal)}
                                </span>
                            </span>
                            <StatusBadge status={validationStatus} />
                        </div>
                        <h2 className="text-[15px] font-bold text-[#1A1D26] lg:text-lg">
                            {journal.kelas?.nama_kelas ?? '-'} -{' '}
                            {journal.mataPelajaran?.nama_mapel ?? '-'}
                        </h2>
                        <div className="flex items-center gap-2">
                            <User className="h-3.5 w-3.5 shrink-0 text-[#6B7280]" />
                            <p className="truncate text-xs font-normal text-[#6B7280]">
                                Guru : {journal.guru?.name ?? '-'}
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            <BookOpen className="h-3.5 w-3.5 shrink-0 text-[#6B7280]" />
                            <p className="truncate text-xs font-normal text-[#6B7280]">
                                Mata Pelajaran :{' '}
                                {journal.mataPelajaran?.nama_mapel ?? '-'}
                            </p>
                        </div>
                        <div className="grid grid-cols-2 gap-2 rounded-xl bg-[#F6F8FC] p-1">
                            <button
                                type="button"
                                onClick={() => setTab('absensi')}
                                className={tabButtonClass(tab === 'absensi')}
                            >
                                Absensi Siswa
                            </button>
                            <button
                                type="button"
                                onClick={() => setTab('detail')}
                                className={tabButtonClass(tab === 'detail')}
                            >
                                Detail Jurnal
                            </button>
                        </div>
                    </section>

                    {/* Isi tab */}
                    <div className="lg:col-span-3">
                        {tab === 'absensi' ? (
                            <div className="flex flex-col gap-3">
                                <div className="flex items-center justify-between gap-2">
                                    <h3 className="text-sm font-bold whitespace-nowrap text-[#1A1D26] lg:text-base">
                                        Daftar Absensi Siswa
                                    </h3>
                                    <span className="text-xs font-normal whitespace-nowrap text-[#6B7280]">
                                        {students.length} siswa
                                    </span>
                                </div>

                                {/* Rekap agregat dari data asli */}
                                <div className="grid grid-cols-4 gap-2">
                                    {recap.map((item) => (
                                        <div
                                            key={item.label}
                                            className="flex flex-col items-center gap-0.5 rounded-xl border border-[#E5E9F2] bg-white px-2 py-2.5"
                                        >
                                            <span
                                                className={`text-lg leading-6 font-extrabold ${item.valueClass}`}
                                            >
                                                {item.value}
                                            </span>
                                            <span className="text-[11px] font-medium text-[#6B7280]">
                                                {item.label}
                                            </span>
                                        </div>
                                    ))}
                                </div>

                                {students.length === 0 ? (
                                    <div className="flex flex-col items-center gap-2 rounded-xl border border-[#E5E9F2] bg-white p-6 text-center">
                                        <p className="text-[13px] font-semibold text-[#1A1D26]">
                                            Daftar siswa belum tersedia
                                        </p>
                                        <p className="text-xs font-normal text-[#6B7280]">
                                            Belum ada tabel siswa di database,
                                            sehingga kehadiran per nama belum
                                            bisa ditampilkan. Rekap agregat di
                                            atas diambil dari data jurnal ini.
                                        </p>
                                    </div>
                                ) : (
                                    <>
                                        <label className="flex items-center gap-2 rounded-xl border border-[#E5E9F2] bg-white px-3 py-2.5">
                                            <Search className="h-4 w-4 shrink-0 text-[#9CA3AF]" />
                                            <input
                                                type="search"
                                                value={studentQuery}
                                                onChange={(e) =>
                                                    setStudentQuery(
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Cari nama siswa..."
                                                className="w-full bg-transparent text-[13px] font-normal text-[#1A1D26] outline-none placeholder:text-[#9CA3AF]"
                                            />
                                        </label>

                                        <div className="flex gap-2 overflow-x-auto">
                                            {STATUS_FILTERS.map((filter) => {
                                                const isActive =
                                                    statusFilter === filter.key;

                                                return (
                                                    <button
                                                        key={filter.key}
                                                        type="button"
                                                        onClick={() =>
                                                            setStatusFilter(
                                                                filter.key,
                                                            )
                                                        }
                                                        className={`shrink-0 rounded-full border px-3.5 py-[7px] text-xs whitespace-nowrap ${
                                                            isActive
                                                                ? 'border-[#2563EB] bg-[#2563EB] font-semibold text-white'
                                                                : 'border-[#E5E9F2] bg-white font-medium text-[#6B7280]'
                                                        }`}
                                                    >
                                                        {filter.label}
                                                    </button>
                                                );
                                            })}
                                        </div>

                                        {filteredStudents.length === 0 ? (
                                            <div className="flex flex-col items-center gap-2 rounded-xl border border-[#E5E9F2] bg-white p-6 text-center">
                                                <p className="text-[13px] font-semibold text-[#1A1D26]">
                                                    Tidak ada siswa yang cocok
                                                </p>
                                                <p className="text-xs font-normal text-[#6B7280]">
                                                    Coba ubah kata kunci atau
                                                    filter status.
                                                </p>
                                            </div>
                                        ) : (
                                            <div className="flex flex-col gap-2.5">
                                                {filteredStudents.map(
                                                    (student) => {
                                                        const meta =
                                                            STATUS_META[
                                                                student.status
                                                            ];

                                                        return (
                                                            <article
                                                                key={student.id}
                                                                className="flex items-center gap-2.5 rounded-[14px] border border-[#E5E9F2] bg-white p-2.5"
                                                            >
                                                                <span
                                                                    className={`flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-full text-[15px] font-bold ${avatarColorClass(student.nama)}`}
                                                                >
                                                                    {student.nama
                                                                        .charAt(
                                                                            0,
                                                                        )
                                                                        .toUpperCase()}
                                                                </span>
                                                                <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                                                                    <span className="truncate text-[13px] font-semibold text-[#1A1D26]">
                                                                        {
                                                                            student.nama
                                                                        }
                                                                    </span>
                                                                    <span className="text-[11px] font-normal whitespace-nowrap text-[#6B7280]">
                                                                        NIS.{' '}
                                                                        {
                                                                            student.nis
                                                                        }
                                                                    </span>
                                                                </div>
                                                                <span
                                                                    className={`shrink-0 rounded-full px-2.5 py-[5px] text-[11px] font-semibold whitespace-nowrap ${meta.badgeClass}`}
                                                                >
                                                                    •{' '}
                                                                    {meta.label}
                                                                </span>
                                                            </article>
                                                        );
                                                    },
                                                )}
                                            </div>
                                        )}
                                    </>
                                )}

                                <Link
                                    href={`/jurnal/${journal.id}/absensi`}
                                    className="flex w-full items-center justify-center gap-2 rounded-xl bg-[#2563EB] p-3.5 text-sm font-semibold whitespace-nowrap text-white transition enabled:hover:bg-[#1D4ED8]"
                                >
                                    <SquarePen className="h-4 w-4 text-white" />
                                    Kelola Absensi
                                </Link>
                            </div>
                        ) : (
                            <div className="flex flex-col gap-2.5">
                                <DetailRow
                                    label="Jam Pelajaran"
                                    value={`${journal.jam_mulai.slice(0, 5)} – ${journal.jam_selesai.slice(0, 5)}`}
                                />
                                <DetailRow
                                    label="Materi"
                                    value={journal.materi || '–'}
                                />
                                <DetailRow
                                    label="Kegiatan"
                                    value={journal.kegiatan || '–'}
                                />
                                <DetailRow
                                    label="Catatan"
                                    value={journal.catatan || '–'}
                                />
                            </div>
                        )}
                    </div>
                    <div className="lg:col-span-5">
                        <JournalHistoryTimeline histories={histories} />
                    </div>
                </div>
            </div>
        </>
    );
}
