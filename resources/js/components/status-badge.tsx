import type { ValidationStatus } from '@/types';

const STATUS_STYLE: Record<ValidationStatus, string> = {
    divalidasi: 'bg-[#E8F7EE] text-[#16A34A]',
    revisi: 'bg-[#FDECEC] text-[#DC2626]',
    pending: 'bg-[#FFF4E0] text-[#D97706]',
};

const STATUS_LABEL: Record<ValidationStatus, string> = {
    divalidasi: 'Sudah Validasi',
    revisi: 'Perlu Revisi',
    pending: 'Menunggu Validasi',
};

/**
 * Badge status validasi gaya guru — satu-satunya sumber kebenaran
 * untuk label + warna pending/divalidasi/revisi di semua role.
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
