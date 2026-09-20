<?php

namespace App\Services;

use App\Models\Journal;
use App\Models\User;
use App\Models\UserNotification;

/**
 * Notifikasi derived: isi (judul/sub/url) dihitung di frontend dari
 * tabel jurnal, service ini hanya mengelola status baca per user
 * (push saat ada kejadian, hapus saat basi, tandai baca).
 */
final class JournalNotificationService
{
    public static function keyFor(Journal $journal, string $kind): string
    {
        return "jurnal.{$journal->id}.{$kind}";
    }

    public static function push(int $userId, Journal $journal, string $kind, string $title, ?string $body, string $url): void
    {
        UserNotification::updateOrCreate(
            ['user_id' => $userId, 'key' => self::keyFor($journal, $kind)],
            [
                'kind' => $kind,
                'title' => $title,
                'body' => $body,
                'url' => $url,
                'journal_id' => $journal->id,
                // Kejadian baru = belum dibaca lagi
                'read_at' => null,
            ]
        );
    }

    /** @return list<int> */
    public static function mpkUserIds(): array
    {
        return User::where('role', 'mpk')->pluck('id')->all();
    }

    /**
     * Jurnal baru / kembali pending -> semua MPK dapat antrean unread.
     */
    public static function notifyMpkNewJournal(Journal $journal): void
    {
        $journal->loadMissing(['guru', 'kelas', 'mataPelajaran']);
        $guru = $journal->guru?->name ?? '-';
        $kelas = $journal->kelas?->nama_kelas ?? '-';
        $mapel = $journal->mataPelajaran?->nama_mapel ?? '-';

        foreach (self::mpkUserIds() as $mpkId) {
            self::push(
                $mpkId,
                $journal,
                'pending',
                'Jurnal baru menunggu validasi',
                "{$guru} • {$kelas} • {$mapel}",
                "/mpk/jurnal/{$journal->id}"
            );
        }
    }

    /**
     * Keputusan MPK (divalidasi / revisi) -> guru pemilik dapat unread.
     * Antrean pending jurnal ini untuk semua MPK ikut dibersihkan
     * karena sudah tidak actionable lagi.
     */
    public static function notifyGuruDecision(Journal $journal): void
    {
        $journal->loadMissing(['guru', 'kelas', 'mataPelajaran', 'validator']);
        $kind = $journal->status === 'revisi' ? 'revisi' : 'divalidasi';
        $kelas = $journal->kelas?->nama_kelas ?? '-';
        $mapel = $journal->mataPelajaran?->nama_mapel ?? '-';
        $oleh = $journal->validator?->name;

        $title = $kind === 'revisi' ? 'Jurnal perlu revisi' : 'Jurnal telah divalidasi';
        $body = $journal->validation_note
            ? $journal->validation_note.($oleh ? " • {$oleh}" : '')
            : "{$kelas} • {$mapel}".($oleh ? " • {$oleh}" : '');

        self::push($journal->guru_id, $journal, $kind, $title, $body, "/jurnal/{$journal->id}");
        self::clearPendingForJournal($journal->id);
    }

    /**
     * Jurnal yang direset ke pending (guru edit) -> keputusan lama basi,
     * hapus agar tidak jadi unread hantu, lalu antrekan ulang ke MPK.
     */
    public static function notifyResetToPending(Journal $journal): void
    {
        UserNotification::where('journal_id', $journal->id)
            ->whereIn('kind', ['divalidasi', 'revisi'])
            ->delete();

        self::notifyMpkNewJournal($journal);
    }

    /**
     * Hapus antrean pending satu jurnal (sudah diputuskan).
     */
    public static function clearPendingForJournal(int $journalId): void
    {
        UserNotification::where('journal_id', $journalId)
            ->where('kind', 'pending')
            ->delete();
    }

    /**
     * Hapus semua notifikasi satu jurnal (dipakai saat jurnal dihapus).
     */
    public static function clearAllForJournal(int $journalId): void
    {
        UserNotification::where('journal_id', $journalId)->delete();
    }

    /**
     * Tandai semua notifikasi satu jurnal milik user sebagai dibaca.
     * Dipanggil saat user membuka halaman detail jurnal.
     */
    public static function markJournalRead(int $userId, int $journalId): int
    {
        return UserNotification::forUser($userId)
            ->where('journal_id', $journalId)
            ->unread()
            ->update(['read_at' => now()]);
    }

    /** @return list<string> */
    public static function unreadKeys(int $userId, int $limit = 200): array
    {
        return UserNotification::forUser($userId)
            ->unread()
            ->latest()
            ->limit($limit)
            ->pluck('key')
            ->all();
    }
}
