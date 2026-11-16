<?php
// Ajuste as credenciais conforme seu MySQL/XAMPP.
const DB_HOST = '127.0.0.1';
const DB_NAME = 'kart_center';
const DB_USER = 'root';
const DB_PASS = '';
const APP_NAME = 'Kart Center';

date_default_timezone_set('America/Sao_Paulo');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly'=>true, 'samesite'=>'Lax', 'secure'=>!empty($_SERVER['HTTPS'])]);
    session_start();
}

function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES=>false
        ]);
    }
    return $pdo;
}
function ensure_baterias_schema(): void {
    $pdo = db();
    $columns = $pdo->query("SHOW COLUMNS FROM baterias")->fetchAll();
    $fields = array_column($columns, 'Field');
    if (!in_array('nome', $fields, true)) {
        $pdo->exec("ALTER TABLE baterias ADD COLUMN nome VARCHAR(150) NOT NULL DEFAULT 'Bateria Kart Center' AFTER id");
    }
    $pdo->exec("UPDATE baterias SET nome = COALESCE(NULLIF(TRIM(nome), ''), 'Bateria Kart Center') WHERE nome IS NULL OR TRIM(nome) = ''");
    $statusColumn = $pdo->query("SHOW COLUMNS FROM baterias LIKE 'status'")->fetch();
    if ($statusColumn && stripos((string)$statusColumn['Type'], 'encerrada') === false) {
        $pdo->exec("ALTER TABLE baterias MODIFY status ENUM('disponivel','lotada','cancelada','encerrada') NOT NULL DEFAULT 'disponivel'");
    }
}
ensure_baterias_schema();
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function logged(): bool { return !empty($_SESSION['user']); }
function user(): ?array { return $_SESSION['user'] ?? null; }
function is_admin(): bool { return logged() && user()['tipo'] === 'administrador'; }
function require_login(): void { if (!logged()) { flash('Faça login para acessar essa página.', 'warning'); header('Location: login.php'); exit; } }
function require_admin(): void { require_login(); if (!is_admin()) { flash('Acesso restrito ao administrador.', 'danger'); header('Location: index.php'); exit; } }
function require_booking_access(): void { require_login(); if (is_admin()) { flash('Administradores não podem realizar reservas.', 'danger'); header('Location: admin/index.php'); exit; } }
function can_book(): bool { return logged() && !is_admin(); }
function refresh_user(): void { if (logged()) { $s=db()->prepare('SELECT id,nome,email,tipo,criado_em FROM usuarios WHERE id=?'); $s->execute([user()['id']]); $u=$s->fetch(); if ($u) $_SESSION['user']=$u; } }
function flash(string $message, string $type='success'): void { $_SESSION['flash']=['message'=>$message,'type'=>$type]; }
function csrf(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function verify_csrf(): void { if ($_SERVER['REQUEST_METHOD']==='POST' && !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(403); exit('Solicitação inválida. Atualize a página e tente novamente.'); } }
function redirect(string $url): never { header('Location: '.$url); exit; }
function valid_email(string $email): bool { return (bool)filter_var($email, FILTER_VALIDATE_EMAIL); }
function battery_status_label(string $status): string {
    $labels = ['disponivel'=>'Disponível','lotada'=>'Lotada','cancelada'=>'Cancelada','encerrada'=>'Encerrada'];
    return $labels[$status] ?? ucfirst((string)$status);
}
function battery_can_book(string $status): bool {
    return $status === 'disponivel';
}
