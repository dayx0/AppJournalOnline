<?php

namespace App\Services;

use App\Models\Journal;
use App\Models\JournalHistory;

/**
 * Pencatat jejak audit validasi jurnal.
 */
final class JournalHistoryService
{
    public static function log(
        Journal $journal,
        ?int $actorId,
        string $aksi,
        ?string $dari = null,
        ?string $ke = null,
        ?string $catatan = null,
    ): void {
        JournalHistory::create([
            'journal_id' => $journal->id,
            'actor_id' => $actorId,
            'aksi' => $aksi,
            'dari_status' => $dari,
            'ke_status' => $ke,
            'catatan' => $catatan,
        ]);
    }

    public static function logDibuat(Journal $journal, int $guruId): void
    {
        self::log($journal, $guruId, JournalHistory::AKSI_DIBUAT, null, 'pending');
    }

    public static function logKeputusan(Journal $journal, int $mpkId, string $dari): void
    {
        $aksi = $journal->status === JournalHistory::AKSI_REVISI
            ? JournalHistory::AKSI_REVISI
            : JournalHistory::AKSI_DIVALIDASI;

        self::log($journal, $mpkId, $aksi, $dari, $journal->status, $journal->validation_note);
    }

    public static function logResetPending(Journal $journal, int $guruId, string $dari): void
    {
        self::log($journal, $guruId, JournalHistory::AKSI_RESET_PENDING, $dari, 'pending');
    }
}
