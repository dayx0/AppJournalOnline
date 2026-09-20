import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import type { ReactNode } from 'react';

/**
 * Kerangka halaman gaya guru: container mobile-first + header
 * (tombol kembali di HP, judul + subjudul + aksi di desktop).
 *
 * Lebar konten selalu mengikuti sisa layar (full width di dalam
 * sidebar desktop, max-w-2xl hanya di layar kecil) agar tidak ada
 * teks yang kepotong oleh batas max-width.
 *
 * Prop `framed=false` mematikan panel abu-abu desktop — dipakai
 * halaman yang selalu dirender di dalam AppLayout (mis. role MPK)
 * agar tidak terjadi bingkai ganda: kartu konten AppLayout +
 * panel PageShell.
 */
export default function PageShell({
    title,
    subtitle,
    backHref,
    backLabel = 'Kembali',
    actions,
    framed = true,
    children,
}: {
    title: string;
    subtitle?: string;
    backHref?: string;
    backLabel?: string;
    actions?: ReactNode;
    framed?: boolean;
    children: ReactNode;
}) {
    return (
        <div
            className={`mx-auto w-full max-w-2xl font-[Inter,system-ui,sans-serif] lg:max-w-none ${
                framed ? 'lg:rounded-2xl lg:bg-[#F6F8FC] lg:py-2' : ''
            }`}
        >
            <section className="flex items-center gap-3 px-4 pt-2 pb-1 lg:px-8 lg:pt-6">
                {backHref && (
                    <Link
                        href={backHref}
                        aria-label={backLabel}
                        className="flex h-9 w-9 shrink-0 items-center justify-center lg:hidden"
                    >
                        <ArrowLeft className="h-[22px] w-[22px] text-[#1A1D26]" />
                    </Link>
                )}
                <div className="min-w-0 flex-1">
                    <h1 className="text-[18px] font-bold whitespace-nowrap text-[#1A1D26] lg:text-2xl">
                        {title}
                    </h1>
                    {subtitle && (
                        <p className="mt-0.5 hidden text-sm font-normal text-[#6B7280] lg:block">
                            {subtitle}
                        </p>
                    )}
                </div>
                {actions && (
                    <div className="hidden shrink-0 lg:block">{actions}</div>
                )}
            </section>
            {children}
        </div>
    );
}
