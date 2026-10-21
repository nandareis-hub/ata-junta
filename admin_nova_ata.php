<?php 
require_once 'config/conexao.php';

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero_ata = $_POST['numero_ata'];
    $mandato = $_POST['mandato'];
    $tipo_reuniao = $_POST['tipo_reuniao'];
    $numero_reuniao = $_POST['numero_reuniao'];
    $data_reuniao = $_POST['data_reuniao'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fim = $_POST['hora_fim'];
    $local_reuniao = $_POST['local_reuniao'];
    $presidente = $_POST['presidente'];
    $secretaria = $_POST['secretaria'];
    $tesoureiro = $_POST['tesoureiro'];
    $participantes_presenciais = $_POST['participantes_presenciais'];
    $participantes_videoconf = $_POST['participantes_videoconf'];
    $ausentes_justificados = $_POST['ausentes_justificados'];

    $sql = "INSERT INTO atas (numero_ata, mandato, tipo_reuniao, numero_reuniao, data_reuniao, hora_inicio, hora_fim, local_reuniao, presidente, secretaria, tesoureiro, participantes_presenciais, participantes_videoconf, ausentes_justificados) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$numero_ata, $mandato, $tipo_reuniao, $numero_reuniao, $data_reuniao, $hora_inicio, $hora_fim, $local_reuniao, $presidente, $secretaria, $tesoureiro, $participantes_presenciais, $participantes_videoconf, $ausentes_justificados])) {
        $ata_id = $pdo->lastInsertId();
        
        // Inserir ponto de ordem inicial se preenchido
        if (!empty($_POST['ponto_titulo'])) {
            $sql_ponto = "INSERT INTO pontos_ordem (ata_id, numero_ponto, proposta_numero, codigo_procedimento, titulo, subscrito_por, valor_base, detalhes, resultado_votacao) VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_ponto = $pdo->prepare($sql_ponto);
            $stmt_ponto->execute([
                $ata_id,
                $_POST['proposta_numero'],
                $_POST['codigo_procedimento'],
                $_POST['ponto_titulo'],
                $_POST['subscrito_por'],
                !empty($_POST['valor_base']) ? $_POST['valor_base'] : null,
                $_POST['detalhes'],
                $_POST['resultado_votacao']
            ]);
        }
        
        $mensagem = "Ata registada com sucesso!";
    } else {
        $mensagem = "Erro ao registrar ata.";
    }
}

include_once 'includes/header.php'; 
?>

<div class="card">
    <h2>Registar Nova Ata de Reunião</h2>
    <?php if ($mensagem): ?>
        <p style="color: green; font-weight: bold; margin-bottom: 15px;"><?php echo $mensagem; ?></p>
    <?php endif; ?>

    <form action="" method="POST">
        <h3>Dados da Reunião</h3>
        <div class="form-group">
            <label>N.º da Ata (ex: 20/2026)</label>
            <input type="text" name="numero_ata" required>
        </div>
        <div class="form-group">
            <label>Mandato (ex: 2025/2029)</label>
            <input type="text" name="mandato" value="2025/2029" required>
        </div>
        <div class="form-group">
            <label>Tipo de Reunião</label>
            <select name="tipo_reuniao" required>
                <option value="Ordinária">Ordinária</option>
                <option value="Extraordinária">Extraordinária</option>
            </select>
        </div>
        <div class="form-group">
            <label>N.º da Reunião por Extenso (ex: Quarta)</label>
            <input type="text" name="numero_reuniao" required>
        </div>
        <div class="form-group">
            <label>Data</label>
            <input type="date" name="data_reuniao" required>
        </div>
        <div class="form-group">
            <label>Hora Início</label>
            <input type="time" name="hora_inicio" required>
        </div>
        <div class="form-group">
            <label>Hora Fim</label>
            <input type="time" name="hora_fim">
        </div>
        <div class="form-group">
            <label>Local da Reunião</label>
            <input type="text" name="local_reuniao" value="Sede da União, sita na Rua Dona Leonor de Mascarenhas, número quarenta e quatro, Letra A, em Almada" required>
        </div>

        <h3>Membros e Participantes</h3>
        <div class="form-group">
            <label>Presidente</label>
            <input type="text" name="presidente" value="Maria D'Assis Beiramar Lopes Almeida" required>
        </div>
        <div class="form-group">
            <label>Secretária</label>
            <input type="text" name="secretaria" value="Maria Filomena Duarte Paris" required>
        </div>
        <div class="form-group">
            <label>Tesoureiro</label>
            <input type="text" name="tesoureiro" value="Carlos Manuel Mestre Valente Martins">
        </div>
        <div class="form-group">
            <label>Vogais Presentes (Separados por vírgula)</label>
            <textarea name="participantes_presenciais" rows="2"></textarea>
        </div>
        <div class="form-group">
            <label>Participantes em Videoconferência</label>
            <textarea name="participantes_videoconf" rows="2"></textarea>
        </div>
        <div class="form-group">
            <label>Ausentes e Justificação</label>
            <textarea name="ausentes_justificados" rows="2"></textarea>
        </div>

        <h3>Ponto 1 da Ordem do Dia (Opcional)</h3>
        <div class="form-group">
            <label>Proposta N.º (ex: Proposta nº 149)</label>
            <input type="text" name="proposta_numero">
        </div>
        <div class="form-group">
            <label>Código Procedimento (ex: CP 16/26)</label>
            <input type="text" name="codigo_procedimento">
        </div>
        <div class="form-group">
            <label>Título / Assunto</label>
            <input type="text" name="ponto_titulo">
        </div>
        <div class="form-group">
            <label>Subscrito por</label>
            <input type="text" name="subscrito_por">
        </div>
        <div class="form-group">
            <label>Valor Base (€)</label>
            <input type="number" step="0.01" name="valor_base">
        </div>
        <div class="form-group">
            <label>Detalhes da Proposta</label>
            <textarea name="detalhes" rows="3"></textarea>
        </div>
        <div class="form-group">
            <label>Resultado da Votação</label>
            <input type="text" name="resultado_votacao" value="Aprovada por unanimidade">
        </div>

        <button type="submit" class="btn btn-accent">Guardar Ata</button>
    </form>
</div>

<?php include_once 'includes/footer.php'; ?>