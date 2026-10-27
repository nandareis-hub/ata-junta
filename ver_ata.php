<?php 
require_once 'config/conexao.php';
include_once 'includes/header.php'; 

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: atas.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM atas WHERE id = ?");
$stmt->execute([$id]);
$ata = $stmt->fetch();

if (!$ata) {
    echo "<div class='card'><h2>Ata não encontrada.</h2></div>";
    include_once 'includes/footer.php';
    exit;
}

// Buscar pontos de ordem
$stmt_pontos = $pdo->prepare("SELECT * FROM pontos_ordem WHERE ata_id = ? ORDER BY numero_ponto ASC");
$stmt_pontos->execute([$id]);
$pontos = $stmt_pontos->fetchAll();
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2 style="margin: 0;">ATA NÚMERO <?php echo htmlspecialchars($ata['numero_ata']); ?></h2>
        <!-- Botão para Editar esta Ata -->
        <a href="editar_ata.php?id=<?php echo $ata['id']; ?>" class="btn" style="background-color: #d97706; color: #fff; text-decoration: none; padding: 8px 15px; border-radius: 4px; font-weight: bold;">✏️ Editar Ata</a>
    </div>

    <p><strong>Mandato:</strong> <?php echo htmlspecialchars($ata['mandato']); ?></p>
    <hr style="margin: 15px 0; border: 0; border-top: 1px solid #ccc;">

    <!-- Exibição limpa da Sessão unindo o número por extenso e o tipo de reunião com " | " -->
    <p><strong>Sessão:</strong> <?php echo htmlspecialchars($ata['numero_reuniao']) . ' | ' . htmlspecialchars($ata['tipo_reuniao']); ?></p>
    
    <p><strong>Data e Hora:</strong> <?php echo date('d/m/Y', strtotime($ata['data_reuniao'])); ?> às <?php echo date('H:i', strtotime($ata['hora_inicio'])); ?>h</p>
    <p><strong>Local:</strong> <?php echo htmlspecialchars($ata['local_reuniao']); ?></p>
    <p><strong>Presidência:</strong> <?php echo htmlspecialchars($ata['presidente']); ?></p>
    <p><strong>Secretária:</strong> <?php echo htmlspecialchars($ata['secretaria']); ?></p>
    <?php if(!empty($ata['tesoureiro'])): ?>
        <p><strong>Tesoureiro:</strong> <?php echo htmlspecialchars($ata['tesoureiro']); ?></p>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Presenças e Convocatória</h3>
    <p><strong>Vogais / Presentes:</strong> <?php echo htmlspecialchars($ata['participantes_presenciais']); ?></p>
    <p><strong>Participação por Videoconferência:</strong> <?php echo htmlspecialchars($ata['participantes_videoconf']); ?></p>
    <p><strong>Faltas Justificadas:</strong> <?php echo htmlspecialchars($ata['ausentes_justificados']); ?></p>
</div>

<div class="card">
    <h3>Ordem do Dia / Deliberações</h3>
    <?php foreach ($pontos as $p): ?>
        <div style="background: #f8f9fa; padding: 15px; border-left: 3px solid var(--accent); margin-top: 15px;">
            <h4>Ponto <?php echo $p['numero_ponto']; ?>. <?php echo htmlspecialchars($p['proposta_numero']); ?> <?php echo !empty($p['codigo_procedimento']) ? '('.htmlspecialchars($p['codigo_procedimento']).')' : ''; ?></h4>
            <p><strong>Assunto:</strong> <?php echo htmlspecialchars($p['titulo']); ?></p>
            <p><strong>Subscrita por:</strong> <?php echo htmlspecialchars($p['subscrito_por']); ?></p>
            
            <?php if(!empty($p['valor_base'])): ?>
                <!-- Formatação rigorosa do Valor Base com separador de milhar por ponto e decimal por vírgula -->
                <p><strong>Valor Base:</strong> <?php echo number_format($p['valor_base'], 2, ',', '.'); ?> € + IVA</p>
            <?php endif; ?>

            <p style="margin-top: 10px;"><?php echo nl2br(htmlspecialchars($p['detalhes'])); ?></p>
            <p style="margin-top: 10px;"><strong>Resultado:</strong> <span class="badge"><?php echo htmlspecialchars($p['resultado_votacao']); ?></span></p>
        </div>
    <?php endforeach; ?>
</div>

<div style="margin-top: 20px;">
    <a href="atas.php" class="btn" style="background: #6c757d; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 4px;">Voltar à Lista de Atas</a>
</div>

<?php include_once 'includes/footer.php'; ?>