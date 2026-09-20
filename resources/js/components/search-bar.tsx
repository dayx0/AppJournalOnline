import { Search } from 'lucide-react';

/**
 * Kolom pencarian gaya guru.
 */
export default function SearchBar({
    value,
    onChange,
    placeholder = 'Cari...',
    label = 'Cari',
}: {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    label?: string;
}) {
    return (
        <div className="px-4 py-1 lg:px-8 lg:pt-4">
            <label className="flex items-center gap-2.5 rounded-xl border border-[#E5E9F2] bg-white px-3.5 py-3">
                <Search className="h-[18px] w-[18px] shrink-0 text-[#6B7280]" />
                <span className="sr-only">{label}</span>
                <input
                    type="search"
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    placeholder={placeholder}
                    className="w-full bg-transparent text-[13px] font-normal text-[#1A1D26] outline-none placeholder:text-[#6B7280]"
                />
            </label>
        </div>
    );
}
