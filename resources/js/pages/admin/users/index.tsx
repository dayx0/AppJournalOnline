import { Head, router, useForm } from '@inertiajs/react';
import { ChevronDown, Pencil, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import EmptyState from '@/components/empty-state';
import FormField, { INPUT_CLASS } from '@/components/form-field';
import PageShell from '@/components/page-shell';
import SimplePager from '@/components/simple-pager';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kelola User', href: '/admin/users' },
];

type Role = 'guru' | 'admin' | 'mpk';

type User = {
    id: number;
    name: string;
    email: string;
    role: string;
};

type PaginatedUsers = {
    data: User[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
};

const ROLE_STYLE: Record<string, string> = {
    guru: 'bg-[#EFF4FF] text-[#2563EB]',
    admin: 'bg-[#F6F8FC] text-[#1A1D26]',
    mpk: 'bg-[#FFF4E0] text-[#D97706]',
};

const ROLE_OPTIONS: { value: Role; label: string }[] = [
    { value: 'guru', label: 'Guru' },
    { value: 'mpk', label: 'MPK' },
    { value: 'admin', label: 'Admin' },
];

function RoleSelect({
    id,
    value,
    onChange,
}: {
    id: string;
    value: Role;
    onChange: (value: Role) => void;
}) {
    return (
        <div className="relative">
            <select
                id={id}
                value={value}
                onChange={(e) => onChange(e.target.value as Role)}
                className={`${INPUT_CLASS} appearance-none pr-9`}
            >
                {ROLE_OPTIONS.map((o) => (
                    <option key={o.value} value={o.value}>
                        {o.label}
                    </option>
                ))}
            </select>
            <ChevronDown className="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-[#9CA3AF]" />
        </div>
    );
}

export default function UsersIndex({ users }: { users: PaginatedUsers }) {
    const { data, setData, post, reset, errors, processing } = useForm({
        name: '',
        email: '',
        password: '',
        role: 'guru' as Role,
    });
    const [showForm, setShowForm] = useState(false);

    const [editingUser, setEditingUser] = useState<User | null>(null);
    const {
        data: editData,
        setData: setEditData,
        put,
        errors: editErrors,
        processing: editProcessing,
    } = useForm({
        name: '',
        email: '',
        role: 'guru' as Role,
        password: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/admin/users', {
            onSuccess: () => {
                reset();
                setShowForm(false);
            },
        });
    }

    function hapus(id: number) {
        if (confirm('Yakin hapus user ini?')) {
            router.delete(`/admin/users/${id}`);
        }
    }

    function mulaiEdit(u: User) {
        setEditingUser(u);
        setEditData({
            name: u.name,
            email: u.email,
            role: (u.role as Role) ?? 'guru',
            password: '',
        });
    }

    function submitEdit(e: React.FormEvent) {
        e.preventDefault();

        if (!editingUser) {
            return;
        }

        put(`/admin/users/${editingUser.id}`, {
            onSuccess: () => setEditingUser(null),
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kelola User" />
            <PageShell
                title="Kelola User"
                subtitle={`${users.total ?? users.data.length} user terdaftar`}
                actions={
                    <button
                        type="button"
                        onClick={() => setShowForm(!showForm)}
                        className="inline-flex shrink-0 items-center gap-2 rounded-full bg-[#2563EB] px-4 py-2.5 text-sm font-semibold text-white"
                    >
                        <Plus className="h-4 w-4 text-white" />
                        {showForm ? 'Tutup' : 'Tambah User'}
                    </button>
                }
            >
                {showForm && (
                    <section className="px-4 pt-3 lg:px-8">
                        <div className="flex flex-col gap-2.5 rounded-2xl border border-[#E5E9F2] bg-white p-3.5">
                            <h2 className="text-[15px] font-bold text-[#1A1D26]">
                                Tambah User Baru
                            </h2>
                            <form
                                onSubmit={submit}
                                className="grid grid-cols-1 gap-3 lg:grid-cols-2"
                            >
                                <FormField
                                    label="Nama *"
                                    htmlFor="name"
                                    error={errors.name}
                                >
                                    <input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) =>
                                            setData('name', e.target.value)
                                        }
                                        placeholder="Nama lengkap"
                                        className={INPUT_CLASS}
                                    />
                                </FormField>
                                <FormField
                                    label="Email *"
                                    htmlFor="email"
                                    error={errors.email}
                                >
                                    <input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) =>
                                            setData('email', e.target.value)
                                        }
                                        placeholder="nama@sekolah.id"
                                        className={INPUT_CLASS}
                                    />
                                </FormField>
                                <FormField
                                    label="Password *"
                                    htmlFor="password"
                                    error={errors.password}
                                >
                                    <input
                                        id="password"
                                        type="password"
                                        value={data.password}
                                        onChange={(e) =>
                                            setData('password', e.target.value)
                                        }
                                        placeholder="Minimal 8 karakter"
                                        className={INPUT_CLASS}
                                    />
                                </FormField>
                                <FormField
                                    label="Role *"
                                    htmlFor="role"
                                    error={errors.role}
                                >
                                    <RoleSelect
                                        id="role"
                                        value={data.role}
                                        onChange={(v) => setData('role', v)}
                                    />
                                </FormField>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="flex h-12 w-full items-center justify-center rounded-[10px] bg-[#2563EB] text-sm font-bold whitespace-nowrap text-white transition enabled:hover:bg-[#1D4ED8] disabled:opacity-60 lg:col-span-2"
                                >
                                    {processing ? 'Menyimpan...' : 'Simpan'}
                                </button>
                            </form>
                        </div>
                    </section>
                )}

                {editingUser && (
                    <section className="px-4 pt-3 lg:px-8">
                        <div className="flex flex-col gap-2.5 rounded-2xl border border-[#E5E9F2] bg-white p-3.5">
                            <h2 className="text-[15px] font-bold text-[#1A1D26]">
                                Edit User: {editingUser.name}
                            </h2>
                            <form
                                onSubmit={submitEdit}
                                className="grid grid-cols-1 gap-3 lg:grid-cols-2"
                            >
                                <FormField
                                    label="Nama *"
                                    htmlFor="edit-name"
                                    error={editErrors.name}
                                >
                                    <input
                                        id="edit-name"
                                        value={editData.name}
                                        onChange={(e) =>
                                            setEditData('name', e.target.value)
                                        }
                                        className={INPUT_CLASS}
                                    />
                                </FormField>
                                <FormField
                                    label="Email *"
                                    htmlFor="edit-email"
                                    error={editErrors.email}
                                >
                                    <input
                                        id="edit-email"
                                        type="email"
                                        value={editData.email}
                                        onChange={(e) =>
                                            setEditData('email', e.target.value)
                                        }
                                        className={INPUT_CLASS}
                                    />
                                </FormField>
                                <FormField
                                    label="Password (kosongkan jika tidak diganti)"
                                    htmlFor="edit-password"
                                    error={editErrors.password}
                                >
                                    <input
                                        id="edit-password"
                                        type="password"
                                        value={editData.password}
                                        onChange={(e) =>
                                            setEditData(
                                                'password',
                                                e.target.value,
                                            )
                                        }
                                        className={INPUT_CLASS}
                                    />
                                </FormField>
                                <FormField
                                    label="Role *"
                                    htmlFor="edit-role"
                                    error={editErrors.role}
                                >
                                    <RoleSelect
                                        id="edit-role"
                                        value={editData.role}
                                        onChange={(v) => setEditData('role', v)}
                                    />
                                </FormField>
                                <div className="flex gap-2 lg:col-span-2">
                                    <button
                                        type="submit"
                                        disabled={editProcessing}
                                        className="flex h-12 flex-1 items-center justify-center rounded-[10px] bg-[#2563EB] text-sm font-bold whitespace-nowrap text-white transition enabled:hover:bg-[#1D4ED8] disabled:opacity-60"
                                    >
                                        {editProcessing
                                            ? 'Menyimpan...'
                                            : 'Update'}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setEditingUser(null)}
                                        className="flex h-12 items-center justify-center rounded-[10px] border border-[#E5E9F2] bg-white px-4 text-sm font-semibold whitespace-nowrap text-[#6B7280]"
                                    >
                                        Batal
                                    </button>
                                </div>
                            </form>
                        </div>
                    </section>
                )}

                <section className="flex flex-col gap-[19px] px-4 pt-3.5 pb-6 lg:px-8 lg:py-6">
                    {users.data.length === 0 ? (
                        <EmptyState
                            title="Belum ada user"
                            description="Tambahkan akun guru, MPK, atau admin."
                        />
                    ) : (
                        <div className="grid grid-cols-1 gap-[19px] md:grid-cols-2">
                            {users.data.map((u) => (
                                <article
                                    key={u.id}
                                    className="flex items-center gap-2.5 rounded-xl bg-white p-[14px] shadow-[0px_2px_10px_0px_#0F172A0D]"
                                >
                                    <span className="flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-full bg-[#EFF4FF] text-[15px] font-bold text-[#2563EB]">
                                        {u.name.charAt(0).toUpperCase()}
                                    </span>
                                    <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                                        <span className="truncate text-[13px] font-semibold text-[#1A1D26]">
                                            {u.name}
                                        </span>
                                        <span className="truncate text-[11px] font-normal text-[#6B7280]">
                                            {u.email}
                                        </span>
                                    </div>
                                    <span
                                        className={`shrink-0 rounded-lg px-2 py-1 text-[10px] font-semibold whitespace-nowrap capitalize ${ROLE_STYLE[u.role] ?? 'bg-[#F3F4F6] text-[#6B7280]'}`}
                                    >
                                        {u.role}
                                    </span>
                                    <span className="flex shrink-0 items-center gap-1">
                                        <button
                                            type="button"
                                            onClick={() => mulaiEdit(u)}
                                            aria-label={`Edit ${u.name}`}
                                            className="flex h-8 w-8 items-center justify-center rounded-lg text-[#2563EB] transition hover:bg-[#EFF4FF]"
                                        >
                                            <Pencil className="h-4 w-4" />
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => hapus(u.id)}
                                            aria-label={`Hapus ${u.name}`}
                                            className="flex h-8 w-8 items-center justify-center rounded-lg text-[#DC2626] transition hover:bg-[#FDECEC]"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </button>
                                    </span>
                                </article>
                            ))}
                        </div>
                    )}
                </section>

                <div className="pb-6">
                    <SimplePager links={users.links} />
                </div>
            </PageShell>

            <button
                type="button"
                onClick={() => setShowForm(!showForm)}
                aria-label={showForm ? 'Tutup form' : 'Tambah user'}
                className="fixed right-5 bottom-24 z-10 flex h-14 w-14 items-center justify-center rounded-full bg-[#2563EB] shadow-[0_12px_24px_-6px_rgba(37,99,235,0.6)] lg:hidden"
            >
                {showForm ? (
                    <X className="h-[26px] w-[26px] text-white" />
                ) : (
                    <Plus className="h-[26px] w-[26px] text-white" />
                )}
            </button>
        </AppLayout>
    );
}
