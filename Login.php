<?php
require_once __DIR__ . '/config.php';
iniciar_sessao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Login.html');
    exit();
}

$email = trim((string) ($_POST['email'] ?? ''));
$senha = (string) ($_POST['senha'] ?? '');

$stmt = db()->prepare('SELECT id, nome, senha, deve_trocar_senha FROM professor WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$professor = $stmt->get_result()->fetch_assoc();
$stmt->close();

$ok = false;
$trocar = false;

if ($professor) {
    $hash = $professor['senha'];
    if (password_get_info($hash)['algo'] !== null) {
        $ok = password_verify($senha, $hash);
    } else {
        // Senha antiga gravada sem criptografia: aceita uma única vez e obriga a troca.
        $ok = hash_equals($hash, $senha);
        $trocar = true;
    }
    $trocar = $trocar || (int) $professor['deve_trocar_senha'] === 1 || $senha === SENHA_PADRAO;
}

if (!$ok) {
    // Mesma mensagem para e-mail e senha errados, para não revelar quais e-mails existem.
    echo "<script>alert('E-mail ou senha incorretos.'); window.location.href = 'Login.html#popup1';</script>";
    exit();
}

session_regenerate_id(true);
$_SESSION['id'] = (int) $professor['id'];
$_SESSION['nome'] = $professor['nome'];
$_SESSION['trocar_senha'] = $trocar;

header('Location: ' . ($trocar ? 'MudarSenha.php' : 'AgendaSemanal.php'));
exit();
