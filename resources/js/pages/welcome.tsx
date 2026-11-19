import { Head, Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { useEffect, useState } from 'react';
import { login } from '@/routes';

export default function Welcome() {
    const [showSplash, setShowSplash] = useState(true);

    useEffect(() => {
        const t = setTimeout(() => setShowSplash(false), 1800);

        return () => clearTimeout(t);
    }, []);

    return (
        <>
            <Head title="Journal Online" />
            <div className="min-h-screen bg-[#EDF3FF]">
                {/* Splash screen — hanya logo asli di tengah, sesuai Figma iPhone 17-1 */}
                <div
                    aria-hidden={!showSplash}
                    data-test="welcome-splash"
                    className={`fixed inset-0 z-50 flex items-center justify-center bg-[#EDF3FF] transition-opacity duration-500 ${
                        showSplash
                            ? 'opacity-100'
                            : 'pointer-events-none opacity-0'
                    }`}
                >
                    <img
                        src="/images/logo-smkn1-dukuhturi.png"
                        alt="Logo SMK N 1 Dukuhturi"
                        className="h-40 w-auto md:h-48"
                        draggable={false}
                    />
                </div>
                <main className="mx-auto flex min-h-screen w-full max-w-md flex-col items-center px-6 pt-14 pb-10 sm:max-w-lg md:max-w-xl md:pt-20 lg:max-w-2xl">
                    {/* Logo + Judul — sesuai Figma iPhone 17-3 atas */}
                    <div className="flex flex-col items-center text-center">
                        <img
                            src="/images/logo-smkn1-dukuhturi.png"
                            alt="Logo SMK N 1 Dukuhturi"
                            className="h-28 w-auto md:h-36"
                            draggable={false}
                        />
                        <h1 className="mt-6 text-2xl font-extrabold tracking-tight text-[#1F2A44] md:text-3xl">
                            Journal Online
                        </h1>
                        <p className="mt-1 text-[11px] font-medium tracking-[0.18em] text-[#6B7280] uppercase md:text-xs">
                            SMK N 1 Dukuhturi
                        </p>
                    </div>

                    {/* Teks Smart Journal — rata kiri sesuai Figma */}
                    <div className="mt-12 w-full md:mt-16">
                        <h2 className="text-xl font-extrabold text-[#1F2A44] md:text-2xl">
                            Smart Journal
                        </h2>

                        <p className="mt-2 max-w-md text-sm leading-relaxed text-[#1F2A44]/80 md:text-base">
                            Kelola jurnal mengajar dan catatan kegiatan
                            pembelajaran harian dengan lebih mudah dalam satu
                            aplikasi.
                        </p>
                    </div>

                    <div className="flex-1" />

                    {/* Tombol bulat biru (>) ke Login */}
                    <div className="flex w-full flex-col items-center gap-4 pt-10">
                        <Link
                            href={login()}
                            aria-label="Masuk ke halaman Login"
                            className="flex h-16 w-16 items-center justify-center rounded-full bg-[#1B5BD7] text-white shadow-lg shadow-[#1B5BD7]/30 transition hover:scale-105 hover:bg-[#174FBF] focus-visible:ring-4 focus-visible:ring-[#1B5BD7]/40 focus-visible:outline-none active:scale-95 md:h-[72px] md:w-[72px]"
                            data-test="welcome-cta-button"
                        >
                            <ChevronRight
                                className="h-8 w-8"
                                strokeWidth={2.5}
                            />
                        </Link>
                    </div>
                </main>
            </div>
        </>
    );
}
