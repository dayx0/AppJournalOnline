/**
 * Baris detail label + nilai gaya guru.
 */
export default function DetailRow({
    label,
    value,
}: {
    label: string;
    value: string;
}) {
    return (
        <div className="rounded-xl border border-[#E5E9F2] bg-white p-3.5">
            <p className="text-[11px] font-semibold text-[#6B7280]">{label}</p>
            <p className="mt-1 text-[13px] font-normal whitespace-pre-line text-[#1A1D26]">
                {value}
            </p>
        </div>
    );
}
