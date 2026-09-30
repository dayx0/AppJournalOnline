import { formatTanggal } from '@/lib/format';
import type { JournalHistory } from '@/types';

const AKSI_META: Record<string, { label: string; badgeClass: string }> = {
    dibuat: {
        label: 'Dibuat',
        badgeClass: 'bg-[#EFF4FF] text-[#2563EB]',
    },
    divalidasi: {
        label: 'Divalidasi',
        badgeClass: 'bg-[#E8F7EE] text-[#16A34A]',
    },
    revisi: {
        label: 'Revisi (data lama)',
        badgeClass: 'bg-[#FDECEC] text-[#DC2626]',
    },
    diisi: {
        label: 'Diisi guru',
        badgeClass: 'bg-[#FFF4E0] text-[#D97706]',
    },
    ditolak: {
        label: 'Ditolak (tidak hadir)',
        badgeClass: 'bg-[#FDECEC] text-[#DC2626]',
    },
    jam_kosong: {
        label: 'Jam kosong',
        badgeClass: 'bg-[#FDECEC] text-[#991B1B]',
    },
    terlambat: {
        label: 'Hadir (terlambat)',
        badgeClass: 'bg-[#FFF4E0] text-[#B45309]',
    },
    izin: {
        label: 'Izin / tugas dinas',
        badgeClass: 'bg-[#EFF4FF] text-[#2563EB]',
    },
    reset_pending: {
        label: 'Diubah • perlu validasi ulang',
        badgeClass: 'bg-[#FFF4E0] text-[#D97706]',
    },
};

function formatWaktu(iso: string): string {
    const date = new Date(iso);

    if (Number.isNaN(date.getTime())) {
        return iso;
    }

    return `${formatTanggal(iso.slice(0, 10))} • ${date.toLocaleTimeString(
        'id-ID',
        { hour: '2-digit', minute: '2-digit' },
    )}`;
}

/**
 * Timeline jejak audit validasi satu jurnal.
 * Dipakai di detail guru maupun halaman periksa MPK.
 */
export default function JournalHistoryTimeline({
    histories = [],
}: {
    histories?: JournalHistory[];
}) {
    if (histories.length === 0) {
        return null;
    }

    return (
        <section className="flex flex-col gap-2.5 rounded-2xl border border-[#E5E9F2] bg-white p-3.5">
            <h3 className="text-sm font-bold whitespace-nowrap text-[#1A1D26] lg:text-base">
                Riwayat Validasi
            </h3>
            <ol className="flex flex-col gap-2">
                {histories.map((h) => {
                    const meta = AKSI_META[h.aksi] ?? {
                        label: h.aksi,
                        badgeClass: 'bg-[#F3F4F6] text-[#6B7280]',
                    };

                    return (
                        <li
                            key={h.id}
                            className="flex items-start gap-2.5 rounded-xl bg-[#F6F8FC] p-2.5"
                        >
                            <span
                                className={`shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap ${meta.badgeClass}`}
                            >
                                {meta.label}
                            </span>
                            <span className="flex min-w-0 flex-1 flex-col gap-0.5">
                                <span className="text-[13px] font-semibold text-[#1A1D26]">
                                    {h.actor?.name ?? 'Sistem'}
                                    {h.dari_status && h.ke_status
                                        ? ` • ${h.dari_status} → ${h.ke_status}`
                                        : ''}
                                </span>
                                {h.catatan && (
                                    <span className="text-xs font-normal whitespace-pre-line text-[#6B7280]">
                                        {h.catatan}
                                    </span>
                                )}
                                <span className="text-[11px] font-normal whitespace-nowrap text-[#9CA3AF]">
                                    {formatWaktu(h.created_at)}
                                </span>
                            </span>
                        </li>
                    );
                })}
            </ol>
        </section>
    );
}
