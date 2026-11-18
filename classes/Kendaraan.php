<?php
abstract class Kendaraan {
    public function __construct(
        private int $id,
        private string $plat,
        private string $pemilik,
        private float $kapasitasTangki,
        private float $isiTangki = 0
    ) {
        if ($kapasitasTangki <= 0) throw new InvalidArgumentException('Kapasitas tangki harus lebih dari 0.');
        if ($isiTangki < 0 || $isiTangki > $kapasitasTangki) throw new InvalidArgumentException('Isi tangki harus 0 sampai kapasitas tangki.');
    }

    // Pabrik objek: baris database -> Mobil / Motor.
    // $isiTangki = kondisi tangki saat tiba di SPBU (diinput per transaksi, tidak tersimpan di data kendaraan).
    public static function fromRow(array $r, float $isiTangki = 0.0): Kendaraan {
        $a = [(int)$r['id'], $r['plat'], $r['pemilik'], (float)$r['kapasitas_tangki'], $isiTangki];
        return $r['jenis'] === 'Mobil'
            ? new Mobil(...$a, cc: (int)$r['kapasitas_mesin'], roda: (int)$r['jumlah_roda'], penumpang: (int)$r['max_penumpang'])
            : new Motor(...$a, cc: (int)$r['kapasitas_mesin']);
    }

    public function getId(): int { return $this->id; }
    public function getPlat(): string { return $this->plat; }
    public function getPemilik(): string { return $this->pemilik; }
    public function getKapasitasTangki(): float { return $this->kapasitasTangki; }
    public function getIsiTangki(): float { return $this->isiTangki; }
    public function getSisaTangki(): float { return $this->kapasitasTangki - $this->isiTangki; }
    protected function tambahIsi(float $liter): void { $this->isiTangki += $liter; }
    public function jenis(): string { return static::class; }

    // Abstract method: aturan pengisian ditentukan subclass
    abstract public function prosesPengisian(BahanBakar $bbm, float $liter, float $literSubsidi): float;

    // Aturan umum (boleh di-override subclass)
    public function bolehMemakai(BahanBakar $bbm): bool { return true; }
    public function alasanPenolakan(BahanBakar $bbm): string { return 'BBM ini tidak dapat dipakai oleh kendaraan Anda.'; }
    public function batasSubsidiPerHari(BahanBakar $bbm): float { return INF; }

    // Validasi bersama; null = lolos
    protected function cekPengisian(BahanBakar $bbm, float $liter): ?string {
        if ($liter <= 0 || !is_finite($liter)) return 'Jumlah liter tidak valid.';
        if (!$this->bolehMemakai($bbm)) return $this->alasanPenolakan($bbm);
        if ($liter > $this->getSisaTangki() + 1e-6) return sprintf('Sisa kapasitas tangki hanya %.2f liter.', $this->getSisaTangki());
        return null;
    }
}
