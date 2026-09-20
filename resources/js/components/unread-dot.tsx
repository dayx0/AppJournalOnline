/**
 * Lencana unread bersama: pil "Baru" di daftar + angka di bell/sidebar.
 */
export function UnreadPill() {
    return (
        <span className="rounded-full bg-[#DC2626] px-2 py-0.5 text-[10px] font-bold whitespace-nowrap text-white">
            Baru
        </span>
    );
}

export function CountBadge({ count }: { count: number }) {
    if (!count || count <= 0) {
        return null;
    }

    return (
        <span className="flex h-5 min-w-5 items-center justify-center rounded-full bg-[#DC2626] px-1.5 text-[11px] font-bold text-white">
            {count > 99 ? '99+' : count}
        </span>
    );
}
