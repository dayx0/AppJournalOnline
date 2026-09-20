/**
 * Format "2025-09-08" menjadi "08 Sep 2025".
 * Mengembalikan nilai asli bila tidak bisa diparse.
 */
export function formatTanggal(value: string): string {
    const date = parseTanggal(value);

    if (!date) {
        return value;
    }

    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

/**
 * Parse tanggal "YYYY-MM-DD" sebagai waktu lokal
 * (menghindari geser hari akibat zona waktu UTC).
 */
export function parseTanggal(value: string): Date | null {
    const date = new Date(`${value}T00:00:00`);

    return Number.isNaN(date.getTime()) ? null : date;
}

/**
 * Format ISO datetime menjadi label relatif ala notifikasi guru:
 * "baru saja", "N mnt", "N jam", "Kemarin", "N hari", atau tanggal.
 */
export function formatRelatif(iso: string): string {
    const time = new Date(iso).getTime();

    if (Number.isNaN(time)) {
        return '';
    }

    const menit = Math.floor((Date.now() - time) / 60000);

    if (menit < 1) {
        return 'baru saja';
    }

    if (menit < 60) {
        return `${menit} mnt`;
    }

    const jam = Math.floor(menit / 60);

    if (jam < 24) {
        return `${jam} jam`;
    }

    const hari = Math.floor(jam / 24);

    if (hari === 1) {
        return 'Kemarin';
    }

    if (hari < 7) {
        return `${hari} hari`;
    }

    return formatTanggal(iso.slice(0, 10));
}
