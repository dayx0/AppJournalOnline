import { Head, Link, usePage } from '@inertiajs/react';
import { BookOpenText, Calendar, Presentation, Users } from 'lucide-react';
import StatCard from '@/components/stat-card';
import StatusBadge from '@/components/status-badge';
import { formatTanggal } from '@/lib/format';
import type { Auth, Journal, ValidationStatus } from '@/types';

interface Stats {
    totalJurnal: number;
    totalKelas: number;
    totalMapel: number;
    jurnalMingguIni: number;
}

interface Props {
    stats: Stats;
    recentJournals: Journal[];
}

export default function Dashboard({ stats, recentJournals = [] }: Props) {
    const { auth } = usePage().props as unknown as { auth: Auth };
    const firstName = auth.user.name.split(' ')[0] || auth.user.name;
    const roleLabel = auth.user.role === 'admin' ? 'Admin' : 'Guru';

    return (
        <>
            <Head title="Beranda" />

            <div className="mx-auto w-full max-w-2xl font-[Inter,system-ui,sans-serif] lg:max-w-5xl lg:rounded-2xl lg:bg-[#F6F8FC] lg:py-2">
                {/* Sapaan */}
                <section className="flex flex-col gap-[11px] px-5 pt-1.5 lg:gap-2 lg:px-8 lg:pt-6">
                    <h1 className="text-[20px] font-bold whitespace-nowrap text-[#1A1D26] lg:text-[28px]">
                        Halo, {firstName}
                    </h1>
                    <p className="text-[12px] font-normal whitespace-nowrap text-[#6B7280] lg:text-sm">
                        {roleLabel} &bull; AppJurnalOnline
                    </p>
                </section>

                {/* Banner */}
                <section className="px-5 pt-3 pb-1 lg:px-8 lg:pt-5">
                    <div className="flex items-center gap-3 rounded-2xl bg-[#EFF4FF] p-4 lg:gap-5 lg:p-6">
                        <div className="flex min-w-0 flex-1 flex-col gap-[15px] lg:gap-2">
                            <p className="text-sm font-bold text-[#1E40AF] lg:text-lg">
                                Semangat mengajar hari ini!
                            </p>
                            <p className="text-xs font-normal text-[#6B7280] lg:text-sm">
                                Jurnal dan absensi siswa kini lebih mudah
                                dikelola.
                            </p>
                        </div>
                        <div className="flex h-[72px] w-[72px] shrink-0 items-center justify-center rounded-xl bg-[#DBEAFE] lg:h-24 lg:w-24">
                            <Presentation className="h-8 w-8 text-[#2563EB] lg:h-11 lg:w-11" />
                        </div>
                    </div>
                </section>

                {/* Statistik: 2 kolom di mobile, 4 kolom di desktop */}
                <section className="grid grid-cols-2 gap-3 px-5 pt-2.5 pb-1 lg:grid-cols-4 lg:gap-4 lg:px-8 lg:pt-5">
                    <StatCard
                        label="Total Jurnal"
                        value={String(stats.totalJurnal)}
                        sub={`${stats.jurnalMingguIni} dari minggu ini`}
                        icon={BookOpenText}
                        iconWrapperClass="bg-[#EFF4FF]"
                        iconClass="text-[#2563EB]"
                    />
                    {/*
                     * Belum ada tabel siswa di database,
                     * jadi kartu ini menampilkan tanda strip.
                     */}
                    <StatCard
                        label="Jumlah Siswa"
                        value="–"
                        sub="Belum ada data siswa"
                        icon={Users}
                        iconWrapperClass="bg-[#E8F7EE]"
                        iconClass="text-[#16A34A]"
                    />
                    <StatCard
                        label="Total Kelas"
                        value={String(stats.totalKelas)}
                        sub="Data master"
                        icon={Presentation}
                        iconWrapperClass="bg-[#FFF4E0]"
                        iconClass="text-[#D97706]"
                    />
                    <StatCard
                        label="Mata Pelajaran"
                        value={String(stats.totalMapel)}
                        sub="Data master"
                        icon={Calendar}
                        iconWrapperClass="bg-[#FDECEC]"
                        iconClass="text-[#DC2626]"
                    />
                </section>

                {/* Jurnal Terbaru: 1 kolom di mobile, 2 kolom di desktop */}
                <section className="flex flex-col gap-[18px] px-5 py-3.5 pb-6 lg:px-8 lg:py-6">
                    <div className="flex items-center justify-between">
                        <h2 className="text-[15px] font-bold whitespace-nowrap text-[#1A1D26] lg:text-lg">
                            Jurnal Terbaru
                        </h2>
                        <Link
                            href="/jurnal"
                            className="text-xs font-semibold whitespace-nowrap text-[#2563EB] lg:text-sm"
                        >
                            Lihat Semua &gt;
                        </Link>
                    </div>

                    {recentJournals.length === 0 ? (
                        <div className="flex flex-col items-center gap-2 rounded-[14px] bg-white p-6 text-center shadow-[0px_2px_10px_0px_#0F172A0D]">
                            <p className="text-[13px] font-semibold text-[#1A1D26]">
                                Belum ada jurnal
                            </p>
                            <p className="text-xs font-normal text-[#6B7280]">
                                Mulai catat kegiatan mengajar hari ini.
                            </p>
                            <Link
                                href="/jurnal/create"
                                className="mt-1 rounded-full bg-[#2563EB] px-4 py-2 text-xs font-semibold text-white"
                            >
                                + Buat Jurnal
                            </Link>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 gap-3 lg:grid-cols-2 lg:gap-4">
                            {recentJournals.map((journal, index) => {
                                const status: ValidationStatus =
                                    journal.status ?? 'pending';

                                return (
                                    <article
                                        key={journal.id}
                                        className="flex items-center gap-2.5 rounded-[14px] bg-white p-3 shadow-[0px_2px_10px_0px_#0F172A0D]"
                                    >
                                        <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#F6F8FC] text-[13px] font-bold text-[#1A1D26]">
                                            {index + 1}
                                        </span>
                                        <div className="flex min-w-0 flex-1 flex-col gap-[11px]">
                                            <span className="text-[11px] font-normal whitespace-nowrap text-[#9CA3AF]">
                                                {formatTanggal(journal.tanggal)}
                                            </span>
                                            <span className="truncate text-[13px] font-semibold text-[#1A1D26]">
                                                {journal.kelas?.nama_kelas ??
                                                    '-'}{' '}
                                                -{' '}
                                                {journal.mataPelajaran
                                                    ?.nama_mapel ?? '-'}
                                            </span>
                                        </div>
                                        <StatusBadge status={status} />
                                    </article>
                                );
                            })}
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}
