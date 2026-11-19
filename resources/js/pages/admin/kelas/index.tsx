import { Head, router, useForm } from '@inertiajs/react';
import { ChevronDown, Pencil, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import EmptyState from '@/components/empty-state';
import FormField, { INPUT_CLASS } from '@/components/form-field';
import PageShell from '@/components/page-shell';
import SimplePager from '@/components/simple-pager';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kelola Kelas', href: '/admin/kelas' },
];

type GuruOption = { id: number; name: string };

type JurusanOption = {
    id: number;
    kode: string;
    nama: string;
    kelompok: string;
};

type Kelas = {
    id: number;
    nama_kelas: string;
    tingkat: number;
    jurusan_id: number | null;
    jurusan?: JurusanOption | null;
    wali_kelas_id: number | null;
    wali_kelas?: { id: number; name: string } | null;
};

type PaginatedKelas = {
    data: Kelas[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
};

const ROMAWI: Record<number, string> = { 10: 'X', 11: 'XI', 12: 'XII' };

function jurusanUntukTingkat(
    jurusans: JurusanOption[],
    allowedByTingkat: Record<string, string[]>,
    tingkat: number,
): JurusanOption[] {
    const allowed = allowedByTingkat[String(tingkat)];

    if (!allowed) {
        return jurusans;
    }

    return jurusans.filter((j) => allowed.includes(j.kode));
}

function saranNama(tingkat: number, kode: string | undefined): string {
    if (!kode) {
        return '';
    }

    const romawi = ROMAWI[tingkat] ?? `T${tingkat}`;

    // Rombel tunggal PPLG/RPL tanpa nomor (warisan: "X PPLG", "XII RPL").
    if (['PPLG', 'RPL'].includes(kode)) {
        return `${romawi} ${kode}`;
    }

    return `${romawi} ${kode} 1`;
}

function JurusanSelect({
    id,
    value,
    onChange,
    options,
    error,
}: {
    id: string;
    value: string;
    onChange: (v: string) => void;
    options: JurusanOption[];
    error?: string;
}) {
    void error;

    return (
        <div className="relative">
            <select
                id={id}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                className={`${INPUT_CLASS} appearance-none pr-9`}
            >
                <option value="">-- Pilih jurusan --</option>
                {options.map((j) => (
                    <option key={j.id} value={String(j.id)}>
                        {j.kode} — {j.nama}
                    </option>
                ))}
            </select>
            <ChevronDown className="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-[#9CA3AF]" />
        </div>
    );
}

export default function KelasIndex({
    kelas,
    guru,
    jurusans,
    allowedByTingkat,
    filters,
    counts,
}: {
    kelas: PaginatedKelas;
    guru: GuruOption[];
    jurusans: JurusanOption[];
    allowedByTingkat: Record<string, string[]>;
    filters: { tingkat: string; jurusan_id: string | null };
    counts: Record<string, number>;
}) {
    const { data, setData, post, reset, errors, processing } = useForm({
        nama_kelas: '',
        jurusan_id: '' as string,
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
        jurusan_id: '' as string,
        tingkat: 10 as number,
        wali_kelas_id: '' as string,
    });

    const tingkatAktif = filters?.tingkat ?? 'semua';
    const jurusanAktif = filters?.jurusan_id ?? '';
    const isFiltered = tingkatAktif !== 'semua' || jurusanAktif !== '';

    const opsiFilterJurusan =
        tingkatAktif === 'semua'
            ? jurusans
            : jurusanUntukTingkat(
                  jurusans,
                  allowedByTingkat,
                  Number(tingkatAktif),
              );

    function navigasiFilter(tingkat: string, jurusanId: string) {
        const params: Record<string, string> = {};

        if (tingkat !== 'semua') {
            params.tingkat = tingkat;
        }

        if (jurusanId !== '') {
            params.jurusan_id = jurusanId;
        }

        router.get('/admin/kelas', params, {
            preserveState: true,
            replace: true,
        });
    }

    function gantiTabTingkat(tingkat: string) {
        // Ganti tingkat: jurusan yang tak berlaku di tingkat baru ikut direset.
        let jurusan = jurusanAktif;

        if (jurusan !== '' && tingkat !== 'semua') {
            const allowed = allowedByTingkat[tingkat];
            const kode = jurusans.find((j) => String(j.id) === jurusan)?.kode;

            if (kode && allowed && !allowed.includes(kode)) {
                jurusan = '';
            }
        }

        navigasiFilter(tingkat, jurusan);
    }

    function gantiFilterJurusan(jurusanId: string) {
        navigasiFilter(tingkatAktif, jurusanId);
    }

    function resetFilter() {
        router.get('/admin/kelas', {}, { preserveState: true, replace: true });
    }

    const TABS: { value: string; label: string }[] = [
        { value: 'semua', label: 'Semua' },
        { value: '10', label: 'X' },
        { value: '11', label: 'XI' },
        { value: '12', label: 'XII' },
    ];

    const opsiTambah = jurusanUntukTingkat(
        jurusans,
        allowedByTingkat,
        data.tingkat,
    );
    const opsiEdit = jurusanUntukTingkat(
        jurusans,
        allowedByTingkat,
        editData.tingkat,
    );
    const kodeTambah = jurusans.find(
        (j) => String(j.id) === data.jurusan_id,
    )?.kode;
    const kodeEdit = jurusans.find(
        (j) => String(j.id) === editData.jurusan_id,
    )?.kode;
    const saranTambah = saranNama(data.tingkat, kodeTambah);
    const saranEdit = saranNama(editData.tingkat, kodeEdit);

    function gantiTingkatTambah(tingkat: number) {
        setData('tingkat', tingkat);
        // Reset jurusan yang tak berlaku di tingkat baru (mis. AKL di XI).
        const allowed = allowedByTingkat[String(tingkat)];
        const kode = jurusans.find(
            (j) => String(j.id) === data.jurusan_id,
        )?.kode;

        if (kode && allowed && !allowed.includes(kode)) {
            setData('jurusan_id', '');
        }
    }

    function gantiTingkatEdit(tingkat: number) {
        setEditData('tingkat', tingkat);
        const allowed = allowedByTingkat[String(tingkat)];
        const kode = jurusans.find(
            (j) => String(j.id) === editData.jurusan_id,
        )?.kode;

        if (kode && allowed && !allowed.includes(kode)) {
            setEditData('jurusan_id', '');
        }
    }

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
            jurusan_id: k.jurusan_id ? String(k.jurusan_id) : '',
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
        <>
            <Head title="Kelola Kelas" />
            <PageShell
                title="Kelola Kelas"
                subtitle={
                    isFiltered
                        ? `${kelas.total ?? kelas.data.length} kelas sesuai filter`
                        : `${kelas.total ?? kelas.data.length} kelas terdaftar`
                }
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
                                    label="Tingkat *"
                                    htmlFor="tingkat"
                                    error={errors.tingkat}
                                >
                                    <div className="relative">
                                        <select
                                            id="tingkat"
                                            value={data.tingkat}
                                            onChange={(e) =>
                                                gantiTingkatTambah(
                                                    Number(e.target.value),
                                                )
                                            }
                                            className={`${INPUT_CLASS} appearance-none pr-9`}
                                        >
                                            <option value={10}>X (10)</option>
                                            <option value={11}>XI (11)</option>
                                            <option value={12}>XII (12)</option>
                                        </select>
                                        <ChevronDown className="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-[#9CA3AF]" />
                                    </div>
                                </FormField>
                                <FormField
                                    label="Jurusan *"
                                    htmlFor="jurusan"
                                    error={errors.jurusan_id}
                                >
                                    <JurusanSelect
                                        id="jurusan"
                                        value={data.jurusan_id}
                                        onChange={(v) =>
                                            setData('jurusan_id', v)
                                        }
                                        options={opsiTambah}
                                    />
                                </FormField>
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
                                        placeholder={
                                            saranTambah
                                                ? `Contoh: ${saranTambah}`
                                                : 'Contoh: X AKL 1'
                                        }
                                        className={INPUT_CLASS}
                                    />
                                    {saranTambah && data.nama_kelas === '' && (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setData(
                                                    'nama_kelas',
                                                    saranTambah,
                                                )
                                            }
                                            className="mt-1 text-xs font-semibold text-[#2563EB] hover:underline"
                                        >
                                            Pakai saran: {saranTambah}
                                        </button>
                                    )}
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
                                    label="Tingkat *"
                                    htmlFor="edit-tingkat"
                                    error={editErrors.tingkat}
                                >
                                    <div className="relative">
                                        <select
                                            id="edit-tingkat"
                                            value={editData.tingkat}
                                            onChange={(e) =>
                                                gantiTingkatEdit(
                                                    Number(e.target.value),
                                                )
                                            }
                                            className={`${INPUT_CLASS} appearance-none pr-9`}
                                        >
                                            <option value={10}>X (10)</option>
                                            <option value={11}>XI (11)</option>
                                            <option value={12}>XII (12)</option>
                                        </select>
                                        <ChevronDown className="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-[#9CA3AF]" />
                                    </div>
                                </FormField>
                                <FormField
                                    label="Jurusan *"
                                    htmlFor="edit-jurusan"
                                    error={editErrors.jurusan_id}
                                >
                                    <JurusanSelect
                                        id="edit-jurusan"
                                        value={editData.jurusan_id}
                                        onChange={(v) =>
                                            setEditData('jurusan_id', v)
                                        }
                                        options={opsiEdit}
                                    />
                                </FormField>
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
                                        placeholder={
                                            saranEdit
                                                ? `Contoh: ${saranEdit}`
                                                : undefined
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

                <section className="px-4 pt-3 lg:px-8">
                    <div className="flex flex-col gap-3 rounded-2xl border border-[#E5E9F2] bg-white p-3.5">
                        <div
                            className="flex gap-2 overflow-x-auto pb-1"
                            role="tablist"
                            aria-label="Filter tingkat kelas"
                        >
                            {TABS.map((tab) => {
                                const aktif = tingkatAktif === tab.value;
                                const jumlah = counts?.[tab.value] ?? 0;

                                return (
                                    <button
                                        key={tab.value}
                                        type="button"
                                        role="tab"
                                        aria-selected={aktif}
                                        onClick={() =>
                                            gantiTabTingkat(tab.value)
                                        }
                                        className={`inline-flex shrink-0 items-center gap-1.5 rounded-full px-4 py-2 text-xs font-semibold whitespace-nowrap transition ${
                                            aktif
                                                ? 'bg-[#2563EB] text-white'
                                                : 'border border-[#E5E9F2] bg-white text-[#6B7280] hover:border-[#2563EB] hover:text-[#2563EB]'
                                        }`}
                                    >
                                        {tab.value === 'semua'
                                            ? `Semua (${jumlah})`
                                            : `${tab.label} (${jumlah})`}
                                    </button>
                                );
                            })}
                        </div>
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-end">
                            <div className="relative flex-1">
                                <label
                                    htmlFor="filter-jurusan"
                                    className="mb-1.5 block text-xs font-semibold text-[#1A1D26]"
                                >
                                    Jurusan
                                </label>
                                <select
                                    id="filter-jurusan"
                                    value={jurusanAktif}
                                    onChange={(e) =>
                                        gantiFilterJurusan(e.target.value)
                                    }
                                    className={`${INPUT_CLASS} appearance-none pr-9`}
                                >
                                    <option value="">
                                        Semua jurusan
                                    </option>
                                    {opsiFilterJurusan.map((j) => (
                                        <option
                                            key={j.id}
                                            value={String(j.id)}
                                        >
                                            {j.kode} — {j.nama}
                                        </option>
                                    ))}
                                </select>
                                <ChevronDown className="pointer-events-none absolute right-3 bottom-3.5 h-4 w-4 -translate-y-1/2 text-[#9CA3AF]" />
                            </div>
                            {isFiltered && (
                                <button
                                    type="button"
                                    onClick={resetFilter}
                                    className="inline-flex h-11 shrink-0 items-center justify-center rounded-[10px] border border-[#E5E9F2] bg-white px-4 text-xs font-semibold whitespace-nowrap text-[#2563EB] transition hover:bg-[#EFF4FF]"
                                >
                                    Reset filter
                                </button>
                            )}
                        </div>
                    </div>
                </section>

                <section className="flex flex-col gap-[19px] px-4 pt-3.5 pb-6 lg:px-8 lg:py-6">
                    {kelas.data.length === 0 ? (
                        <EmptyState
                            title={
                                isFiltered
                                    ? 'Tidak ada kelas sesuai filter'
                                    : 'Belum ada kelas'
                            }
                            description={
                                isFiltered
                                    ? 'Coba ubah tingkat atau jurusan yang dipilih.'
                                    : 'Tambahkan data kelas untuk dipakai di jurnal.'
                            }
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
                                            {k.jurusan?.kode ?? '-'}
                                            {k.jurusan?.nama
                                                ? ` • ${k.jurusan.nama}`
                                                : ''}{' '}
                                            &bull; Tingkat {k.tingkat}
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
        </>
    );
}

KelasIndex.layout = {
    breadcrumbs,
};
