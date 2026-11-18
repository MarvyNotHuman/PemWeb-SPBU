<?php
// Minggu 5: session, cookie, autentikasi, pembatasan hak akses
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

function user(): ?array { return $_SESSION['user'] ?? null; }
function wajib_login(): void { if (!user()) redirect('login.php'); }
function wajib_admin(): void {
    wajib_login();
    if (user()['role'] !== 'admin') { http_response_code(403); die('Akses ditolak: khusus admin.'); }
}
