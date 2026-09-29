<?php
require __DIR__ . "/config/db.php";
$db = \Config\Database::getConnection();
// O novo hash para 'admin123' gerado no PHP.
$hash = '$2y$12$B7q8GTxvNEOgooRhlS0sC.N4GdO/ZzkxMi0AHGLFrnTLiztaa/L6.';
$db->prepare("UPDATE users SET password_hash = ? WHERE email = 'admin@ecardeneta.com'")->execute([$hash]);
echo "Password fixed ind DB!\n";
