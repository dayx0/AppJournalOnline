import { Head } from '@inertiajs/react';
import { BookOpen, School, Users } from 'lucide-react';
import PageShell from '@/components/page-shell';
import StatCard from '@/components/stat-card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Stats = {
    totalUsers: number;
    totalGuru: number;
    totalJurnal: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/admin/dashboard' },
];

export default function AdminDashboard({ stats }: { stats: Stats }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin Dashboard" />
            <PageShell
                title="Dashboard Admin"
                subtitle="Ringkasan data sistem"
            >
                <section className="grid grid-cols-2 gap-3 px-5 pt-2.5 pb-6 lg:grid-cols-3 lg:gap-4 lg:px-8 lg:pt-5 lg:pb-6">
                    <StatCard
                        label="Total User"
                        value={String(stats.totalUsers)}
                        sub="Terdaftar di sistem"
                        icon={Users}
                        iconWrapperClass="bg-[#EFF4FF]"
                        iconClass="text-[#2563EB]"
                    />
                    <StatCard
                        label="Total Guru"
                        value={String(stats.totalGuru)}
                        sub="Akun guru aktif"
                        icon={School}
                        iconWrapperClass="bg-[#E8F7EE]"
                        iconClass="text-[#16A34A]"
                    />
                    <StatCard
                        label="Total Jurnal"
                        value={String(stats.totalJurnal)}
                        sub="Dibuat para guru"
                        icon={BookOpen}
                        iconWrapperClass="bg-[#FFF4E0]"
                        iconClass="text-[#D97706]"
                    />
                </section>
            </PageShell>
        </AppLayout>
    );
}
