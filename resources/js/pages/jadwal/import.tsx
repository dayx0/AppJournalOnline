import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CircleAlert, CircleCheck } from 'lucide-react';
import { useState } from 'react';
import EmptyState from '@/components/empty-state';
import PageShell from '@/components/page-shell';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Jadwal', href: '/jadwal' },
    { title: 'Pratinjau Import', href: '/jadwal/import' },
];

type DraftRow = {
    no: number;
    data: {
        kelas_id: number;
        hari: string;
        jam_mulai: string;
        jam_selesai: string;
        mapel_id: number;
        guru_id: number;
    } | null;
    errors: string[];
};

export default function ImportPreview({
    rows,
    semester,
    validCount,
}: {
    rows: DraftRow[];
    semester: string;
    validCount: number;
}) {
    const [processing, setProcessing] = useState(false);
    const invalidCount = rows.length - validCount;

    function konfirmasi() {
        if (
            validCount === 0 ||
            !confirm(
                `Import ${validCount} slot valid semester ${semester}? Baris error dilewati.`,
            )
        ) {
            return;
        }

        setProcessing(true);
        router.post(
            '/jadwal/import/confirm',
            {},
            {
                onFinish: () => setProcessing(false),
            },
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pratinjau Import Jadwal" />
            <PageShell
                framed={false}
                title="Pratinjau Import"
                subtitle={`${validCount} valid • ${invalidCount} error • semester ${semester}`}
            >
                <section className="px-4 pt-3 lg:px-8">
                    <Link
                        href="/jadwal"
                        className="inline-flex items-center gap-1 text-xs font-semibold text-[#2563EB]"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Kembali (belum ada yang disimpan)
                    </Link>
                </section>

                <section className="px-4 pt-3 pb-6 lg:px-8">
                    {rows.length === 0 ? (
                        <EmptyState
                            title="Tidak ada baris terbaca"
                            description="File kosong atau header tidak dikenali. Unduh template dulu."
                        />
                    ) : (
                        <div className="flex flex-col gap-2">
                            {rows.map((row) => {
                                const ok = row.errors.length === 0;

                                return (
                                    <div
                                        key={row.no}
                                        className={`flex items-start gap-2.5 rounded-xl border bg-white p-3 ${
                                            ok
                                                ? 'border-[#E5E9F2]'
                                                : 'border-[#FECACA]'
                                        }`}
                                    >
                                        <span
                                            className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full ${
                                                ok
                                                    ? 'bg-[#E8F7EE]'
                                                    : 'bg-[#FDECEC]'
                                            }`}
                                        >
                                            {ok ? (
                                                <CircleCheck className="h-4 w-4 text-[#16A34A]" />
                                            ) : (
                                                <CircleAlert className="h-4 w-4 text-[#DC2626]" />
                                            )}
                                        </span>
                                        <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                                            <span className="text-xs font-bold text-[#1A1D26]">
                                                Baris {row.no}
                                            </span>
                                            {ok && row.data ? (
                                                <span className="truncate text-xs font-normal text-[#6B7280]">
                                                    {row.data.hari} •{' '}
                                                    {row.data.jam_mulai}–
                                                    {row.data.jam_selesai}
                                                </span>
                                            ) : (
                                                row.errors.map((e) => (
                                                    <span
                                                        key={e}
                                                        className="text-xs font-normal text-[#DC2626]"
                                                    >
                                                        • {e}
                                                    </span>
                                                ))
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </section>

                {validCount > 0 && (
                    <section className="px-4 pb-6 lg:px-8">
                        <button
                            type="button"
                            onClick={konfirmasi}
                            disabled={processing}
                            className="flex h-12 w-full items-center justify-center rounded-[10px] bg-[#2563EB] text-sm font-bold text-white transition enabled:hover:bg-[#1D4ED8] disabled:opacity-60"
                        >
                            {processing
                                ? 'Mengimpor...'
                                : `Import ${validCount} Slot Valid`}
                        </button>
                        <p className="pt-2 text-center text-[11px] font-normal text-[#9CA3AF]">
                            Slot yang sudah ada dilewati otomatis (tidak dobel).
                        </p>
                    </section>
                )}
            </PageShell>
        </AppLayout>
    );
}
