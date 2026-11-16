<?php require_once __DIR__.'/../config.php'; require_admin();
$id = (int)($_GET['id'] ?? 0);
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM baterias WHERE id = ?');
$stmt->execute([$id]);
$battery = $stmt->fetch();
if (!$battery) { flash('Bateria não encontrada.', 'danger'); redirect('baterias.php'); }
$errors=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $nome = trim((string)($_POST['nome'] ?? ''));
    $data = trim((string)($_POST['data'] ?? ''));
    $horario = trim((string)($_POST['horario'] ?? ''));
    $duracao = (int)($_POST['duracao'] ?? 0);
    $preco = (float)($_POST['preco'] ?? 0);
    $totalVagas = (int)($_POST['total_vagas'] ?? 0);
    $status = $_POST['status'] ?? 'disponivel';
    $reservasUsadas = (int)$pdo->prepare('SELECT COALESCE(SUM(quantidade_participantes),0) FROM reservas WHERE bateria_id = ?')->execute([$id]);
    $reservasUsadas = (int)$pdo->query('SELECT COALESCE(SUM(quantidade_participantes),0) FROM reservas WHERE bateria_id = ' . (int)$id)->fetchColumn();

    if ($nome === '') $errors[] = 'Informe o nome da bateria.';
    if ($data === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) $errors[] = 'Informe uma data válida.';
    if ($horario === '' || !preg_match('/^\d{2}:\d{2}$/', $horario)) $errors[] = 'Informe um horário válido.';
    if ($duracao <= 0) $errors[] = 'A duração deve ser maior que zero.';
    if ($preco <= 0) $errors[] = 'O preço deve ser maior que zero.';
    if ($totalVagas <= 0) $errors[] = 'A quantidade total de vagas deve ser maior que zero.';
    if (!in_array($status, ['disponivel','lotada','cancelada','encerrada'], true)) $errors[] = 'Status inválido.';
    if ($totalVagas < $reservasUsadas) $errors[] = 'A capacidade não pode ser inferior ao total de participantes já reservados.';
    if ($data && $horario) {
        $inicio = strtotime($data . ' ' . $horario);
        if ($inicio === false) $errors[] = 'Data e horário inválidos.';
    }

    if (!$errors) {
        $vagas = max(0, $totalVagas - $reservasUsadas);
        if ($status === 'disponivel' && $vagas <= 0) $status = 'lotada';
        $stmt = $pdo->prepare('UPDATE baterias SET nome=?, data=?, horario=?, duracao=?, preco=?, total_vagas=?, vagas_disponiveis=?, status=? WHERE id=?');
        $stmt->execute([$nome, $data, $horario . ':00', $duracao, $preco, $totalVagas, $vagas, $status, $id]);
        flash('Bateria atualizada com sucesso.', 'success');
        redirect('baterias.php');
    }
}

$battery['nome'] = $battery['nome'] ?? 'Bateria Kart Center';
require __DIR__.'/../includes/header.php';
?>
<div class="form-wrap"><form class="form-card" method="post"><h1>Editar bateria</h1><p>Atualize os dados da bateria e seu status.</p><?php foreach($errors as $er): ?><div class="alert danger"><?= e($er) ?></div><?php endforeach; ?><input type="hidden" name="csrf" value="<?= csrf() ?>"><div class="field"><label>Nome da bateria</label><input name="nome" value="<?= e($battery['nome']) ?>" required></div><div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr))"><div class="field"><label>Data da corrida</label><input type="date" name="data" value="<?= e($battery['data']) ?>" required></div><div class="field"><label>Horário de início</label><input type="time" name="horario" value="<?= substr($battery['horario'],0,5) ?>" required></div></div><div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr))"><div class="field"><label>Duração (minutos)</label><input type="number" min="1" name="duracao" value="<?= (int)$battery['duracao'] ?>" required></div><div class="field"><label>Preço por pessoa</label><input type="number" min="0.01" step="0.01" name="preco" value="<?= number_format((float)$battery['preco'],2,'.','') ?>" required></div></div><div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr))"><div class="field"><label>Quantidade total de vagas</label><input type="number" min="1" name="total_vagas" value="<?= (int)$battery['total_vagas'] ?>" required></div><div class="field"><label>Status</label><select name="status"><option value="disponivel" <?= $battery['status']==='disponivel'?'selected':'' ?>>Disponível</option><option value="lotada" <?= $battery['status']==='lotada'?'selected':'' ?>>Lotada</option><option value="cancelada" <?= $battery['status']==='cancelada'?'selected':'' ?>>Cancelada</option><option value="encerrada" <?= $battery['status']==='encerrada'?'selected':'' ?>>Encerrada</option></select></div></div><button class="btn">SALVAR ALTERAÇÕES</button><div class="form-foot"><a href="baterias.php">Voltar para baterias</a></div></form></div><?php require __DIR__.'/../includes/footer.php'; ?>
