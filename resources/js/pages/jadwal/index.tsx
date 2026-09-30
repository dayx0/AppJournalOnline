import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import EmptyState from '@/components/empty-state';
import FormField, { INPUT_CLASS } from '@/components/form-field';
import PageShell from '@/components/page-shell';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Jadwal', href: '/jadwal' }];

type JadwalRow = {
    id: number;
    kelas_id: number;
    mapel_id: number;
    guru_id: number | null;
    hari: string;
    jam_mulai: string;
    jam_selesai: string;
    semester: string;
    aktif: boolean;
    kelas?: { id: number; nama_kelas: string } | null;
    mataPelajaran?: {
        id: number;
        nama_mapel: string;
        kode_mapel: string;
    } | null;
    guru?: { id: number; name: string } | null;
};

type GridColumn = { mulai: string; selesai: string; istirahat: boolean };

type GridCell = { jadwal: JadwalRow | null; span: number; istirahat: boolean };

type GridDay = { hari: string; label: string; cells: GridCell[] };

type Option = {
    id: number;
    nama_kelas?: string;
    nama_mapel?: string;
    name?: string;
};

const EMPTY = {
    kelas_id: '',
    mapel_id: '',
    guru_id: '',
    hari: 'senin',
    jam_mulai: '',
    jam_selesai: '',
    semester: '',
    aktif: true as boolean,
};

const ALUR = [
    { no: '1', teks: 'Wali/Admin susun jadwal mingguan di sini' },
    { no: '2', teks: 'Slot kosong dibuat otomatis tiap jam 00:00' },
    { no: '3', teks: 'Guru mengisi slot pada jamnya' },
    { no: '4', teks: 'MPK memvalidasi kehadiran guru' },
];

function JadwalForm({
    idPrefix,
    data,
    setData,
    errors,
    processing,
    submitLabel,
    onSubmit,
    kelas,
    mapel,
    guru,
    hari,
}: {
    idPrefix: string;
    data: typeof EMPTY;
    setData: (key: keyof typeof EMPTY, value: string | boolean) => void;
    errors: Partial<Record<keyof typeof EMPTY, string>>;
    processing: boolean;
    submitLabel: string;
    onSubmit: (e: React.FormEvent) => void;
    kelas: Option[];
    mapel: Option[];
    guru: Option[];
    hari: Record<string, string>;
}) {
    return (
        <form
            onSubmit={onSubmit}
            className="grid grid-cols-1 gap-3 lg:grid-cols-3"
        >
            <FormField
                label="Kelas *"
                htmlFor={`${idPrefix}-kelas`}
                error={errors.kelas_id}
            >
                <select
                    id={`${idPrefix}-kelas`}
                    value={data.kelas_id}
                    onChange={(e) => setData('kelas_id', e.target.value)}
                    className={`${INPUT_CLASS} appearance-none pr-9`}
                >
                    <option value="">-- Pilih kelas --</option>
                    {kelas.map((k) => (
                        <option key={k.id} value={String(k.id)}>
                            {k.nama_kelas}
                        </option>
                    ))}
                </select>
            </FormField>
            <FormField
                label="Mata pelajaran *"
                htmlFor={`${idPrefix}-mapel`}
                error={errors.mapel_id}
            >
                <select
                    id={`${idPrefix}-mapel`}
                    value={data.mapel_id}
                    onChange={(e) => setData('mapel_id', e.target.value)}
                    className={`${INPUT_CLASS} appearance-none pr-9`}
                >
                    <option value="">-- Pilih mapel --</option>
                    {mapel.map((m) => (
                        <option key={m.id} value={String(m.id)}>
                            {m.nama_mapel}
                        </option>
                    ))}
                </select>
            </FormField>
            <FormField
                label="Guru pengampu *"
                htmlFor={`${idPrefix}-guru`}
                error={errors.guru_id}
            >
                <select
                    id={`${idPrefix}-guru`}
                    value={data.guru_id}
                    onChange={(e) => setData('guru_id', e.target.value)}
                    className={`${INPUT_CLASS} appearance-none pr-9`}
                >
                    <option value="">-- Pilih guru --</option>
                    {guru.map((g) => (
                        <option key={g.id} value={String(g.id)}>
                            {g.name}
                        </option>
                    ))}
                </select>
            </FormField>
            <FormField
                label="Hari *"
                htmlFor={`${idPrefix}-hari`}
                error={errors.hari}
            >
                <select
                    id={`${idPrefix}-hari`}
                    value={data.hari}
                    onChange={(e) => setData('hari', e.target.value)}
                    className={`${INPUT_CLASS} appearance-none pr-9`}
                >
                    {Object.entries(hari).map(([key, label]) => (
                        <option key={key} value={key}>
                            {label}
                        </option>
                    ))}
                </select>
            </FormField>
            <FormField
                label="Jam mulai *"
                htmlFor={`${idPrefix}-mulai`}
                error={errors.jam_mulai}
            >
                <input
                    id={`${idPrefix}-mulai`}
                    type="time"
                    value={data.jam_mulai}
                    onChange={(e) => setData('jam_mulai', e.target.value)}
                    className={INPUT_CLASS}
                />
            </FormField>
            <FormField
                label="Jam selesai *"
                htmlFor={`${idPrefix}-selesai`}
                error={errors.jam_selesai}
            >
                <input
                    id={`${idPrefix}-selesai`}
                    type="time"
                    value={data.jam_selesai}
                    onChange={(e) => setData('jam_selesai', e.target.value)}
                    className={INPUT_CLASS}
                />
            </FormField>
            <FormField
                label="Semester *"
                htmlFor={`${idPrefix}-semester`}
                error={errors.semester}
            >
                <input
                    id={`${idPrefix}-semester`}
                    value={data.semester}
                    onChange={(e) => setData('semester', e.target.value)}
                    placeholder="2026/2027-ganjil"
                    className={INPUT_CLASS}
                />
            </FormField>
            <FormField label="Status" htmlFor={`${idPrefix}-aktif`}>
                <select
                    id={`${idPrefix}-aktif`}
                    value={data.aktif ? '1' : '0'}
                    onChange={(e) => setData('aktif', e.target.value === '1')}
                    className={`${INPUT_CLASS} appearance-none pr-9`}
                >
                    <option value="1">Aktif (terbit tiap hari)</option>
                    <option value="0">Nonaktif (tidak diterbitkan)</option>
                </select>
            </FormField>
            <div className="flex items-end lg:col-span-1">
                <button
                    type="submit"
                    disabled={processing}
                    className="flex h-12 w-full items-center justify-center rounded-[10px] bg-[#2563EB] text-sm font-bold whitespace-nowrap text-white transition enabled:hover:bg-[#1D4ED8] disabled:opacity-60"
                >
                    {processing ? 'Menyimpan...' : submitLabel}
                </button>
            </div>
        </form>
    );
}

export default function JadwalIndex({
    kelasAktif,
    columns,
    days,
    total,
    nonaktif,
    kelas,
    mapel,
    guru,
    hari,
    filters,
    kelasSaya,
}: {
    kelasAktif: { id: number; nama_kelas: string } | null;
    columns: GridColumn[];
    days: GridDay[];
    total: number;
    nonaktif: JadwalRow[];
    kelas: Option[];
    mapel: Option[];
    guru: Option[];
    hari: Record<string, string>;
    filters: { kelas_id?: string };
    kelasSaya: Option[] | null;
}) {
    const { data, setData, post, reset, errors, processing } = useForm({
        ...EMPTY,
    });
    const [showForm, setShowForm] = useState(false);
    const [editing, setEditing] = useState<JadwalRow | null>(null);
    const {
        data: editData,
        setData: setEditData,
        put,
        errors: editErrors,
        processing: editProcessing,
    } = useForm({ ...EMPTY });
    const [filterKelas, setFilterKelas] = useState(filters.kelas_id ?? '');
    const {
        data: impData,
        setData: setImpData,
        post: impPost,
        errors: impErrors,
        processing: impProcessing,
    } = useForm<{ file: File | null; semester: string }>({
        file: null,
        semester: '2026/2027-ganjil',
    });
    const [showImport, setShowImport] = useState(false);

    // Wali hanya mengelola kelas binaannya: kunci pilihan + kunci filter.
    const terkunci = kelasSaya !== null;
    const daftarKelas = terkunci ? (kelasSaya ?? []) : kelas;
    const subJudul = kelasAktif
        ? `Jadwal: ${kelasAktif.nama_kelas} • ${total} slot`
        : 'Belum ada kelas yang bisa dikelola';

    // Nomor kolom ala tabel dinding: hanya kolom pelajaran yang dinomori,
    // kolom istirahat bertanda IST (seperti gambar jadwal sekolah).
    // Murni turunan dari index (tanpa variabel counter) agar lolos
    // aturan immutability React: nomor = posisi - jumlah IST sebelumnya.
    const nomorKolom = columns.map((c, i) =>
        c.istirahat
            ? 'IST'
            : String(
                  i + 1 - columns.slice(0, i).filter((x) => x.istirahat).length,
              ),
    );

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post('/jadwal', {
            onSuccess: () => {
                reset();
                setShowForm(false);
            },
        });
    }

    function submitImport(e: React.FormEvent) {
        e.preventDefault();

        if (!impData.file) {
            return;
        }

        // Inertia otomatis memakai multipart saat ada File di data.
        impPost('/jadwal/import');
    }

    function mulaiEdit(j: JadwalRow) {
        setEditing(j);
        setEditData({
            kelas_id: String(j.kelas_id),
            mapel_id: String(j.mapel_id),
            guru_id: j.guru_id ? String(j.guru_id) : '',
            hari: j.hari,
            jam_mulai: j.jam_mulai.slice(0, 5),
            jam_selesai: j.jam_selesai.slice(0, 5),
            semester: j.semester,
            aktif: j.aktif,
        });
        // Form edit ada di atas grid: gulirkan ke sana agar langsung terlihat.
        requestAnimationFrame(() => {
            document
                .getElementById('form-edit-jadwal')
                ?.scrollIntoView({ behavior: 'smooth' });
        });
    }

    function submitEdit(e: React.FormEvent) {
        e.preventDefault();

        if (!editing) {
            return;
        }

        put(`/jadwal/${editing.id}`, { onSuccess: () => setEditing(null) });
    }

    function hapus(id: number) {
        if (
            confirm(
                'Yakin hapus jadwal ini? Slot yang sudah terbit hari ini tidak ikut terhapus.',
            )
        ) {
            router.delete(`/jadwal/${id}`);
        }
    }

    function applyFilter(kelasId: string) {
        setFilterKelas(kelasId);
        router.get('/jadwal', kelasId === '' ? {} : { kelas_id: kelasId }, {
            preserveState: true,
            replace: true,
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`Jadwal${kelasAktif ? `: ${kelasAktif.nama_kelas}` : ''}`}
            />
            <PageShell
                framed={false}
                title={`Jadwal${kelasAktif ? `: ${kelasAktif.nama_kelas}` : ''}`}
                subtitle={subJudul}
            >
                {/* Panduan alur untuk orang awam */}
                <section className="px-4 pt-3 lg:px-8">
                    <div className="grid grid-cols-2 gap-2 rounded-2xl bg-[#EFF4FF] p-3.5 lg:grid-cols-4">
                        {ALUR.map((langkah) => (
                            <div
                                key={langkah.no}
                                className="flex items-start gap-2"
                            >
                                <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#2563EB] text-[11px] font-bold text-white">
                                    {langkah.no}
                                </span>
                                <span className="text-[11px] font-medium text-[#1E40AF] lg:text-xs">
                                    {langkah.teks}
                                </span>
                            </div>
                        ))}
                    </div>
                </section>

                <section className="flex flex-col gap-2 px-4 pt-3 sm:flex-row sm:items-center lg:px-8">
                    {!terkunci && (
                        <select
                            aria-label="Pilih kelas"
                            value={filterKelas}
                            onChange={(e) => applyFilter(e.target.value)}
                            className={`${INPUT_CLASS} max-w-xs appearance-none pr-9`}
                        >
                            {daftarKelas.map((k) => (
                                <option key={k.id} value={String(k.id)}>
                                    {k.nama_kelas}
                                </option>
                            ))}
                        </select>
                    )}
                    <button
                        type="button"
                        onClick={() => setShowForm(!showForm)}
                        className="inline-flex shrink-0 items-center gap-2 rounded-full bg-[#2563EB] px-4 py-2.5 text-sm font-semibold text-white"
                    >
                        <Plus className="h-4 w-4 text-white" />
                        {showForm ? 'Tutup Form' : 'Tambah Jadwal'}
                    </button>
                    <button
                        type="button"
                        onClick={() => setShowImport(!showImport)}
                        className="inline-flex shrink-0 items-center gap-2 rounded-full border border-[#E5E9F2] bg-white px-4 py-2.5 text-sm font-semibold text-[#2563EB]"
                    >
                        {showImport ? 'Tutup Import' : 'Import Excel'}
                    </button>
                </section>

                {showImport && (
                    <section className="px-4 pt-3 lg:px-8">
                        <div className="flex flex-col gap-2.5 rounded-2xl border border-[#E5E9F2] bg-white p-3.5">
                            <h2 className="text-[15px] font-bold text-[#1A1D26]">
                                Import dari Excel
                            </h2>
                            <p className="text-xs font-normal text-[#6B7280]">
                                1. Unduh{' '}
                                <a
                                    href="/jadwal/template"
                                    className="font-semibold text-[#2563EB] underline"
                                >
                                    template Excel
                                </a>{' '}
                                lalu isi barisnya. 2. Unggah untuk pratinjau —
                                tidak langsung tersimpan. 3. Periksa baris
                                error, lalu konfirmasi.
                            </p>
                            <form
                                onSubmit={submitImport}
                                className="grid grid-cols-1 gap-3 lg:grid-cols-3"
                            >
                                <FormField
                                    label="Semester *"
                                    htmlFor="imp-semester"
                                    error={impErrors.semester}
                                >
                                    <input
                                        id="imp-semester"
                                        value={impData.semester}
                                        onChange={(e) =>
                                            setImpData(
                                                'semester',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="2026/2027-ganjil"
                                        className={INPUT_CLASS}
                                    />
                                </FormField>
                                <FormField
                                    label="File (.xlsx/.xls/.csv, maks 2MB) *"
                                    htmlFor="imp-file"
                                    error={impErrors.file}
                                >
                                    <input
                                        id="imp-file"
                                        type="file"
                                        accept=".xlsx,.xls,.csv"
                                        onChange={(e) =>
                                            setImpData(
                                                'file',
                                                e.target.files?.[0] ?? null,
                                            )
                                        }
                                        className="text-xs font-normal text-[#6B7280]"
                                    />
                                </FormField>
                                <div className="flex items-end">
                                    <button
                                        type="submit"
                                        disabled={
                                            impProcessing || !impData.file
                                        }
                                        className="flex h-12 w-full items-center justify-center rounded-[10px] bg-[#16A34A] text-sm font-bold whitespace-nowrap text-white transition enabled:hover:opacity-90 disabled:opacity-60"
                                    >
                                        {impProcessing
                                            ? 'Membaca...'
                                            : 'Pratinjau'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </section>
                )}

                {showForm && (
                    <section className="px-4 pt-3 lg:px-8">
                        <div className="flex flex-col gap-2.5 rounded-2xl border border-[#E5E9F2] bg-white p-3.5">
                            <h2 className="text-[15px] font-bold text-[#1A1D26]">
                                Tambah Jadwal
                            </h2>
                            <p className="text-xs font-normal text-[#6B7280]">
                                Cukup diisi sekali per semester. Slot harian
                                dibuat otomatis — tidak perlu membuat jurnal
                                manual lagi.
                            </p>
                            <JadwalForm
                                idPrefix="add"
                                data={data}
                                setData={setData}
                                errors={errors}
                                processing={processing}
                                submitLabel="Simpan"
                                onSubmit={submit}
                                kelas={daftarKelas}
                                mapel={mapel}
                                guru={guru}
                                hari={hari}
                            />
                        </div>
                    </section>
                )}

                {editing && (
                    <section
                        id="form-edit-jadwal"
                        className="px-4 pt-3 lg:px-8"
                    >
                        <div className="flex flex-col gap-2.5 rounded-2xl border border-[#E5E9F2] bg-white p-3.5">
                            <h2 className="text-[15px] font-bold text-[#1A1D26]">
                                Edit Jadwal
                            </h2>
                            <JadwalForm
                                idPrefix="edit"
                                data={editData}
                                setData={setEditData}
                                errors={editErrors}
                                processing={editProcessing}
                                submitLabel="Update"
                                onSubmit={submitEdit}
                                kelas={daftarKelas}
                                mapel={mapel}
                                guru={guru}
                                hari={hari}
                            />
                            <button
                                type="button"
                                onClick={() => setEditing(null)}
                                className="text-sm font-semibold text-[#6B7280]"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                onClick={() => hapus(editing.id)}
                                className="inline-flex items-center gap-2 self-start rounded-full border border-[#FECACA] bg-white px-4 py-2 text-xs font-semibold text-[#DC2626]"
                            >
                                <Trash2 className="h-4 w-4" />
                                Hapus slot ini
                            </button>
                        </div>
                    </section>
                )}

                {/* Tabel dinding: baris = hari, kolom = jam, sel bergabung */}
                <section className="px-4 pt-3.5 pb-2 lg:px-8 lg:py-6">
                    {days.length === 0 ? (
                        <EmptyState
                            title="Belum ada jadwal"
                            description={
                                terkunci
                                    ? 'Kelas binaanmu belum punya template. Tekan Tambah Jadwal — cukup sekali per semester.'
                                    : 'Pilih kelas lalu tekan Tambah Jadwal untuk membuat template mingguan.'
                            }
                        />
                    ) : (
                        <div className="overflow-x-auto rounded-2xl border border-[#E5E9F2] bg-white">
                            <table className="w-full min-w-[720px] border-collapse text-center">
                                <thead>
                                    <tr className="bg-[#F6F8FC]">
                                        <th className="sticky left-0 border-r border-[#E5E9F2] bg-[#F6F8FC] px-3 py-2 text-xs font-bold text-[#1A1D26]">
                                            Hari
                                        </th>
                                        {columns.map((c, i) => (
                                            <th
                                                key={i}
                                                className={`border-l border-[#E5E9F2] px-1 py-2 ${c.istirahat ? 'bg-[#F3F4F6]' : ''}`}
                                            >
                                                <span className="block text-xs font-bold text-[#1A1D26]">
                                                    {nomorKolom[i]}
                                                </span>
                                                <span className="block text-[10px] font-normal whitespace-nowrap text-[#6B7280]">
                                                    {c.mulai.slice(0, 5)}–
                                                    {c.selesai.slice(0, 5)}
                                                </span>
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {days.map((d) => (
                                        <tr
                                            key={d.hari}
                                            className="border-t border-[#E5E9F2]"
                                        >
                                            <td className="sticky left-0 border-r border-[#E5E9F2] bg-white px-3 py-2 text-xs font-bold text-[#1A1D26]">
                                                {d.label}
                                            </td>
                                            {d.cells.map((cell, i) =>
                                                cell.jadwal ? (
                                                    <td
                                                        key={i}
                                                        colSpan={cell.span}
                                                        onClick={() =>
                                                            mulaiEdit(
                                                                cell.jadwal!,
                                                            )
                                                        }
                                                        title="Klik untuk edit"
                                                        className="cursor-pointer border-l border-[#E5E9F2] bg-[#EFF4FF] px-1 py-2 transition hover:bg-[#DBEAFE]"
                                                    >
                                                        <span className="block text-xs font-bold whitespace-nowrap text-[#1E40AF]">
                                                            {cell.jadwal
                                                                .mataPelajaran
                                                                ?.kode_mapel ??
                                                                '-'}
                                                        </span>
                                                        <span className="block truncate text-[10px] font-normal text-[#6B7280]">
                                                            {cell.jadwal.guru
                                                                ?.name ??
                                                                '(tanpa guru)'}
                                                        </span>
                                                    </td>
                                                ) : (
                                                    <td
                                                        key={i}
                                                        className={`border-l border-[#E5E9F2] px-1 py-2 ${
                                                            cell.istirahat
                                                                ? 'bg-[#F3F4F6] text-[10px] font-semibold text-[#9CA3AF]'
                                                                : 'bg-white'
                                                        }`}
                                                    >
                                                        {cell.istirahat
                                                            ? 'IST'
                                                            : '–'}
                                                    </td>
                                                ),
                                            )}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                    <p className="pt-2 text-[11px] font-normal text-[#9CA3AF]">
                        Ketuk kotak mapel untuk mengedit. Kolom abu-abu (IST) =
                        jam istirahat. Berlaku satu semester dan bisa diubah
                        kapan saja.
                    </p>
                </section>

                {nonaktif.length > 0 && (
                    <section className="px-4 pb-6 lg:px-8">
                        <h2 className="pb-2 text-sm font-bold text-[#6B7280]">
                            Nonaktif (tidak diterbitkan) — {nonaktif.length}{' '}
                            slot
                        </h2>
                        <div className="grid grid-cols-1 gap-2 md:grid-cols-2">
                            {nonaktif.map((j) => (
                                <button
                                    key={j.id}
                                    type="button"
                                    onClick={() => mulaiEdit(j)}
                                    className="flex items-center gap-2 rounded-xl border border-dashed border-[#E5E9F2] bg-white p-3 text-left opacity-70"
                                >
                                    <span className="min-w-0 flex-1 truncate text-xs font-semibold text-[#1A1D26]">
                                        {hari[j.hari] ?? j.hari} •{' '}
                                        {j.jam_mulai.slice(0, 5)}–
                                        {j.jam_selesai.slice(0, 5)} •{' '}
                                        {j.mataPelajaran?.kode_mapel ?? '-'}
                                    </span>
                                    <Pencil className="h-4 w-4 shrink-0 text-[#2563EB]" />
                                </button>
                            ))}
                        </div>
                    </section>
                )}
            </PageShell>
        </AppLayout>
    );
}
