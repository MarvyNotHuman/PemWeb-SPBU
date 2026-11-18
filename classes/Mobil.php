<?php
class Mobil extends Kendaraan implements Pengisian, Pembayaran {
    public const BATAS_SOLAR_RODA_4 = 50;
    public const BATAS_SOLAR_RODA_6_KE_ATAS = 200;
    public const BATAS_PERTALITE = 50;
    public const BATAS_CC_BBM_SUBSIDI = 1400;

    public function __construct(int $id, string $plat, string $pemilik, float $tangki, float $isi,
                                private int $cc, private int $roda, private int $penumpang) {
        parent::__construct($id, $plat, $pemilik, $tangki, $isi);   // super chaining
    }

    public static function rodaValid(int $roda): bool { return $roda === 4 || $roda >= 6; }

    private function melanggarCc(BahanBakar $b): bool {
        return $b->subsidi && !$b->diesel && $this->cc > self::BATAS_CC_BBM_SUBSIDI;
    }
    public function bolehMemakai(BahanBakar $bbm): bool { return parent::bolehMemakai($bbm) && !$this->melanggarCc($bbm); }
    public function alasanPenolakan(BahanBakar $bbm): string {
        return $this->melanggarCc($bbm)
            ? "Mobil di atas " . self::BATAS_CC_BBM_SUBSIDI . " cc tidak dapat memakai {$bbm->nama} (BBM subsidi)."
            : parent::alasanPenolakan($bbm);
    }
    public function batasSubsidiPerHari(BahanBakar $bbm): float {
        if (!$bbm->subsidi) return parent::batasSubsidiPerHari($bbm);
        if (!$bbm->diesel) return self::BATAS_PERTALITE;
        return $this->roda >= 6 ? self::BATAS_SOLAR_RODA_6_KE_ATAS : self::BATAS_SOLAR_RODA_4;
    }

    public function prosesPengisian(BahanBakar $bbm, float $liter, float $literSubsidi): float {
        if ($this->cekPengisian($bbm, $liter) !== null) return 0;
        $this->tambahIsi($liter);                                  // ubah state
        return $bbm->hitungHarga($literSubsidi, $liter - $literSubsidi);
    }
    public function bayar(float $jumlah, string $metode): string {
        if ($jumlah <= 0) throw new InvalidArgumentException('Pembayaran tidak valid.');
        return sprintf('Pembayaran mobil %s sebesar %s (%s) diterima.', $this->getPlat(), rupiah($jumlah), $metode);
    }
}
