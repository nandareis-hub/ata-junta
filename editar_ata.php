<?php 
require_once 'config/conexao.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: atas.php");
    exit;
}

$mensagem = '';
$tipo_alerta = '';

// Processar formulário de submissão da edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fase_registo = $_POST['fase_registo'] ?? 'Agendada';
    $carater_reuniao = $_POST['carater_reuniao'] ?? 'Pública';
    $tipo_reuniao = $_POST['tipo_reuniao'] ?? 'Ordinária';
    $numero_ata = trim($_POST['numero_ata'] ?? '');
    $mandato = trim($_POST['mandato'] ?? '');
    $numero_reuniao = trim($_POST['numero_reuniao'] ?? '');
    $data_reuniao = $_POST['data_reuniao'] ?? '';
    $hora_inicio = $_POST['hora_inicio'] ?? '';
    $hora_fim = !empty($_POST['hora_fim']) ? $_POST['hora_fim'] : null;
    $local_reuniao = trim($_POST['local_reuniao'] ?? '');
    $presidente = trim($_POST['presidente'] ?? '');
    $secretaria = trim($_POST['secretaria'] ?? '');
    $tesoureiro = trim($_POST['tesoureiro'] ?? '');
    
    if ($hora_fim && $hora_inicio && $hora_fim < $hora_inicio) {
        $mensagem = "Erro: A hora de encerramento não pode ser anterior à hora de início!";
        $tipo_alerta = "erro";
    } else {
        $membros_presentes_arr = $_POST['membros_presentes'] ?? [];
        $participantes_presenciais = !empty($membros_presentes_arr) ? implode(', ', $membros_presentes_arr) : null;
        $participantes_videoconf = !empty($_POST['participantes_videoconf']) ? trim($_POST['participantes_videoconf']) : null;
        $ausentes_justificados = !empty($_POST['ausentes_justificados']) ? trim($_POST['ausentes_justificados']) : null;

        $sql = "UPDATE atas SET numero_ata = ?, mandato = ?, tipo_reuniao = ?, numero_reuniao = ?, data_reuniao = ?, hora_inicio = ?, hora_fim = ?, local_reuniao = ?, presidente = ?, secretaria = ?, tesoureiro = ?, participantes_presenciais = ?, participantes_videoconf = ?, ausentes_justificados = ?, carater_reuniao = ?, estado = ? WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$numero_ata, $mandato, $tipo_reuniao, $numero_reuniao, $data_reuniao, $hora_inicio, $hora_fim, $local_reuniao, $presidente, $secretaria, $tesoureiro, $participantes_presenciais, $participantes_videoconf, $ausentes_justificados, $carater_reuniao, $fase_registo, $id])) {
            
            // Atualizar o primeiro ponto de ordem se existir
            if (!empty($_POST['ponto_titulo'])) {
                $valor_base_final = null;
                if (!empty($_POST['valor_base'])) {
                    $v_base = str_replace('.', '', $_POST['valor_base']);
                    $v_base = str_replace(',', '.', $v_base);
                    $valor_base_final = (float)$v_base;
                }

                // Verificar se já existe ponto de ordem para esta ata
                $stmt_check = $pdo->prepare("SELECT id FROM pontos_ordem WHERE ata_id = ? LIMIT 1");
                $stmt_check->execute([$id]);
                $ponto_existente = $stmt_check->fetch();

                if ($ponto_existente) {
                    $sql_ponto_up = "UPDATE pontos_ordem SET proposta_numero = ?, codigo_procedimento = ?, titulo = ?, subscrito_por = ?, valor_base = ?, detalhes = ?, resultado_votacao = ? WHERE ata_id = ?";
                    $stmt_p_up = $pdo->prepare($sql_ponto_up);
                    $stmt_p_up->execute([
                        $_POST['proposta_numero'] ?? '',
                        $_POST['codigo_procedimento'] ?? '',
                        $_POST['ponto_titulo'],
                        $_POST['subscrito_por'] ?? '',
                        $valor_base_final,
                        $_POST['detalhes'] ?? '',
                        $_POST['resultado_votacao'] ?? 'Aprovado por unanimidade',
                        $id
                    ]);
                } else {
                    $sql_ponto_ins = "INSERT INTO pontos_ordem (ata_id, numero_ponto, proposta_numero, codigo_procedimento, titulo, subscrito_por, valor_base, detalhes, resultado_votacao) VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt_p_ins = $pdo->prepare($sql_ponto_ins);
                    $stmt_p_ins->execute([
                        $id,
                        $_POST['proposta_numero'] ?? '',
                        $_POST['codigo_procedimento'] ?? '',
                        $_POST['ponto_titulo'],
                        $_POST['subscrito_por'] ?? '',
                        $valor_base_final,
                        $_POST['detalhes'] ?? '',
                        $_POST['resultado_votacao'] ?? 'Aprovado por unanimidade'
                    ]);
                }
            }

            $mensagem = "Sucesso! A Ata foi atualizada com segurança.";
            $tipo_alerta = "sucesso";
        } else {
            $mensagem = "Erro crítico ao atualizar a base de dados.";
            $tipo_alerta = "erro";
        }
    }
}

// Buscar dados atuais da ata
$stmt = $pdo->prepare("SELECT * FROM atas WHERE id = ?");
$stmt->execute([$id]);
$ata = $stmt->fetch();

if (!$ata) {
    echo "Ata não encontrada.";
    exit;
}

// Buscar ponto de ordem associado
$stmt_p = $pdo->prepare("SELECT * FROM pontos_ordem WHERE ata_id = ? LIMIT 1");
$stmt_p->execute([$id]);
$ponto = $stmt_p->fetch();

include_once 'includes/header.php'; 
?>

<div class="card">
    <h2>Editar Ata N.º <?php echo htmlspecialchars($ata['numero_ata']); ?></h2>
    
    <?php if ($mensagem): ?>
        <div style="padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; 
            <?php echo ($tipo_alerta === 'sucesso') ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'; ?>">
            <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Fase / Estado do Registo <span style="color:red">*</span></label>
            <select name="fase_registo" required>
                <option value="Agendada" <?php echo ($ata['estado'] === 'Agendada') ? 'selected' : ''; ?>>Agendada</option>
                <option value="Em Minuta" <?php echo ($ata['estado'] === 'Em Minuta') ? 'selected' : ''; ?>>Em Minuta</option>
                <option value="Aprovada" <?php echo ($ata['estado'] === 'Aprovada') ? 'selected' : ''; ?>>Aprovada e Definitiva</option>
            </select>
        </div>
        <div class="form-group">
            <label>Caráter da Reunião</label>
            <select name="carater_reuniao" required>
                <option value="Pública" <?php echo ($ata['carater_reuniao'] === 'Pública') ? 'selected' : ''; ?>>Pública</option>
                <option value="Privada" <?php echo ($ata['carater_reuniao'] === 'Privada') ? 'selected' : ''; ?>>Privada (Fechada)</option>
            </select>
        </div>
        <div class="form-group">
            <label>Tipo de Reunião</label>
            <select name="tipo_reuniao" required>
                <option value="Ordinária" <?php echo ($ata['tipo_reuniao'] === 'Ordinária') ? 'selected' : ''; ?>>Ordinária</option>
                <option value="Extraordinária" <?php echo ($ata['tipo_reuniao'] === 'Extraordinária') ? 'selected' : ''; ?>>Extraordinária</option>
            </select>
        </div>
        <div class="form-group">
            <label>N.º da Ata <span style="color:red">*</span></label>
            <input type="text" name="numero_ata" value="<?php echo htmlspecialchars($ata['numero_ata']); ?>" required>
        </div>
        <div class="form-group">
            <label>Mandato</label>
            <input type="text" name="mandato" value="<?php echo htmlspecialchars($ata['mandato']); ?>" required>
        </div>
        <div class="form-group">
            <label>N.º da Reunião por Extenso <span style="color:red">*</span></label>
            <input type="text" name="numero_reuniao" value="<?php echo htmlspecialchars($ata['numero_reuniao']); ?>" required>
        </div>
        <div class="form-group">
            <label>Data da Reunião <span style="color:red">*</span></label>
            <input type="date" name="data_reuniao" value="<?php echo htmlspecialchars($ata['data_reuniao']); ?>" required>
        </div>
        <div class="form-group">
            <label>Hora de Início <span style="color:red">*</span></label>
            <input type="time" name="hora_inicio" value="<?php echo htmlspecialchars($ata['hora_inicio']); ?>" required>
        </div>
        <div class="form-group">
            <label>Hora de Encerramento</label>
            <input type="time" name="hora_fim" value="<?php echo htmlspecialchars($ata['hora_fim'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Local da Reunião</label>
            <input type="text" name="local_reuniao" value="<?php echo htmlspecialchars($ata['local_reuniao']); ?>" required>
        </div>
        <div class="form-group">
            <label>Presidente</label>
            <input type="text" name="presidente" value="<?php echo htmlspecialchars($ata['presidente']); ?>" required>
        </div>
        <div class="form-group">
            <label>Secretária</label>
            <input type="text" name="secretaria" value="<?php echo htmlspecialchars($ata['secretaria']); ?>" required>
        </div>
        <div class="form-group">
            <label>Tesoureiro</label>
            <input type="text" name="tesoureiro" value="<?php echo htmlspecialchars($ata['tesoureiro'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Participantes Presenciais</label>
            <textarea name="membros_presentes_txt" rows="2"><?php echo htmlspecialchars($ata['participantes_presenciais'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label>Participantes em Videoconferência</label>
            <textarea name="participantes_videoconf" rows="2"><?php echo htmlspecialchars($ata['participantes_videoconf'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label>Ausentes e Justificação Legal</label>
            <textarea name="ausentes_justificados" rows="2"><?php echo htmlspecialchars($ata['ausentes_justificados'] ?? ''); ?></textarea>
        </div>

        <hr style="margin: 20px 0;">
        <h3>Deliberação / Ponto de Ordem</h3>
        <div class="form-group">
            <label>Proposta N.º</label>
            <input type="text" name="proposta_numero" value="<?php echo htmlspecialchars($ponto['proposta_numero'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Código de Procedimento</label>
            <input type="text" name="codigo_procedimento" value="<?php echo htmlspecialchars($ponto['codigo_procedimento'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Título / Assunto</label>
            <input type="text" name="ponto_titulo" value="<?php echo htmlspecialchars($ponto['titulo'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Subscrito por</label>
            <input type="text" name="subscrito_por" value="<?php echo htmlspecialchars($ponto['subscrito_por'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Valor Base (€)</label>
            <input type="text" id="valor_base" name="valor_base" value="<?php echo isset($ponto['valor_base']) ? number_format($ponto['valor_base'], 2, ',', '.') : ''; ?>">
        </div>
        <div class="form-group">
            <label>Detalhes / Discussão</label>
            <textarea name="detalhes" rows="5"><?php echo htmlspecialchars($ponto['detalhes'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label>Resultado da Votação</label>
            <select name="resultado_votacao">
                <option value="Aprovado por unanimidade" <?php echo (($ponto['resultado_votacao'] ?? '') === 'Aprovado por unanimidade') ? 'selected' : ''; ?>>Aprovado por unanimidade</option>
                <option value="Aprovado por maioria" <?php echo (($ponto['resultado_votacao'] ?? '') === 'Aprovado por maioria') ? 'selected' : ''; ?>>Aprovado por maioria</option>
                <option value="Reprovado" <?php echo (($ponto['resultado_votacao'] ?? '') === 'Reprovado') ? 'selected' : ''; ?>>Reprovado</option>
                <option value="Retirado" <?php echo (($ponto['resultado_votacao'] ?? '') === 'Retirado') ? 'selected' : ''; ?>>Retirado</option>
                <option value="Adiado" <?php echo (($ponto['resultado_votacao'] ?? '') === 'Adiado') ? 'selected' : ''; ?>>Adiado</option>
            </select>
        </div>

        <div style="margin-top: 25px; display: flex; justify-content: space-between;">
            <a href="ver_ata.php?id=<?php echo $id; ?>" class="btn" style="background: #6c757d; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 4px;">Cancelar</a>
            <button type="submit" class="btn btn-accent" onclick="return confirm('Confirma as alterações a esta ata?');">Guardar Alterações</button>
        </div>
    </form>
</div>

<!-- Máscara para o Valor Base -->
<script>
    const elValorBase = document.getElementById('valor_base');
    if (elValorBase) {
        elValorBase.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value === '') {
                e.target.value = '';
                return;
            }
            let floatValue = (parseFloat(value) / 100).toFixed(2);
            let parts = floatValue.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            e.target.value = parts.join(',');
        });
    }
</script>

<?php include_once 'includes/footer.php'; ?>