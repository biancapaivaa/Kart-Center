<?php require_once __DIR__.'/../config.php'; require_admin();
$pageTitle='Gerenciar baterias';
$pdo=db();
$errors=[];
$success='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'criar') {
        $nome = trim((string)($_POST['nome'] ?? ''));
        $data = trim((string)($_POST['data'] ?? ''));
        $horario = trim((string)($_POST['horario'] ?? ''));
        $duracao = (int)($_POST['duracao'] ?? 0);
        $preco = (float)($_POST['preco'] ?? 0);
        $totalVagas = (int)($_POST['total_vagas'] ?? 0);
        $status = $_POST['status'] ?? 'disponivel';

        if ($nome === '') $errors[] = 'Informe o nome da bateria.';
        if ($data === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) $errors[] = 'Informe uma data válida.';
        if ($horario === '' || !preg_match('/^\d{2}:\d{2}$/', $horario)) $errors[] = 'Informe um horário válido.';
        if ($duracao <= 0) $errors[] = 'A duração deve ser maior que zero.';
        if ($preco <= 0) $errors[] = 'O preço deve ser maior que zero.';
        if ($totalVagas <= 0) $errors[] = 'A quantidade total de vagas deve ser maior que zero.';
        if (!in_array($status, ['disponivel','lotada','cancelada','encerrada'], true)) $errors[] = 'Status inválido.';

        if ($data && $horario) {
            $inicio = strtotime($data . ' ' . $horario);
            if ($inicio === false || $inicio <= time()) {
                $errors[] = 'A data e o horário devem ser futuras.';
            }
        }

        if (!$errors) {
            $stmt = $pdo->prepare('INSERT INTO baterias (nome, data, horario, duracao, preco, total_vagas, vagas_disponiveis, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$nome, $data, $horario . ':00', $duracao, $preco, $totalVagas, $totalVagas, $status]);
            $success = 'Bateria cadastrada com sucesso.';
            $nome = $data = $horario = '';
            $duracao = $preco = $totalVagas = 0;
            $status = 'disponivel';
        }
    }

    if ($action === 'editar') {
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim((string)($_POST['nome'] ?? ''));
        $data = trim((string)($_POST['data'] ?? ''));
        $horario = trim((string)($_POST['horario'] ?? ''));
        $duracao = (int)($_POST['duracao'] ?? 0);
        $preco = (float)($_POST['preco'] ?? 0);
        $totalVagas = (int)($_POST['total_vagas'] ?? 0);
        $status = $_POST['status'] ?? 'disponivel';

        $sel = $pdo->prepare('SELECT * FROM baterias WHERE id = ?');
        $sel->execute([$id]);
        $battery = $sel->fetch();
        if (!$battery) {
            $errors[] = 'Bateria não encontrada.';
        } else {
            $reservas = (int)$pdo->prepare('SELECT COALESCE(SUM(quantidade_participantes),0) FROM reservas WHERE bateria_id = ?')->execute([$id]);
            $reservas = (int)$pdo->query('SELECT COALESCE(SUM(quantidade_participantes),0) FROM reservas WHERE bateria_id = ' . (int)$id)->fetchColumn();

            if ($nome === '') $errors[] = 'Informe o nome da bateria.';
            if ($data === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) $errors[] = 'Informe uma data válida.';
            if ($horario === '' || !preg_match('/^\d{2}:\d{2}$/', $horario)) $errors[] = 'Informe um horário válido.';
            if ($duracao <= 0) $errors[] = 'A duração deve ser maior que zero.';
            if ($preco <= 0) $errors[] = 'O preço deve ser maior que zero.';
            if ($totalVagas <= 0) $errors[] = 'A quantidade total de vagas deve ser maior que zero.';
            if (!in_array($status, ['disponivel','lotada','cancelada','encerrada'], true)) $errors[] = 'Status inválido.';
            if ($totalVagas < $reservas) {
                $errors[] = 'A capacidade não pode ser inferior ao total de participantes já reservados.';
            }
            if ($data && $horario) {
                $inicio = strtotime($data . ' ' . $horario);
                if ($inicio === false) $errors[] = 'Data e horário inválidos.';
            }

            if (!$errors) {
                $newDisponiveis = max(0, $totalVagas - $reservas);
                $stmt = $pdo->prepare('UPDATE baterias SET nome=?, data=?, horario=?, duracao=?, preco=?, total_vagas=?, vagas_disponiveis=?, status=? WHERE id=?');
                $stmt->execute([$nome, $data, $horario . ':00', $duracao, $preco, $totalVagas, $newDisponiveis, $status, $id]);
                if ($newDisponiveis <= 0 && $status === 'disponivel') {
                    $pdo->prepare('UPDATE baterias SET status = ? WHERE id = ?')->execute(['lotada', $id]);
                }
                $success = 'Bateria atualizada com sucesso.';
            }
        }
    }

    if ($action === 'excluir') {
        $id = (int)($_POST['id'] ?? 0);
        $check = $pdo->prepare('SELECT COUNT(*) FROM reservas WHERE bateria_id = ?');
        $check->execute([$id]);
        if ((int)$check->fetchColumn() > 0) {
            $errors[] = 'Esta bateria possui reservas vinculadas e não pode ser excluída diretamente.';
        } else {
            $stmt = $pdo->prepare('DELETE FROM baterias WHERE id = ?');
            $stmt->execute([$id]);
            $success = 'Bateria excluída com sucesso.';
        }
    }
}

$rows = $pdo->query('SELECT * FROM baterias ORDER BY data, horario')->fetchAll();
require __DIR__.'/../includes/header.php';
?>
<div class="page-top"><div><div class="eyebrow">Gerenciamento da pista</div><h1 class="page-title">Gerenciar Baterias</h1></div><a class="btn" href="baterias-nova.php">NOVA BATERIA</a></div>
<?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
<?php foreach ($errors as $error): ?><div class="alert danger"><?= e($error) ?></div><?php endforeach; ?>
<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Data</th>
                <th>Horário</th>
                <th>Duração</th>
                <th>Preço</th>
                <th>Capacidade</th>
                <th>Disponíveis</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $b): $reservas_usadas = (int)$pdo->prepare('SELECT COALESCE(SUM(quantidade_participantes),0) FROM reservas WHERE bateria_id = ?')->execute([$b['id']]); $reservas_usadas = (int)$pdo->query('SELECT COALESCE(SUM(quantidade_participantes),0) FROM reservas WHERE bateria_id = ' . (int)$b['id'])->fetchColumn(); ?>
                <tr>
                    <td><?= e($b['nome']) ?></td>
                    <td><?= date('d/m/Y', strtotime($b['data'])) ?></td>
                    <td><?= substr($b['horario'], 0, 5) ?></td>
                    <td><?= (int)$b['duracao'] ?> min</td>
                    <td>R$ <?= number_format((float)$b['preco'], 2, ',', '.') ?></td>
                    <td><?= (int)$b['total_vagas'] ?></td>
                    <td><?= (int)$b['vagas_disponiveis'] ?></td>
                    <td><span class="badge <?= $b['status'] === 'cancelada' || $b['status'] === 'encerrada' ? 'redbg' : ($b['status'] === 'lotada' ? 'dark' : 'green') ?>"><?= e(battery_status_label($b['status'])) ?></span></td>
                    <td>
                        <div class="actions">
                            <a class="btn small dark" href="baterias-editar.php?id=<?= (int)$b['id'] ?>">Editar</a>
                            <form class="inline-form" method="post" action="baterias.php" data-confirm="Tem certeza que deseja excluir a bateria <?= e($b['nome']) ?>?">
                                <input type="hidden" name="csrf" value="<?= csrf() ?>">
                                <input type="hidden" name="action" value="excluir">
                                <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                <button class="btn small" type="submit">Excluir</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__.'/../includes/footer.php'; ?>
