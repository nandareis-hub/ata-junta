<?php 
require_once 'config/conexao.php';
include_once 'includes/header.php'; 

// Buscar a ata mais recente
$stmt = $pdo->query("SELECT * FROM atas ORDER BY data_reuniao DESC LIMIT 1");
$ultima_ata = $stmt->fetch();
?>

<div class="card">
    <h2>Bem-vindo ao Portal Autárquico</h2>
    <p>Aceda às atas oficiais, deliberações e propostas aprovadas pela Executivo da União das Freguesias de Almada, Cova da Piedade, Pragal e Cacilhas.</p>
</div>

<?php if ($ultima_ata): ?>
<div class="card">
    <h2>Última Ata Publicada</h2>
    <p><strong>Ata N.º:</strong> <?php echo htmlspecialchars($ultima_ata['numero_ata']); ?> (Mandato <?php echo htmlspecialchars($ultima_ata['mandato']); ?>)</p>
    <p><strong>Reunião:</strong> <?php echo htmlspecialchars($ultima_ata['numero_reuniao']); ?> Reunião <?php echo htmlspecialchars($ultima_ata['tipo_reuniao']); ?></p>
    <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($ultima_ata['data_reuniao'])); ?></p>
    <br>
    <a href="ver_ata.php?id=<?php echo $ultima_ata['id']; ?>" class="btn">Consultar Detalhes da Ata</a>
</div>
<?php endif; ?>

<?php include_once 'includes/footer.php'; ?>