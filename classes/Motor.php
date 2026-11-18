<?php
class Motor extends Kendaraan implements Pengisian, Pembayaran {
    public const BATAS_CC_BBM_SUBSIDI = 250;

    public function __construct(int $id, string $plat, string $pemilik, float $tangki, float $isi, private int $cc) {
        parent::__construct($id, $plat, $pemilik, $tangki, $isi);
    }

    private function melanggarCc(BahanBakar $b): bool {
        return $b->subsidi && !$b->diesel && $this->cc > self::BATAS_CC_BBM_SUBSIDI;
    }
    public function bolehMemakai(BahanBakar $bbm): bool { return !$bbm->diesel && !$this->melanggarCc($bbm); }
    public function alasanPenolakan(BahanBakar $bbm): string {
        if ($bbm->diesel) return "Motor tidak dapat memakai BBM diesel ({$bbm->nama}).";
        if ($this->melanggarCc($bbm)) return "Motor di atas " . self::BATAS_CC_BBM_SUBSIDI . " cc tidak dapat memakai {$bbm->nama} (BBM subsidi).";
        return parent::alasanPenolakan($bbm);
    }

    public function prosesPengisian(BahanBakar $bbm, float $liter, float $literSubsidi): float {
        if ($this->cekPengisian($bbm, $liter) !== null) return 0;
        $this->tambahIsi($liter);
        return $bbm->hitungHarga($literSubsidi, $liter - $literSubsidi);
    }
    public function bayar(float $jumlah, string $metode): string {
        if ($jumlah <= 0) throw new InvalidArgumentException('Pembayaran tidak valid.');
        return sprintf('Pembayaran motor %s sebesar %s (%s) diterima.', $this->getPlat(), rupiah($jumlah), $metode);
    }
}
