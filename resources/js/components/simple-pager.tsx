import { router } from '@inertiajs/react';

export type PageLink = {
    url: string | null;
    label: string;
    active: boolean;
};

/**
 * Pagination gaya guru: hanya Sebelumnya/Berikutnya + tautan
 * "Lihat Semua". Nomor halaman ala admin/MPK sengaja tidak dipakai.
 */
export default function SimplePager({
    links,
    seeAllHref,
    seeAllLabel = 'Lihat Semua',
}: {
    links: PageLink[];
    seeAllHref?: string;
    seeAllLabel?: string;
}) {
    const prev = links.find((l) => l.label.includes('Previous'));
    const next = links.find((l) => l.label.includes('Next'));

    if (!prev && !next && !seeAllHref) {
        return null;
    }

    const btn =
        'shrink-0 rounded-full border px-4 py-2 text-xs font-semibold whitespace-nowrap transition disabled:opacity-50 ';

    return (
        <div className="flex items-center justify-between gap-2 px-4 lg:px-8">
            <button
                type="button"
                disabled={!prev?.url}
                onClick={() => prev?.url && router.visit(prev.url)}
                className={`${btn} ${
                    prev?.url
                        ? 'border-[#E5E9F2] bg-white text-[#2563EB]'
                        : 'border-[#E5E9F2] bg-white text-[#9CA3AF]'
                }`}
            >
                &larr; Sebelumnya
            </button>
            {seeAllHref ? (
                <a
                    href={seeAllHref}
                    className="text-xs font-semibold whitespace-nowrap text-[#2563EB]"
                >
                    {seeAllLabel} &gt;
                </a>
            ) : (
                <span />
            )}
            <button
                type="button"
                disabled={!next?.url}
                onClick={() => next?.url && router.visit(next.url)}
                className={`${btn} ${
                    next?.url
                        ? 'border-[#E5E9F2] bg-white text-[#2563EB]'
                        : 'border-[#E5E9F2] bg-white text-[#9CA3AF]'
                }`}
            >
                Berikutnya &rarr;
            </button>
        </div>
    );
}
