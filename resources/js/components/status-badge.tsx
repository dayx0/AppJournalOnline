import type { ValidationStatus } from '@/types';

const STATUS_STYLE: Record<ValidationStatus, string> = {
    menunggu: 'bg-[#F3F4F6] text-[#6B7280]',
    pending: 'bg-[#FFF4E0] text-[#D97706]',
    divalidasi: 'bg-[#E8F7EE] text-[#16A34A]',
    ditolak: 'bg-[#FDECEC] text-[#DC2626]',
    jam_kosong: 'bg-[#FDECEC] text-[#991B1B]',
    izin: 'bg-[#EFF4FF] text-[#2563EB]',
    terlambat: 'bg-[#FFF4E0] text-[#B45309]',
};

const STATUS_LABEL: Record<ValidationStatus, string> = {
    menunggu: 'Menunggu Diisi',
    pending: 'Menunggu Validasi',
    divalidasi: 'Sudah Validasi',
    ditolak: 'Ditolak (Tidak Hadir)',
    jam_kosong: 'Jam Kosong',
    izin: 'Izin / Tugas Dinas',
    terlambat: 'Hadir (Terlambat)',
};

/**
 * Badge status validasi gaya guru — satu-satunya sumber kebenaran
 * untuk label + warna semua status jurnal di semua role.
 */
export default function StatusBadge({ status }: { status: ValidationStatus }) {
    const key: ValidationStatus = status ?? 'pending';

    return (
        <span
            className={`shrink-0 rounded-lg px-2 py-1 text-[10px] font-semibold whitespace-nowrap ${STATUS_STYLE[key]}`}
        >
            {STATUS_LABEL[key]}
        </span>
    );
}
