import { Link } from '@inertiajs/react';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-[#EDF3FF] p-6 md:p-10">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href={home()}
                            className="flex flex-col items-center gap-2 font-medium"
                        >
                            <img
                                src="/images/logo-smkn1-dukuhturi.png"
                                alt="Logo SMK N 1 Dukuhturi"
                                className="h-16 w-auto"
                                draggable={false}
                            />
                            <span className="sr-only">{title}</span>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-xl font-bold text-[#1F2A44]">
                                {title}
                            </h1>
                            <p className="text-center text-sm text-[#1F2A44]/70">
                                {description}
                            </p>
                        </div>
                    </div>
                    <div className="rounded-2xl bg-white p-6 shadow-sm">
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
