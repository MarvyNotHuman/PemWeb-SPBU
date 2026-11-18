<?php
interface Pengisian {
    // Mengembalikan total harga, atau 0 jika pengisian gagal.
    public function prosesPengisian(BahanBakar $bbm, float $liter, float $literSubsidi): float;
}
