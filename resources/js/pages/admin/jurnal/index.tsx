import { Head } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import PageShell from '@/components/page-shell';
import SimplePager from '@/components/simple-pager';
import StatusBadge from '@/components/status-badge';
import AppLayout from '@/layouts/app-layout';
import { formatTanggal } from '@/lib/format';
import type { BreadcrumbItem, Journal, ValidationStatus } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Monitor Jurnal', href: '/admin/jurnal' },
];

type PaginatedJurnal = {
    data: Journal[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
};

export default function JurnalMonitor({
    jurnals,
}: {
    jurnals: PaginatedJurnal;
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Monitor Jurnal" />
            <PageShell
                title="Monitor Jurnal"
                subtitle={`${jurnals.total ?? jurnals.data.length} jurnal tercatat`}
            >
                <section className="flex flex-col gap-[19px] px-4 pt-3.5 pb-6 lg:px-8 lg:py-6">
                    {jurnals.data.length === 0 ? (
                        <EmptyState
                            title="Belum ada jurnal"
                            description="Jurnal yang dibuat guru akan tampil di sini."
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
                                        <div className="flex min-w-0 flex-1 flex-col gap-[15px]">
                                            <div className="flex items-center justify-between gap-2">
                                                <span className="text-[13px] font-bold whitespace-nowrap text-[#1A1D26]">
                                                    {formatTanggal(j.tanggal)}
                                                </span>
                                                <StatusBadge status={status} />
                                            </div>
                                            <span className="truncate text-xs font-semibold text-[#1A1D26]">
                                                {j.guru?.name ?? '-'} &bull;{' '}
                                                {j.kelas?.nama_kelas ?? '-'}
                                            </span>
                                            <span className="line-clamp-2 text-xs font-normal text-[#6B7280]">
                                                {j.mataPelajaran?.nama_mapel ??
                                                    '-'}{' '}
                                                &bull;{' '}
                                                {j.materi ?? '(belum diisi)'}
                                            </span>
                                        </div>
                                        <ChevronRight className="h-5 w-5 shrink-0 text-[#6B7280]" />
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
        </AppLayout>
    );
}
