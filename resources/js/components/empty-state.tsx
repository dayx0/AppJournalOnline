import type { ReactNode } from 'react';

/**
 * Kartu kosong gaya guru (judul + deskripsi + aksi opsional).
 */
export default function EmptyState({
    title,
    description,
    action,
}: {
    title: string;
    description?: string;
    action?: ReactNode;
}) {
    return (
        <div className="flex flex-col items-center gap-2 rounded-xl bg-white p-6 text-center shadow-[0px_2px_10px_0px_#0F172A0D]">
            <p className="text-[13px] font-semibold text-[#1A1D26]">{title}</p>
            {description && (
                <p className="text-xs font-normal text-[#6B7280]">
                    {description}
                </p>
            )}
            {action && <div className="mt-1">{action}</div>}
        </div>
    );
}
