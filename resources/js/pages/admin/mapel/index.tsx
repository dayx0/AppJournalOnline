import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import EmptyState from '@/components/empty-state';
import FormField, { INPUT_CLASS } from '@/components/form-field';
import PageShell from '@/components/page-shell';
import SimplePager from '@/components/simple-pager';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kelola Mapel', href: '/admin/mapel' },
];

type Mapel = {
    id: number;
    nama_mapel: string;
    kode_mapel: string;
};

type PaginatedMapel = {
    data: Mapel[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
};

export default function MapelIndex({ mapels }: { mapels: PaginatedMapel }) {
    const { data, setData, post, reset, errors, processing } = useForm({
        nama_mapel: '',
        kode_mapel: '',
    });
    const [showForm, setShowForm] = useState(false);
    const [editing, setEditing] = useState<Mapel | null>(null);
    const {
        data: editData,
        setData: setEditData,
        put,
        errors: editErrors,
        processing: editProcessing,
    } = useForm({
        nama_mapel: '',
        kode_mapel: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/admin/mapel', {
            onSuccess: () => {
                reset();
                setShowForm(false);
            },
        });
    }

    function hapus(id: number) {
        if (confirm('Yakin hapus mapel ini?')) {
            router.delete(`/admin/mapel/${id}`);
        }
    }

    function mulaiEdit(m: Mapel) {
        setEditing(m);
        setEditData({ nama_mapel: m.nama_mapel, kode_mapel: m.kode_mapel });
    }

    function submitEdit(e: React.FormEvent) {
        e.preventDefault();

        if (!editing) {
            return;
        }

        put(`/admin/mapel/${editing.id}`, {
            onSuccess: () => setEditing(null),
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kelola Mapel" />
            <PageShell
                title="Kelola Mapel"
                subtitle={`${mapels.total ?? mapels.data.length} mapel terdaftar`}
                actions={
                    <button
                        type="button"
                        onClick={() => setShowForm(!showForm)}
                        className="inline-flex shrink-0 items-center gap-2 rounded-full bg-[#2563EB] px-4 py-2.5 text-sm font-semibold text-white"
                    >
                        <Plus className="h-4 w-4 text-white" />
                        {showForm ? 'Tutup' : 'Tambah Mapel'}
                    </button>
                }
            >
                {showForm && (
                    <section className="px-4 pt-3 lg:px-8">
                        <div className="flex flex-col gap-2.5 rounded-2xl border border-[#E5E9F2] bg-white p-3.5">
                            <h2 className="text-[15px] font-bold text-[#1A1D26]">
                                Tambah Mapel Baru
                            </h2>
                            <form
                                onSubmit={submit}
                                className="grid grid-cols-1 gap-3 lg:grid-cols-2"
                            >
                                <FormField
                                    label="Nama Mapel *"
                                    htmlFor="nama_mapel"
                                    error={errors.nama_mapel}
                                >
                                    <input
                                        id="nama_mapel"
                                        value={data.nama_mapel}
                                        onChange={(e) =>
                                            setData(
                                                'nama_mapel',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Contoh: Matematika"
                                        className={INPUT_CLASS}
                                    />
                                </FormField>
                                <FormField
                                    label="Kode Mapel *"
                                    htmlFor="kode_mapel"
                                    error={errors.kode_mapel}
                                >
                                    <input
                                        id="kode_mapel"
                                        value={data.kode_mapel}
                                        onChange={(e) =>
                                            setData(
                                                'kode_mapel',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Contoh: MTK"
                                        className={INPUT_CLASS}
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

                {editing && (
                    <section className="px-4 pt-3 lg:px-8">
                        <div className="flex flex-col gap-2.5 rounded-2xl border border-[#E5E9F2] bg-white p-3.5">
                            <h2 className="text-[15px] font-bold text-[#1A1D26]">
                                Edit Mapel: {editing.nama_mapel}
                            </h2>
                            <form
                                onSubmit={submitEdit}
                                className="grid grid-cols-1 gap-3 lg:grid-cols-2"
                            >
                                <FormField
                                    label="Nama Mapel *"
                                    htmlFor="edit-nama"
                                    error={editErrors.nama_mapel}
                                >
                                    <input
                                        id="edit-nama"
                                        value={editData.nama_mapel}
                                        onChange={(e) =>
                                            setEditData(
                                                'nama_mapel',
                                                e.target.value,
                                            )
                                        }
                                        className={INPUT_CLASS}
                                    />
                                </FormField>
                                <FormField
                                    label="Kode Mapel *"
                                    htmlFor="edit-kode"
                                    error={editErrors.kode_mapel}
                                >
                                    <input
                                        id="edit-kode"
                                        value={editData.kode_mapel}
                                        onChange={(e) =>
                                            setEditData(
                                                'kode_mapel',
                                                e.target.value,
                                            )
                                        }
                                        className={INPUT_CLASS}
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
                                        onClick={() => setEditing(null)}
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
                    {mapels.data.length === 0 ? (
                        <EmptyState
                            title="Belum ada mapel"
                            description="Tambahkan mata pelajaran untuk dipakai di jurnal."
                        />
                    ) : (
                        <div className="grid grid-cols-1 gap-[19px] md:grid-cols-2">
                            {mapels.data.map((m) => (
                                <article
                                    key={m.id}
                                    className="flex items-center gap-2.5 rounded-xl bg-white p-[14px] shadow-[0px_2px_10px_0px_#0F172A0D]"
                                >
                                    <span className="flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-full bg-[#E8F7EE] text-[15px] font-bold text-[#16A34A]">
                                        {m.nama_mapel.charAt(0).toUpperCase()}
                                    </span>
                                    <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                                        <span className="truncate text-[13px] font-semibold text-[#1A1D26]">
                                            {m.nama_mapel}
                                        </span>
                                        <span className="truncate text-[11px] font-normal text-[#6B7280]">
                                            Kode: {m.kode_mapel}
                                        </span>
                                    </div>
                                    <span className="flex shrink-0 items-center gap-1">
                                        <button
                                            type="button"
                                            onClick={() => mulaiEdit(m)}
                                            aria-label={`Edit ${m.nama_mapel}`}
                                            className="flex h-8 w-8 items-center justify-center rounded-lg text-[#2563EB] transition hover:bg-[#EFF4FF]"
                                        >
                                            <Pencil className="h-4 w-4" />
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => hapus(m.id)}
                                            aria-label={`Hapus ${m.nama_mapel}`}
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
                    <SimplePager links={mapels.links} />
                </div>
            </PageShell>

            <button
                type="button"
                onClick={() => setShowForm(!showForm)}
                aria-label={showForm ? 'Tutup form' : 'Tambah mapel'}
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
