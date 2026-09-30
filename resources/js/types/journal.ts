export interface JournalGuru {
    id: number;
    name: string;
}

export interface JournalKelas {
    id: number;
    nama_kelas: string;
}

export interface JournalMapel {
    id: number;
    nama_mapel: string;
}

export interface JournalAbsensi {
    id: number;
    hadir: number;
    izin: number;
    sakit: number;
    alpha: number;
}

export type ValidationStatus =
    | 'menunggu'
    | 'pending'
    | 'divalidasi'
    | 'ditolak'
    | 'jam_kosong'
    | 'izin'
    | 'terlambat';

export interface JournalValidator {
    id: number;
    name: string;
}

export type JournalHistoryAksi =
    | 'dibuat'
    | 'diisi'
    | 'divalidasi'
    | 'ditolak'
    | 'jam_kosong'
    | 'terlambat'
    | 'izin'
    | 'revisi'
    | 'reset_pending';

export interface JournalHistory {
    id: number;
    journal_id: number;
    actor_id: number | null;
    aksi: JournalHistoryAksi;
    dari_status: string | null;
    ke_status: string | null;
    catatan: string | null;
    created_at: string;
    actor: { id: number; name: string } | null;
}

export interface Journal {
    id: number;
    tanggal: string;
    jam_mulai: string;
    jam_selesai: string;
    // Nullable: slot menunggu yang belum diisi guru belum punya materi/kegiatan.
    materi: string | null;
    kegiatan: string | null;
    catatan: string | null;
    created_at: string;
    updated_at: string;
    status: ValidationStatus;
    validated_by: number | null;
    validated_at: string | null;
    validation_note: string | null;
    validator: JournalValidator | null;
    // Kolom lama, dipertahankan agar kode yang masih membaca
    // `status_validasi` tidak error.
    status_validasi?: string | null;
    guru: JournalGuru | null;
    kelas: JournalKelas | null;
    mataPelajaran: JournalMapel | null;
    absensi: JournalAbsensi | null;
}

export type StudentStatus = 'hadir' | 'izin' | 'sakit' | 'alpha';

/**
 * Kehadiran per siswa per nama.
 *
 * Catatan: belum ada tabel siswa di database, jadi backend
 * saat ini mengirim array kosong. Tipe ini disiapkan agar
 * tinggal diisi saat modul data siswa tersedia.
 */
export interface StudentAttendance {
    id: number;
    nama: string;
    nis: string;
    status: StudentStatus;
}
