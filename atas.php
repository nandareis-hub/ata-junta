<?php 
require_once 'config/conexao.php';
include_once 'includes/header.php'; 

$stmt = $pdo->query("SELECT * FROM atas ORDER BY data_reuniao DESC");
$atas = $stmt->fetchAll();
?>

<div class="card">
    <h2>Arquivo de Atas das Reuniões</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>N.º da Ata</th>
                    <th>Tipo de Reunião</th>
                    <th>Data</th>
                    <th>Presidente</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($atas as $a): ?>
                <tr>
                    <td><strong>Ata N.º <?php echo htmlspecialchars($a['numero_ata']); ?></strong></td>
                    <td><?php echo htmlspecialchars($a['numero_reuniao'] . ' ' . $a['tipo_reuniao']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($a['data_reuniao'])); ?></td>
                    <td><?php echo htmlspecialchars($a['presidente']); ?></td>
                    <td>
                        <a href="ver_ata.php?id=<?php echo $a['id']; ?>" class="btn btn-accent">Ver Completa</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>