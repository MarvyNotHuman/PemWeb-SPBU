<?php
interface Pembayaran {
    public function bayar(float $jumlah, string $metode): string;
}
