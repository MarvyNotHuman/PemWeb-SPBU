<?php
// Atribut bersifat readonly (immutable), setara "private final" di versi Java.
class BahanBakar {
    public function __construct(
        public readonly int $id,
        public readonly string $nama,
        public readonly float $harga,
        public readonly int $angkaMutu,          // RON (bensin) / CN (diesel)
        public readonly bool $diesel,
        public readonly bool $subsidi,
        public readonly float $hargaNonSubsidi,  // harga keekonomian untuk liter di atas kuota
        public readonly bool $kelebihanDitolak   // true: lebih kuota ditolak (Biosolar)
    ) {}

    public static function fromRow(array $r): self {
        return new self((int)$r['id'], $r['nama'], (float)$r['harga'], (int)$r['angka_mutu'],
            (bool)$r['diesel'], (bool)$r['subsidi'], (float)$r['harga_non_subsidi'], (bool)$r['kelebihan_ditolak']);
    }

    public function labelMutu(): string { return ($this->diesel ? 'CN ' : 'RON ') . $this->angkaMutu; }

    // PHP tidak punya overloading; parameter opsional menggantikan dua versi hitungHarga() di Java.
    public function hitungHarga(float $literSubsidi, float $literNon = 0): float {
        $hargaNon = $this->subsidi ? $this->hargaNonSubsidi : $this->harga;
        return $literSubsidi * $this->harga + $literNon * $hargaNon;
    }
}
