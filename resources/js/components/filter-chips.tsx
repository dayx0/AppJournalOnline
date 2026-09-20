/**
 * Chips filter pil gaya guru (dipakai di daftar jurnal, notifikasi, dsb).
 */
export default function FilterChips<T extends string>({
    items,
    active,
    onChange,
}: {
    items: { key: T; label: string }[];
    active: T;
    onChange: (key: T) => void;
}) {
    return (
        <div className="flex gap-2 overflow-x-auto px-4 py-2 lg:px-8">
            {items.map((item) => {
                const isActive = active === item.key;

                return (
                    <button
                        key={item.key}
                        type="button"
                        onClick={() => onChange(item.key)}
                        className={`shrink-0 rounded-full border px-3.5 py-[7px] text-xs whitespace-nowrap ${
                            isActive
                                ? 'border-[#2563EB] bg-[#2563EB] font-semibold text-white'
                                : 'border-[#E5E9F2] bg-white font-medium text-[#6B7280]'
                        }`}
                    >
                        {item.label}
                    </button>
                );
            })}
        </div>
    );
}
