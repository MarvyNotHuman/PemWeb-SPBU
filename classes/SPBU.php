<?php
// Layanan SPBU: aturan bisnis kuota subsidi. Kuota harian dihitung dari tabel transaksi (bukan memori).
class SPBU {
    public const NAMA = 'SPBU UNSOED';
    public const KODE = 'SPBU-001';
    public const LOKASI = 'Purwokerto, Jawa Tengah';

    public function __construct(private mysqli $db) {}

    public function subsidiTerpakai(Kendaraan $k, BahanBakar $b): float {
        $r = query($this->db, 'SELECT COALESCE(SUM(liter_subsidi),0) t FROM transaksi
                               WHERE kendaraan_id=? AND bbm_id=? AND DATE(tanggal)=CURDATE()', 'ii', [$k->getId(), $b->id]);
        return (float)$r[0]['t'];
    }

    // Pemakaian subsidi hari ini untuk banyak kendaraan sekaligus: [kendaraan_id][bbm_id] => liter
    public function terpakaiHariIni(array $ids): array {
        if (!$ids) return [];
        $in = implode(',', array_fill(0, count($ids), '?'));
        $hasil = [];
        foreach (query($this->db, "SELECT kendaraan_id, bbm_id, SUM(liter_subsidi) t FROM transaksi
            WHERE DATE(tanggal)=CURDATE() AND kendaraan_id IN ($in) GROUP BY kendaraan_id, bbm_id",
            str_repeat('i', count($ids)), array_values($ids)) as $r) {
            $hasil[(int)$r['kendaraan_id']][(int)$r['bbm_id']] = (float)$r['t'];
        }
        return $hasil;
    }

    // Validasi isi tangki saat ini terhadap kapasitas maksimum kendaraan; null = valid
    public static function cekIsiTangki(float $isi, float $kapasitas): ?string {
        if (!is_finite($isi) || $isi < 0) return 'Isi tangki saat ini tidak boleh negatif.';
        if ($isi > $kapasitas + 1e-6) return sprintf('Isi tangki saat ini (%.2f L) melebihi kapasitas tangki kendaraan (%.2f L).', $isi, $kapasitas);
        return null;
    }

    public function sisaSubsidi(Kendaraan $k, BahanBakar $b): float {
        return max(0, $k->batasSubsidiPerHari($b) - $this->subsidiTerpakai($k, $b));
    }

    // Urutan cek mengikuti proyek PBO: kelayakan -> kuota subsidi -> kapasitas tangki -> hitung harga.
    public function rincian(Kendaraan $k, BahanBakar $b, float $liter): array {
        if ($k->getSisaTangki() <= 1e-6) {
            return ['ok' => false, 'pesan' => sprintf('Tangki penuh (%.2f dari %.2f liter). Pengisian tidak dapat dilakukan.', $k->getIsiTangki(), $k->getKapasitasTangki())];
        }
        if ($liter <= 0 || !is_finite($liter)) return ['ok' => false, 'pesan' => 'Jumlah liter harus lebih dari 0.'];
        if (!$k->bolehMemakai($b)) return ['ok' => false, 'pesan' => $k->alasanPenolakan($b)];

        $sisa = $b->subsidi ? $this->sisaSubsidi($k, $b) : INF;
        if ($b->subsidi && $b->kelebihanDitolak && $liter > $sisa + 1e-6) {
            return ['ok' => false, 'pesan' => $sisa <= 1e-6
                ? "Kuota subsidi {$b->nama} hari ini sudah habis. Silakan isi kembali besok."
                : sprintf('Melebihi sisa kuota subsidi %s hari ini (sisa %.2f liter).', $b->nama, $sisa)];
        }
        if ($liter > $k->getSisaTangki() + 1e-6) {
            return ['ok' => false, 'pesan' => sprintf('Sisa kapasitas tangki hanya %.2f liter.', $k->getSisaTangki())];
        }
        $lSub = $b->subsidi ? min($liter, $sisa) : 0.0;
        $lNon = $liter - $lSub < 1e-6 ? 0.0 : $liter - $lSub;
        return [
            'ok' => true, 'liter_subsidi' => round($lSub, 2), 'liter_non_subsidi' => round($lNon, 2),
            'total' => $b->hitungHarga($lSub, $lNon),
            'sisa_subsidi' => is_infinite($sisa) ? null : round($sisa, 2),
            'sisa_tangki' => round($k->getSisaTangki(), 2), 'isi_setelah' => round($k->getIsiTangki() + $liter, 2),
            'kapasitas' => $k->getKapasitasTangki(),
        ];
    }
}
