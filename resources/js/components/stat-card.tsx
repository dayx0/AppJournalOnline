import type { LucideIcon } from 'lucide-react';

/**
 * Kartu statistik gaya guru (ikon + label + angka + sub).
 */
export default function StatCard({
    label,
    value,
    sub,
    icon: Icon,
    iconWrapperClass,
    iconClass,
}: {
    label: string;
    value: string;
    sub: string;
    icon: LucideIcon;
    iconWrapperClass: string;
    iconClass: string;
}) {
    return (
        <div className="flex items-center gap-2.5 rounded-[14px] bg-white p-3 shadow-[0px_2px_12px_0px_#0F172A0D] lg:gap-3 lg:p-4">
            <div
                className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] lg:h-11 lg:w-11 ${iconWrapperClass}`}
            >
                <Icon
                    className={`h-[18px] w-[18px] lg:h-5 lg:w-5 ${iconClass}`}
                />
            </div>
            <div className="flex min-w-0 flex-1 flex-col gap-[1px]">
                <span className="text-[11px] font-normal text-[#6B7280] lg:text-xs">
                    {label}
                </span>
                <span className="text-[20px] leading-6 font-extrabold text-[#1A1D26] lg:text-2xl lg:leading-7">
                    {value}
                </span>
                <span className="truncate text-[10px] font-normal text-[#9CA3AF] lg:text-[11px]">
                    {sub}
                </span>
            </div>
        </div>
    );
}
