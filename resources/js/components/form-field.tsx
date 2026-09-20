import type { ReactNode } from 'react';
import InputError from '@/components/input-error';

export const INPUT_CLASS =
    'h-11 w-full rounded-[10px] border border-[#E5E9F2] bg-white px-3 text-[13px] font-normal text-[#1A1D26] outline-none placeholder:text-[#9CA3AF] focus:border-[#2563EB]';

/**
 * Field form gaya guru (label + input + error).
 */
export default function FormField({
    label,
    htmlFor,
    error,
    children,
}: {
    label: string;
    htmlFor: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div className="flex flex-col gap-1.5">
            <label
                htmlFor={htmlFor}
                className="text-xs font-semibold whitespace-nowrap text-[#1A1D26]"
            >
                {label}
            </label>
            {children}
            <InputError message={error} className="text-xs" />
        </div>
    );
}
