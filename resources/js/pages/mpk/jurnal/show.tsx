import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import DetailRow from '@/components/detail-row';
import JournalHistoryTimeline from '@/components/journal-history-timeline';
import PageShell from '@/components/page-shell';
import StatusBadge from '@/components/status-badge';
import { Textarea } from '@/components/ui/textarea';
import { formatTanggal } from '@/lib/format';
import type { Journal, JournalHistory, ValidationStatus } from '@/types';

const RECAP = [
    { key: 'hadir', label: 'Hadir', valueClass: 'text-[#16A34A]' },
    { key: 'izin', label: 'Izin', valueClass: 'text-[#D97706]' },
    { key: 'sakit', label: 'Sakit', valueClass: 'text-[#DC2626]' },
    { key: 'alpha', label: 'Alpha', valueClass: 'text-[#6B7280]' },
] as const;

export default function ValidasiShow({
    journal,
    histories = [],
}: {
    journal: Journal;
    histories?: JournalHistory[];
}) {
    const validateForm = useForm({ validation_note: '' });
    const tolakForm = useForm({ validation_note: '' });
    const kosongForm = useForm({ validation_note: '' });
    const [tolakOpen, setTolakOpen] = useState(false);

    const absensi = journal.absensi;
    const status: ValidationStatus = journal.status ?? 'pending';

    return (
        <>
            <Head title="Periksa Jurnal" />
            <PageShell
                framed={false}
                title="Periksa Jurnal"
                subtitle={`${journal.kelas?.nama_kelas ?? '-'} • ${journal.mataPelajaran?.nama_mapel ?? '-'} • Guru: ${journal.guru?.name ?? '-'}`}
                backHref="/mpk/jurnal"
                backLabel="Kembali ke validasi jurnal"
            >
                <div className="grid grid-cols-1 gap-3 px-4 pt-3 pb-6 lg:grid-cols-5 lg:gap-4 lg:px-8 lg:py-6">
                    <section className="flex flex-col gap-2.5 rounded-2xl border border-[#E5E9F2] bg-white p-3.5 lg:col-span-2 lg:self-start">
                        <div className="flex items-center justify-between gap-2">
                            <span className="text-xs font-semibold whitespace-nowrap text-[#1A1D26]">
                                {formatTanggal(journal.tanggal)}
                            </span>
                            <StatusBadge status={status} />
                        </div>
                        <h2 className="text-[15px] font-bold text-[#1A1D26] lg:text-lg">
                            {journal.kelas?.nama_kelas ?? '-'} -{' '}
                            {journal.mataPelajaran?.nama_mapel ?? '-'}
                        </h2>
                        <p className="truncate text-xs font-normal text-[#6B7280]">
                            {journal.jam_mulai.slice(0, 5)} –{' '}
                            {journal.jam_selesai.slice(0, 5)} • Guru:{' '}
                            {journal.guru?.name ?? '-'}
                        </p>
                        <div className="grid grid-cols-4 gap-2">
                            {RECAP.map((item) => (
                                <div
                                    key={item.key}
                                    className="flex flex-col items-center gap-0.5 rounded-xl border border-[#E5E9F2] bg-white px-2 py-2.5"
                                >
                                    <span
                                        className={`text-lg leading-6 font-extrabold ${item.valueClass}`}
                                    >
                                        {absensi?.[item.key] ?? '–'}
                                    </span>
                                    <span className="text-[11px] font-medium text-[#6B7280]">
                                        {item.label}
                                    </span>
                                </div>
                            ))}
                        </div>
                        {journal.validator && (
                            <p className="text-xs font-normal text-[#6B7280]">
                                Terakhir divalidasi oleh{' '}
                                {journal.validator.name}
                                {journal.validated_at
                                    ? ` pada ${formatTanggal(journal.validated_at.slice(0, 10))}`
                                    : ''}
                            </p>
                        )}
                        {journal.validation_note && (
                            <p className="text-[13px] font-normal whitespace-pre-line text-[#1A1D26]">
                                Catatan: {journal.validation_note}
                            </p>
                        )}
                    </section>

                    <div className="flex flex-col gap-2.5 lg:col-span-3">
                        <DetailRow
                            label="Materi"
                            value={journal.materi ?? '(belum diisi)'}
                        />
                        <DetailRow
                            label="Kegiatan"
                            value={journal.kegiatan ?? '(belum diisi)'}
                        />
                        {journal.catatan && (
                            <DetailRow
                                label="Catatan Guru"
                                value={journal.catatan}
                            />
                        )}

                        <section className="flex flex-col gap-2.5 rounded-2xl border border-[#E5E9F2] bg-white p-3.5">
                            <h3 className="text-sm font-bold whitespace-nowrap text-[#1A1D26] lg:text-base">
                                Keputusan Validasi
                            </h3>
                            {journal.status === 'divalidasi' && (
                                <p className="rounded-xl bg-[#E8F7EE] p-3 text-xs font-normal text-[#16A34A]">
                                    Jurnal ini sudah divalidasi. Memvalidasi
                                    ulang akan memperbarui waktu validasi.
                                </p>
                            )}
                            {journal.status === 'ditolak' && (
                                <p className="rounded-xl bg-[#FDECEC] p-3 text-xs font-normal text-[#DC2626]">
                                    Jurnal ini ditolak karena guru tidak hadir
                                    di kelas. Keputusan ini final.
                                </p>
                            )}
                            {journal.status === 'jam_kosong' && (
                                <p className="rounded-xl bg-[#FDECEC] p-3 text-xs font-normal text-[#DC2626]">
                                    Slot ini dinyatakan kosong. Jika guru datang
                                    susulan dan mengisi jurnal, status berubah
                                    menjadi terlambat.
                                </p>
                            )}
                            {journal.status === 'izin' && (
                                <p className="rounded-xl bg-[#EFF4FF] p-3 text-xs font-normal text-[#2563EB]">
                                    Slot ini dispensasi resmi (izin/tugas dinas)
                                    — tidak dapat divalidasi, ditolak, atau
                                    ditandai kosong.
                                </p>
                            )}
                            {journal.status === 'pending' && (
                                <form
                                    onSubmit={(e) => {
                                        e.preventDefault();

                                        if (
                                            !confirm(
                                                'Yakin validasi jurnal ini?',
                                            )
                                        ) {
                                            return;
                                        }

                                        validateForm.post(
                                            `/mpk/jurnal/${journal.id}/validate`,
                                            {
                                                onSuccess: () =>
                                                    validateForm.reset(),
                                            },
                                        );
                                    }}
                                    className="flex flex-col gap-1.5"
                                >
                                    <label
                                        htmlFor="note-ok"
                                        className="text-xs font-semibold whitespace-nowrap text-[#1A1D26]"
                                    >
                                        Catatan (opsional)
                                    </label>
                                    <Textarea
                                        id="note-ok"
                                        maxLength={1000}
                                        value={
                                            validateForm.data.validation_note
                                        }
                                        onChange={(e) =>
                                            validateForm.setData(
                                                'validation_note',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Catatan untuk guru (boleh kosong)"
                                    />
                                    <button
                                        type="submit"
                                        disabled={validateForm.processing}
                                        className="flex h-12 w-full items-center justify-center gap-2 rounded-[10px] bg-[#2563EB] text-sm font-bold whitespace-nowrap text-white transition enabled:hover:bg-[#1D4ED8] disabled:opacity-60"
                                    >
                                        {validateForm.processing
                                            ? 'Memvalidasi...'
                                            : 'Validasi Jurnal'}
                                    </button>
                                </form>
                            )}

                            {journal.status === 'pending' &&
                                (!tolakOpen ? (
                                    <button
                                        type="button"
                                        onClick={() => setTolakOpen(true)}
                                        className="flex h-12 w-full items-center justify-center rounded-[10px] bg-[#DC2626] text-sm font-bold whitespace-nowrap text-white transition hover:opacity-90"
                                    >
                                        Tolak (Guru Tidak Hadir)
                                    </button>
                                ) : (
                                    <form
                                        onSubmit={(e) => {
                                            e.preventDefault();

                                            if (
                                                !confirm(
                                                    'Yakin tolak jurnal ini? Guru dinyatakan tidak hadir.',
                                                )
                                            ) {
                                                return;
                                            }

                                            tolakForm.post(
                                                `/mpk/jurnal/${journal.id}/tolak`,
                                                {
                                                    onSuccess: () => {
                                                        tolakForm.reset();
                                                        setTolakOpen(false);
                                                    },
                                                },
                                            );
                                        }}
                                        className="flex flex-col gap-1.5 rounded-xl border border-[#FECACA] bg-[#FEF2F2] p-3"
                                    >
                                        <label
                                            htmlFor="note-tolak"
                                            className="text-xs font-semibold whitespace-nowrap text-[#1A1D26]"
                                        >
                                            Alasan penolakan (wajib)
                                        </label>
                                        <Textarea
                                            id="note-tolak"
                                            required
                                            maxLength={1000}
                                            value={
                                                tolakForm.data.validation_note
                                            }
                                            onChange={(e) =>
                                                tolakForm.setData(
                                                    'validation_note',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Contoh: guru tidak masuk kelas sampai jam berakhir..."
                                        />
                                        {tolakForm.errors.validation_note && (
                                            <p className="text-xs font-normal text-[#DC2626]">
                                                {
                                                    tolakForm.errors
                                                        .validation_note
                                                }
                                            </p>
                                        )}
                                        <div className="flex gap-2">
                                            <button
                                                type="submit"
                                                disabled={tolakForm.processing}
                                                className="flex h-12 flex-1 items-center justify-center rounded-[10px] bg-[#DC2626] text-sm font-bold whitespace-nowrap text-white transition hover:opacity-90 disabled:opacity-60"
                                            >
                                                {tolakForm.processing
                                                    ? 'Mengirim...'
                                                    : 'Kirim Penolakan'}
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setTolakOpen(false)
                                                }
                                                className="flex h-12 items-center justify-center rounded-[10px] border border-[#E5E9F2] bg-white px-4 text-sm font-semibold whitespace-nowrap text-[#6B7280]"
                                            >
                                                Batal
                                            </button>
                                        </div>
                                    </form>
                                ))}

                            {journal.status === 'menunggu' && (
                                <form
                                    onSubmit={(e) => {
                                        e.preventDefault();

                                        if (
                                            !confirm(
                                                'Yakin tandai slot ini sebagai jam kosong?',
                                            )
                                        ) {
                                            return;
                                        }

                                        kosongForm.post(
                                            `/mpk/jurnal/${journal.id}/tandai-kosong`,
                                            {
                                                onSuccess: () =>
                                                    kosongForm.reset(),
                                            },
                                        );
                                    }}
                                    className="flex flex-col gap-1.5"
                                >
                                    <label
                                        htmlFor="note-kosong"
                                        className="text-xs font-semibold whitespace-nowrap text-[#1A1D26]"
                                    >
                                        Keterangan (opsional)
                                    </label>
                                    <Textarea
                                        id="note-kosong"
                                        maxLength={1000}
                                        value={kosongForm.data.validation_note}
                                        onChange={(e) =>
                                            kosongForm.setData(
                                                'validation_note',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Keterangan jam kosong (boleh kosong)"
                                    />
                                    <button
                                        type="submit"
                                        disabled={kosongForm.processing}
                                        className="flex h-12 w-full items-center justify-center rounded-[10px] bg-[#D97706] text-sm font-bold whitespace-nowrap text-white transition hover:opacity-90 disabled:opacity-60"
                                    >
                                        {kosongForm.processing
                                            ? 'Menyimpan...'
                                            : 'Tandai Jam Kosong'}
                                    </button>
                                </form>
                            )}
                        </section>
                    </div>

                    <div className="lg:col-span-5">
                        <JournalHistoryTimeline histories={histories} />
                    </div>
                </div>
            </PageShell>
        </>
    );
}
