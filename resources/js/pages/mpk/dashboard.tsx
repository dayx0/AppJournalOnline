import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    BookOpenText,
    ChevronRight,
    CircleCheck,
    Clock,
} from 'lucide-react';
import EmptyState from '@/components/empty-state';
import PageShell from '@/components/page-shell';
import StatCard from '@/components/stat-card';
import { formatTanggal } from '@/lib/format';
import type { Journal } from '@/types';

type Stats = {
    pending: number;
    divalidasi: number;
    revisi: number;
    total: number;
};

export default function MpkDashboard({
    stats,
    butuhPerhatian,
    kelas = null,
}: {
    stats: Stats;
    butuhPerhatian: Journal[];
    kelas?: { id: number; nama_kelas: string } | null;
}) {
    return (
        <>
            <Head title="Dashboard MPK" />
            <PageShell
                framed={false}
                title="Dashboard MPK"
                subtitle={
                    kelas
                        ? `Kelas ${kelas.nama_kelas} • ${stats.pending} jurnal menunggu validasi`
                        : 'Belum di-assign ke kelas mana pun — hubungi admin'
                }
            >
                <section className="grid grid-cols-2 gap-3 px-5 pt-2.5 pb-1 lg:grid-cols-4 lg:gap-4 lg:px-8 lg:pt-5">
                    <StatCard
                        label="Menunggu Validasi"
                        value={String(stats.pending)}
                        sub="Perlu diperiksa"
                        icon={Clock}
                        iconWrapperClass="bg-[#FFF4E0]"
                        iconClass="text-[#D97706]"
                    />
                    <StatCard
                        label="Sudah Divalidasi"
                        value={String(stats.divalidasi)}
                        sub="Selesai diperiksa"
                        icon={CircleCheck}
                        iconWrapperClass="bg-[#E8F7EE]"
                        iconClass="text-[#16A34A]"
                    />
                    <StatCard
                        label="Perlu Revisi"
                        value={String(stats.revisi)}
                        sub="Dikembalikan ke guru"
                        icon={AlertTriangle}
                        iconWrapperClass="bg-[#FDECEC]"
                        iconClass="text-[#DC2626]"
                    />
                    <StatCard
                        label="Total Jurnal"
                        value={String(stats.total)}
                        sub="Seluruh jurnal guru"
                        icon={BookOpenText}
                        iconWrapperClass="bg-[#EFF4FF]"
                        iconClass="text-[#2563EB]"
                    />
                </section>

                <section className="flex flex-col gap-[18px] px-5 py-3.5 pb-6 lg:px-8 lg:py-6">
                    <h2 className="text-[15px] font-bold whitespace-nowrap text-[#1A1D26] lg:text-lg">
                        Butuh Perhatian (pending &gt; 24 jam)
                    </h2>

                    {butuhPerhatian.length === 0 ? (
                        <EmptyState
                            title="Tidak ada yang telat, kerja bagus!"
                            description="Semua jurnal sudah divalidasi tepat waktu."
                        />
                    ) : (
                        <div className="grid grid-cols-1 gap-3 lg:grid-cols-2">
                            {butuhPerhatian.map((j) => (
                                <article
                                    key={j.id}
                                    className="flex items-center gap-2 rounded-xl bg-white p-[14px] shadow-[0px_2px_10px_0px_#0F172A0D]"
                                >
                                    <Link
                                        href={`/mpk/jurnal/${j.id}`}
                                        className="flex min-w-0 flex-1 items-center gap-2"
                                    >
                                        <div className="flex min-w-0 flex-1 flex-col gap-[15px]">
                                            <span className="text-[13px] font-bold whitespace-nowrap text-[#1A1D26]">
                                                {formatTanggal(j.tanggal)}
                                            </span>
                                            <span className="truncate text-xs font-semibold text-[#1A1D26]">
                                                {j.guru?.name ?? '-'} &bull;{' '}
                                                {j.kelas?.nama_kelas ?? '-'}
                                            </span>
                                            <span className="line-clamp-2 text-xs font-normal text-[#6B7280]">
                                                Materi: {j.materi}
                                            </span>
                                        </div>
                                        <ChevronRight className="h-5 w-5 shrink-0 text-[#6B7280]" />
                                    </Link>
                                </article>
                            ))}
                        </div>
                    )}
                </section>
            </PageShell>
        </>
    );
}
