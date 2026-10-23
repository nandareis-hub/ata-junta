<?php 
require_once 'config/conexao.php';

$mensagem = '';
$tipo_alerta = '';

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
    
    // Validação de segurança: Verificar se a hora de fim é menor que a hora de início
    if ($hora_fim && $hora_inicio && $hora_fim < $hora_inicio) {
        $mensagem = "Erro: A hora de encerramento não pode ser anterior à hora de início!";
        $tipo_alerta = "erro";
    } else {
        // Tratar checkboxes dos membros presentes
        $membros_presentes_arr = $_POST['membros_presentes'] ?? [];
        $participantes_presenciais = !empty($membros_presentes_arr) ? implode(', ', $membros_presentes_arr) : null;
        
        $participantes_videoconf = !empty($_POST['participantes_videoconf']) ? trim($_POST['participantes_videoconf']) : null;
        $ausentes_justificados = !empty($_POST['ausentes_justificados']) ? trim($_POST['ausentes_justificados']) : null;

        // Upload de Áudio seguro
        $ficheiro_audio = null;
        if (isset($_FILES['audio_reuniao']) && $_FILES['audio_reuniao']['error'] === UPLOAD_ERR_OK) {
            $extensao = strtolower(pathinfo($_FILES['audio_reuniao']['name'], PATHINFO_EXTENSION));
            $permitidas = ['mp3', 'wav', 'm4a', 'ogg'];
            
            if (in_array($extensao, $permitidas)) {
                $pasta_destino = 'uploads/';
                if (!is_dir($pasta_destino)) {
                    mkdir($pasta_destino, 0777, true);
                }
                $nome_ficheiro = time() . '_' . basename($_FILES['audio_reuniao']['name']);
                $caminho_destino = $pasta_destino . $nome_ficheiro;
                if (move_uploaded_file($_FILES['audio_reuniao']['tmp_name'], $caminho_destino)) {
                    $ficheiro_audio = $caminho_destino;
                }
            } else {
                $mensagem = "Aviso: O ficheiro de áudio enviado não tem um formato válido (.mp3, .wav).";
                $tipo_alerta = "aviso";
            }
        }

        if ($tipo_alerta !== "erro") {
            $sql = "INSERT INTO atas (numero_ata, mandato, tipo_reuniao, numero_reuniao, data_reuniao, hora_inicio, hora_fim, local_reuniao, presidente, secretaria, tesoureiro, participantes_presenciais, participantes_videoconf, ausentes_justificados, carater_reuniao, ficheiro_audio, estado) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$numero_ata, $mandato, $tipo_reuniao, $numero_reuniao, $data_reuniao, $hora_inicio, $hora_fim, $local_reuniao, $presidente, $secretaria, $tesoureiro, $participantes_presenciais, $participantes_videoconf, $ausentes_justificados, $carater_reuniao, $ficheiro_audio, $fase_registo])) {
                $ata_id = $pdo->lastInsertId();
                
                // Inserir ponto de ordem se preenchido
                if (!empty($_POST['ponto_titulo'])) {
                    $sql_ponto = "INSERT INTO pontos_ordem (ata_id, numero_ponto, proposta_numero, codigo_procedimento, titulo, subscrito_por, valor_base, detalhes, resultado_votacao) VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt_ponto = $pdo->prepare($sql_ponto);
                    $stmt_ponto->execute([
                        $ata_id,
                        $_POST['proposta_numero'] ?? '',
                        $_POST['codigo_procedimento'] ?? '',
                        $_POST['ponto_titulo'],
                        $_POST['subscrito_por'] ?? '',
                        !empty($_POST['valor_base']) ? $_POST['valor_base'] : null,
                        $_POST['detalhes'] ?? '',
                        $_POST['resultado_votacao'] ?? 'Aprovado por unanimidade'
                    ]);
                }
                
                $mensagem = "Sucesso! A Ata n.º " . htmlspecialchars($numero_ata) . " foi guardada com segurança.";
                $tipo_alerta = "sucesso";
            } else {
                $mensagem = "Erro crítico ao gravar na base de dados.";
                $tipo_alerta = "erro";
            }
        }
    }
}

include_once 'includes/header.php'; 
?>

<div class="card">
    <h2>Registo Inteligente e Transcrição ao Vivo</h2>
    
    <?php if ($mensagem): ?>
        <div style="padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; 
            <?php echo ($tipo_alerta === 'sucesso') ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'; ?>">
            <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>

    <!-- NAVEGAÇÃO DE ETAPAS (TABS) -->
    <div class="tabs-navigation">
        <button type="button" class="tab-btn active" onclick="openTab(event, 'tab-agendamento')">1. Dados Gerais</button>
        <button type="button" class="tab-btn" onclick="openTab(event, 'tab-presencas')">2. Presenças</button>
        <button type="button" class="tab-btn" onclick="openTab(event, 'tab-minuta')">3. Deliberações, Voz & Áudio</button>
    </div>

    <form action="" method="POST" enctype="multipart/form-data" onsubmit="return validarFormulario()">
        
        <!-- ETAPA 1: DADOS GERAIS -->
        <div id="tab-agendamento" class="tab-content active">
            <div class="form-group">
                <label>Fase / Estado do Registo <span style="color:red">*</span></label>
                <select name="fase_registo" required>
                    <option value="Agendada">Agendada (Emissão de Convocatória)</option>
                    <option value="Em Minuta">Em Minuta (Reunião Realizada)</option>
                    <option value="Aprovada">Aprovada e Definitiva</option>
                </select>
            </div>
            <div class="form-group">
                <label>Caráter da Reunião</label>
                <select name="carater_reuniao" required>
                    <option value="Pública">Pública</option>
                    <option value="Privada">Privada (Fechada)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Tipo de Reunião</label>
                <select name="tipo_reuniao" required>
                    <option value="Ordinária">Ordinária</option>
                    <option value="Extraordinária">Extraordinária</option>
                </select>
            </div>
            <div class="form-group">
                <label>N.º da Ata <span style="color:red">*</span></label>
                <input type="text" name="numero_ata" required placeholder="Ex: 19/2026">
            </div>
            <div class="form-group">
                <label>Mandato</label>
                <input type="text" name="mandato" value="2025/2029" required>
            </div>
            <div class="form-group">
                <label>N.º da Reunião por Extenso <span style="color:red">*</span></label>
                <input type="text" name="numero_reuniao" required placeholder="Ex: Terceira">
            </div>
            <div class="form-group">
                <label>Data da Reunião <span style="color:red">*</span></label>
                <input type="date" name="data_reuniao" required>
            </div>
            <div class="form-group">
                <label>Hora de Início <span style="color:red">*</span></label>
                <input type="time" name="hora_inicio" id="hora_inicio" required>
            </div>
            <div class="form-group">
                <label>Local da Reunião</label>
                <input type="text" name="local_reuniao" value="Sede da União, sita na Rua Dona Leonor de Mascarenhas, número quarenta e quatro, Letra A, em Almada" required>
            </div>
        </div>

        <!-- ETAPA 2: PRESENÇAS -->
        <div id="tab-presencas" class="tab-content">
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
                <label>Membros Presentes (Seleção Rápida):</label>
                <div class="checkbox-group">
                    <label><input type="checkbox" name="membros_presentes[]" value="Maria D'Assis Beiramar Lopes Almeida (Presidente)" checked> Maria D'Assis Almeida (Presidente)</label>
                    <label><input type="checkbox" name="membros_presentes[]" value="Maria Filomena Duarte Paris (Secretária)" checked> Maria Filomena Paris (Secretária)</label>
                    <label><input type="checkbox" name="membros_presentes[]" value="Carlos Manuel Mestre Valente Martins (Tesoureiro)" checked> Carlos Valente (Tesoureiro)</label>
                    <label><input type="checkbox" name="membros_presentes[]" value="Raquel Palhotas Pacheco (Vogal)"> Raquel Pacheco (Vogal)</label>
                    <label><input type="checkbox" name="membros_presentes[]" value="Paulo Ricardo Correia Póvoa Figueiredo (Vogal)"> Paulo Figueiredo (Vogal)</label>
                    <label><input type="checkbox" name="membros_presentes[]" value="Martim de Freitas Gordinho (Vogal)"> Martim Gordinho (Vogal)</label>
                </div>
            </div>
            <div class="form-group">
                <label>Participantes em Videoconferência</label>
                <textarea name="participantes_videoconf" rows="2" placeholder="Ex: Maria Filomena Duarte Paris (Secretária)"></textarea>
            </div>
            <div class="form-group">
                <label>Ausentes e Justificação Legal</label>
                <textarea name="ausentes_justificados" rows="2" placeholder="Ex: Manuel Domingos Rodrigues Batista (Vogal) - Férias (art.º 19.º da Lei n.º 75/2013)"></textarea>
            </div>
        </div>

        <!-- ETAPA 3: DELIBERAÇÕES, TRANSCRIÇÃO AO VIVO & ÁUDIO -->
        <div id="tab-minuta" class="tab-content">
            <div class="form-group">
                <label>Hora de Encerramento / Fim</label>
                <input type="time" name="hora_fim" id="hora_fim">
            </div>
            <div class="form-group">
                <label>Anexar Gravação de Áudio (.mp3, .wav)</label>
                <input type="file" name="audio_reuniao" accept=".mp3,.wav,.m4a,.ogg">
            </div>

            <hr style="margin: 20px 0; border: 0; border-top: 1px solid var(--border-color);">
            <h3>Primeiro Ponto da Ordem do Dia & Transcrição ao Vivo</h3>

            <div class="form-group">
                <label>Proposta N.º</label>
                <input type="text" name="proposta_numero" placeholder="Ex: Proposta nº 148">
            </div>
            <div class="form-group">
                <label>Código de Procedimento</label>
                <input type="text" name="codigo_procedimento" placeholder="Ex: CP 15/26">
            </div>
            <div class="form-group">
                <label>Título / Assunto</label>
                <input type="text" name="ponto_titulo" placeholder="Ex: Abertura de procedimento por Consulta Prévia">
            </div>
            <div class="form-group">
                <label>Subscrito por</label>
                <input type="text" name="subscrito_por" placeholder="Ex: Maria D'Assis Almeida">
            </div>
            <div class="form-group">
                <label>Valor Base (€)</label>
                <input type="number" step="0.01" name="valor_base" placeholder="19000.00">
            </div>

            <!-- BLOCO DE TRANSCRIÇÃO AO VIVO EM PORTUGUÊS -->
            <div class="form-group">
                <label>Transcrição em Tempo Real (Assistente de Voz)</label>
                <div style="margin-bottom: 10px;">
                    <button type="button" id="btn-gravar-voz" class="btn" style="background-color: #28a745;" onclick="iniciarTranscricao()">🎙️ Iniciar Transcrição ao Vivo</button>
                    <button type="button" id="btn-parar-voz" class="btn" style="background-color: #dc3545; display:none;" onclick="pararTranscricao()">⏹️ Parar Transcrição</button>
                    <span id="status-gravacao" style="margin-left: 10px; font-weight: bold; color: #dc3545;"></span>
                </div>
                <textarea name="detalhes" id="texto-transcrito" rows="5" placeholder="A transcrição ao vivo aparecerá aqui automaticamente à medida que fala..."></textarea>
                <small style="color: #64748b;">Utilize o Google Chrome ou Edge. O microfone converterá a fala diretamente para este campo.</small>
            </div>

            <div class="form-group">
                <label>Resultado da Votação</label>
                <select name="resultado_votacao">
                    <option value="Aprovado por unanimidade">Aprovado por unanimidade</option>
                    <option value="Aprovado por maioria">Aprovado por maioria</option>
                    <option value="Reprovado">Reprovado</option>
                    <option value="Retirado">Retirado da Ordem do Dia</option>
                    <option value="Adiado">Adiado</option>
                </select>
            </div>
        </div>

        <div style="margin-top: 25px; display: flex; justify-content: space-between;">
            <button type="button" class="btn" onclick="prevTab()">Anterior</button>
            <button type="button" class="btn" onclick="nextTab()">Próximo Passo</button>
            <button type="submit" class="btn btn-accent" onclick="return confirm('Confirma que deseja guardar esta ata no sistema?');">Guardar com Segurança</button>
        </div>
    </form>
</div>

<!-- SCRIPTS DE NAVEGAÇÃO, VALIDAÇÃO E TRANSCRIÇÃO DE VOZ -->
<script>
    let currentTabIndex = 0;
    const tabs = ['tab-agendamento', 'tab-presencas', 'tab-minuta'];

    function openTab(evt, tabName) {
        let i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].classList.remove("active");
        }
        tablinks = document.getElementsByClassName("tab-btn");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].classList.remove("active");
        }
        document.getElementById(tabName).classList.add("active");
        if(evt) evt.currentTarget.classList.add("active");
        
        currentTabIndex = tabs.indexOf(tabName);
    }

    function nextTab() {
        if (currentTabIndex < tabs.length - 1) {
            currentTabIndex++;
            openTab(null, tabs[currentTabIndex]);
            document.getElementsByClassName("tab-btn")[currentTabIndex].classList.add("active");
        }
    }

    function prevTab() {
        if (currentTabIndex > 0) {
            currentTabIndex--;
            openTab(null, tabs[currentTabIndex]);
            document.getElementsByClassName("tab-btn")[currentTabIndex].classList.add("active");
        }
    }

    function validarFormulario() {
        const horaInicio = document.getElementById('hora_inicio').value;
        const horaFim = document.getElementById('hora_fim').value;

        if (horaInicio && horaFim && horaFim < horaInicio) {
            alert("Atenção: A hora de fim não pode ser anterior à hora de início da reunião!");
            openTab(null, 'tab-minuta');
            document.getElementsByClassName("tab-btn")[2].classList.add("active");
            return false;
        }
        return true;
    }

    // Lógica da Transcrição de Voz ao Vivo (Web Speech API)
    let reconhecimento;
    let aOuvir = false;

    function iniciarTranscricao() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        
        if (!SpeechRecognition) {
            alert("O seu navegador não suporta transcrição de voz. Utilize o Google Chrome.");
            return;
        }

        reconhecimento = new SpeechRecognition();
        reconhecimento.lang = 'pt-PT';
        reconhecimento.continuous = true;
        reconhecimento.interimResults = true;

        reconhecimento.onstart = function() {
            aOuvir = true;
            document.getElementById('btn-gravar-voz').style.display = 'none';
            document.getElementById('btn-parar-voz').style.display = 'inline-block';
            document.getElementById('status-gravacao').innerText = '🔴 A gravar e a transcrever em direto...';
        };

        reconhecimento.onresult = function(event) {
            let transcricaoFinal = '';
            for (let i = event.resultIndex; i < event.results.length; ++i) {
                if (event.results[i].isFinal) {
                    transcricaoFinal += event.results[i][0].transcript + ' ';
                }
            }
            if (transcricaoFinal) {
                const textarea = document.getElementById('texto-transcrito');
                textarea.value += (textarea.value ? ' ' : '') + transcricaoFinal;
            }
        };

        reconhecimento.onerror = function(event) {
            console.error("Erro no reconhecimento de fala: ", event.error);
        };

        reconhecimento.onend = function() {
            aOuvir = false;
            document.getElementById('btn-gravar-voz').style.display = 'inline-block';
            document.getElementById('btn-parar-voz').style.display = 'none';
            document.getElementById('status-gravacao').innerText = '';
        };

        reconhecimento.start();
    }

    function pararTranscricao() {
        if (reconhecimento && aOuvir) {
            reconhecimento.stop();
        }
    }
</script>

<?php include_once 'includes/footer.php'; ?>