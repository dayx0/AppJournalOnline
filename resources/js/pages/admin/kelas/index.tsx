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
    { title: 'Kelola Kelas', href: '/admin/kelas' },
];

type GuruOption = { id: number; name: string };

type Kelas = {
    id: number;
    nama_kelas: string;
    jurusan: string;
    tingkat: number;
    wali_kelas_id: number | null;
    wali_kelas?: { id: number; name: string } | null;
};

type PaginatedKelas = {
    data: Kelas[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
};

export default function KelasIndex({
    kelas,
    guru,
}: {
    kelas: PaginatedKelas;
    guru: GuruOption[];
}) {
    const { data, setData, post, reset, errors, processing } = useForm({
        nama_kelas: '',
        jurusan: '',
        tingkat: 10 as number,
        wali_kelas_id: '' as string,
    });
    const [showForm, setShowForm] = useState(false);
    const [editing, setEditing] = useState<Kelas | null>(null);
    const {
        data: editData,
        setData: setEditData,
        put,
        errors: editErrors,
        processing: editProcessing,
    } = useForm({
        nama_kelas: '',
        jurusan: '',
        tingkat: 10 as number,
        wali_kelas_id: '' as string,
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/admin/kelas', {
            onSuccess: () => {
                reset();
                setShowForm(false);
            },
        });
    }

    function hapus(id: number) {
        if (confirm('Yakin hapus kelas ini?')) {
            router.delete(`/admin/kelas/${id}`);
        }
    }

    function mulaiEdit(k: Kelas) {
        setEditing(k);
        setEditData({
            nama_kelas: k.nama_kelas,
            jurusan: k.jurusan,
            tingkat: k.tingkat,
            wali_kelas_id: k.wali_kelas_id ? String(k.wali_kelas_id) : '',
        });
    }

    function submitEdit(e: React.FormEvent) {
        e.preventDefault();

        if (!editing) {
            return;
        }

        put(`/admin/kelas/${editing.id}`, {
            onSuccess: () => setEditing(null),
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kelola Kelas" />
            <PageShell
                title="Kelola Kelas"
                subtitle={`${kelas.total ?? kelas.data.length} kelas terdaftar`}
                actions={
                    <button
                        type="button"
                        onClick={() => setShowForm(!showForm)}
                        className="inline-flex shrink-0 items-center gap-2 rounded-full bg-[#2563EB] px-4 py-2.5 text-sm font-semibold text-white"
                    >
                        <Plus className="h-4 w-4 text-white" />
                        {showForm ? 'Tutup' : 'Tambah Kelas'}
                    </button>
                }
            >
                {showForm && (
                    <section className="px-4 pt-3 lg:px-8">
                        <div className="flex flex-col gap-2.5 rounded-2xl border border-[#E5E9F2] bg-white p-3.5">
                            <h2 className="text-[15px] font-bold text-[#1A1D26]">
                                Tambah Kelas Baru
                            </h2>
                            <form
                                onSubmit={submit}
                                className="grid grid-cols-1 gap-3 lg:grid-cols-2"
                            >
                                <FormField
                                    label="Nama Kelas *"
                                    htmlFor="nama_kelas"
                                    error={errors.nama_kelas}
                                >
                                    <input
                                        id="nama_kelas"
                                        value={data.nama_kelas}
                                        onChange={(e) =>
                                            setData(
                                                'nama_kelas',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Contoh: XII IPA 1"
                                        className={INPUT_CLASS}
                                    />
                                </FormField>
                                <FormField
                                    label="Jurusan *"
                                    htmlFor="jurusan"
                                    error={errors.jurusan}
                                >
                                    <input
                                        id="jurusan"
                                        value={data.jurusan}
                                        onChange={(e) =>
                                            setData('jurusan', e.target.value)
                                        }
                                        placeholder="Contoh: IPA"
                                        className={INPUT_CLASS}
                                    />
                                </FormField>
                                <FormField
                                    label="Tingkat *"
                                    htmlFor="tingkat"
                                    error={errors.tingkat}
                                >
                                    <input
                                        id="tingkat"
                                        type="number"
                                        value={data.tingkat}
                                        onChange={(e) =>
                                            setData(
                                                'tingkat',
                                                Number(e.target.value),
                                            )
                                        }
                                        className={INPUT_CLASS}
                                    />
                                </FormField>
                                <FormField
                                    label="Wali kelas"
                                    htmlFor="wali_kelas_id"
                                    error={errors.wali_kelas_id}
                                >
                                    <div className="relative">
                                        <select
                                            id="wali_kelas_id"
                                            value={data.wali_kelas_id}
                                            onChange={(e) =>
                                                setData(
                                                    'wali_kelas_id',
                                                    e.target.value,
                                                )
                                            }
                                            className={`${INPUT_CLASS} appearance-none pr-9`}
                                        >
                                            <option value="">
                                                -- Belum ada wali --
                                            </option>
                                            {guru.map((g) => (
                                                <option
                                                    key={g.id}
                                                    value={String(g.id)}
                                                >
                                                    {g.name}
                                                </option>
                                            ))}
                                        </select>
                                        <ChevronDown className="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-[#9CA3AF]" />
                                    </div>
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
                                Edit Kelas: {editing.nama_kelas}
                            </h2>
                            <form
                                onSubmit={submitEdit}
                                className="grid grid-cols-1 gap-3 lg:grid-cols-2"
                            >
                                <FormField
                                    label="Nama Kelas *"
                                    htmlFor="edit-nama"
                                    error={editErrors.nama_kelas}
                                >
                                    <input
                                        id="edit-nama"
                                        value={editData.nama_kelas}
                                        onChange={(e) =>
                                            setEditData(
                                                'nama_kelas',
                                                e.target.value,
                                            )
                                        }
                                        className={INPUT_CLASS}
                                    />
                                </FormField>
                                <FormField
                                    label="Jurusan *"
                                    htmlFor="edit-jurusan"
                                    error={editErrors.jurusan}
                                >
                                    <input
                                        id="edit-jurusan"
                                        value={editData.jurusan}
                                        onChange={(e) =>
                                            setEditData(
                                                'jurusan',
                                                e.target.value,
                                            )
                                        }
                                        className={INPUT_CLASS}
                                    />
                                </FormField>
                                <FormField
                                    label="Tingkat *"
                                    htmlFor="edit-tingkat"
                                    error={editErrors.tingkat}
                                >
                                    <input
                                        id="edit-tingkat"
                                        type="number"
                                        value={editData.tingkat}
                                        onChange={(e) =>
                                            setEditData(
                                                'tingkat',
                                                Number(e.target.value),
                                            )
                                        }
                                        className={INPUT_CLASS}
                                    />
                                </FormField>
                                <FormField
                                    label="Wali kelas"
                                    htmlFor="edit-wali"
                                    error={editErrors.wali_kelas_id}
                                >
                                    <div className="relative">
                                        <select
                                            id="edit-wali"
                                            value={editData.wali_kelas_id}
                                            onChange={(e) =>
                                                setEditData(
                                                    'wali_kelas_id',
                                                    e.target.value,
                                                )
                                            }
                                            className={`${INPUT_CLASS} appearance-none pr-9`}
                                        >
                                            <option value="">
                                                -- Belum ada wali --
                                            </option>
                                            {guru.map((g) => (
                                                <option
                                                    key={g.id}
                                                    value={String(g.id)}
                                                >
                                                    {g.name}
                                                </option>
                                            ))}
                                        </select>
                                        <ChevronDown className="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-[#9CA3AF]" />
                                    </div>
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
                    {kelas.data.length === 0 ? (
                        <EmptyState
                            title="Belum ada kelas"
                            description="Tambahkan data kelas untuk dipakai di jurnal."
                        />
                    ) : (
                        <div className="grid grid-cols-1 gap-[19px] md:grid-cols-2">
                            {kelas.data.map((k) => (
                                <article
                                    key={k.id}
                                    className="flex items-center gap-2.5 rounded-xl bg-white p-[14px] shadow-[0px_2px_10px_0px_#0F172A0D]"
                                >
                                    <span className="flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-full bg-[#EFF4FF] text-[15px] font-bold text-[#2563EB]">
                                        {k.nama_kelas.charAt(0).toUpperCase()}
                                    </span>
                                    <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                                        <span className="truncate text-[13px] font-semibold text-[#1A1D26]">
                                            {k.nama_kelas}
                                        </span>
                                        <span className="truncate text-[11px] font-normal text-[#6B7280]">
                                            {k.jurusan} &bull; Tingkat{' '}
                                            {k.tingkat}
                                        </span>
                                        <span className="truncate text-[11px] font-normal text-[#6B7280]">
                                            Wali:{' '}
                                            {k.wali_kelas?.name ??
                                                '(belum ditunjuk)'}
                                        </span>
                                    </div>
                                    <span className="flex shrink-0 items-center gap-1">
                                        <button
                                            type="button"
                                            onClick={() => mulaiEdit(k)}
                                            aria-label={`Edit ${k.nama_kelas}`}
                                            className="flex h-8 w-8 items-center justify-center rounded-lg text-[#2563EB] transition hover:bg-[#EFF4FF]"
                                        >
                                            <Pencil className="h-4 w-4" />
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => hapus(k.id)}
                                            aria-label={`Hapus ${k.nama_kelas}`}
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
                    <SimplePager links={kelas.links} />
                </div>
            </PageShell>

            <button
                type="button"
                onClick={() => setShowForm(!showForm)}
                aria-label={showForm ? 'Tutup form' : 'Tambah kelas'}
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
