<?php
require_once 'config.php';

$entregaPulseiras = new EntregaPulseiras();
$estatisticas = $entregaPulseiras->obterEstatisticasHoje();
$ultimasEntregas = $entregaPulseiras->listarUltimasEntregas(5);

// Processa ações
if ($_POST) {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'verificar_qr':
                $qrcode = trim($_POST['qrcode'] ?? '');
                
                if (empty($qrcode)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'QR Code não informado'
                    ]);
                    break;
                }
                
                $verificacao = $entregaPulseiras->verificarPulseira($qrcode);
                
                if ($verificacao['pode_retirar']) {
                    $nomeParticipante = $verificacao['dados']['nome'] ?? 'Participante';
                    
                    echo json_encode([
                        'success' => true,
                        'pode_retirar' => true,
                        'message' => 'PODE RETIRAR PULSEIRA',
                        'participante' => $nomeParticipante,
                        'tipo_qr' => $verificacao['tipo_qr'],
                        'qrcode' => $qrcode,
                        'detalhes' => 'QR Code tipo ' . $verificacao['tipo_qr'] . ' - Primeira retirada hoje'
                    ]);
                } else {
                    $nomeParticipante = null;
                    if ($verificacao['dados']) {
                        $nomeParticipante = $verificacao['dados']['nome'] ?? null;
                    }
                    
                    $detalhes = 'QR Code tipo ' . $verificacao['tipo_qr'];
                    if ($verificacao['ja_retirou']) {
                        $detalhes .= ' - Já retirou hoje às ' . date('H:i', strtotime($verificacao['data_entrega']));
                    }
                    
                    echo json_encode([
                        'success' => false,
                        'message' => $verificacao['motivo'],
                        'participante' => $nomeParticipante,
                        'tipo_qr' => $verificacao['tipo_qr'],
                        'ja_retirou' => $verificacao['ja_retirou'],
                        'data_entrega' => $verificacao['data_entrega'] ?? null,
                        'detalhes' => $detalhes
                    ]);
                }
                break;
                
            case 'confirmar_entrega':
                $qrcode = trim($_POST['qrcode'] ?? '');
                
                if (empty($qrcode)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'QR Code não informado'
                    ]);
                    break;
                }
                
                $resultado = $entregaPulseiras->entregarPulseira($qrcode);
                
                if ($resultado['entrega_registrada'] ?? false) {
                    $nomeParticipante = $resultado['dados']['nome'] ?? 'Participante';
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'PULSEIRA ENTREGUE COM SUCESSO!',
                        'participante' => $nomeParticipante,
                        'tipo_qr' => $resultado['tipo_qr'],
                        'horario_entrega' => date('H:i:s'),
                        'detalhes' => 'QR Code tipo ' . $resultado['tipo_qr'] . ' - Entrega registrada'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => $resultado['motivo'],
                        'participante' => $resultado['dados']['nome'] ?? null,
                        'tipo_qr' => $resultado['tipo_qr']
                    ]);
                }
                break;
        }
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrega de Pulseiras - Salão do Turismo</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #566cfc;
            --secondary-color: #fbd74b;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --pulseira-color: #9b59b6;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-size: 16px;
        }
        
        .header {
            background: var(--pulseira-color);
            color: white;
            padding: 1rem 0;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .main-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 1rem;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        
        .btn-qr {
            background: var(--pulseira-color);
            border: none;
            color: white;
            padding: 1rem 2rem;
            font-size: 1.2rem;
            border-radius: 10px;
            width: 100%;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .btn-qr:hover {
            background: #8e44ad;
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-confirmar {
            background: var(--success-color);
            border: none;
            color: white;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            border-radius: 10px;
            width: 100%;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }
        
        .btn-confirmar:hover {
            background: #218838;
            transform: translateY(-2px);
            color: white;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .status-ativo {
            background: var(--success-color);
            color: white;
        }
        
        .resultado {
            padding: 1rem;
            border-radius: 10px;
            margin: 1rem 0;
            text-align: center;
            font-weight: 600;
            display: none;
        }
        
        .resultado.sucesso {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .resultado.erro {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .resultado.confirmacao {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            font-size: 1rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--pulseira-color);
            box-shadow: 0 0 0 0.2rem rgba(155, 89, 182, 0.25);
        }
        
        .loading {
            display: none;
            text-align: center;
            color: var(--pulseira-color);
        }
        
        .stats-card {
            background: linear-gradient(135deg, var(--pulseira-color), #8e44ad);
            color: white;
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
        }
        
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        @media (max-width: 576px) {
            body {
                font-size: 14px;
            }
            
            .btn-qr {
                padding: 0.8rem 1.5rem;
                font-size: 1.1rem;
            }
            
            .main-container {
                padding: 0.5rem;
            }
        }
        
        /* Estilo para o QR Code Reader */
        #qr-camera-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            border: 2px dashed #dee2e6;
        }
        
        #qr-reader {
            width: 100%;
            max-width: 350px;
            margin: 0 auto;
        }
        
        #qr-reader video {
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .btn-qr.camera-ativa {
            background: var(--danger-color);
        }
        
        .btn-qr.camera-ativa:hover {
            background: #c82333;
            color: white;
        }
        
        .qr-status {
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .qr-status.ativo {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
            display: block;
        }
        
        .qr-status.inativo {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
            display: block;
        }
        
        /* Estilos específicos para o QR Reader */
        #qr-reader {
            border: 3px solid var(--pulseira-color);
            border-radius: 10px;
            overflow: hidden;
            background: #f8f9fa;
            min-height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        #qr-reader video {
            width: 100% !important;
            height: auto !important;
            border-radius: 7px;
            max-width: 350px;
        }
        
        #qr-reader canvas {
            width: 100% !important;
            height: auto !important;
            border-radius: 7px;
            max-width: 350px;
        }
        
        .qr-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: var(--pulseira-color);
            z-index: 10;
        }
        
        .ultimas-entregas {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .entrega-item {
            padding: 0.5rem;
            border-bottom: 1px solid #dee2e6;
            font-size: 0.9rem;
        }
        
        .entrega-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-hand-paper me-2"></i>Entrega de Pulseiras</h1>
        <p class="mb-0">Salão do Turismo - Sistema Mobile</p>
    </div>

    <div class="main-container">
        <!-- Estatísticas do Dia -->
        <div class="stats-card text-center">
            <div class="row">
                <div class="col-4">
                    <div class="stats-number"><?php echo $estatisticas['total_entregas']; ?></div>
                    <div class="stats-label">Total Hoje</div>
                </div>
                <div class="col-4">
                    <div class="stats-number"><?php echo $estatisticas['tipo_10']; ?></div>
                    <div class="stats-label">Tipo 10</div>
                </div>
                <div class="col-4">
                    <div class="stats-number"><?php echo $estatisticas['tipo_20']; ?></div>
                    <div class="stats-label">Tipo 20</div>
                </div>
            </div>
        </div>

        <!-- Status do Sistema -->
        <div class="card">
            <div class="card-body text-center">
                <div class="status-badge status-ativo">
                    <i class="fas fa-hand-paper me-2"></i>SISTEMA DE PULSEIRAS ATIVO
                </div>
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    QR Codes tipo 10 e 20 podem retirar pulseira (uma por dia por pessoa)
                </small>
            </div>
        </div>

        <!-- Leitor QR Code -->
        <div class="card">
            <div class="card-body text-center">
                <!-- Resultado da leitura -->
                <div class="loading" style="margin-bottom: 1rem;">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Verificando QR Code...</p>
                </div>
                
                <div id="resultado" class="resultado" style="margin-bottom: 1rem;"></div>
                
                <!-- Container da câmera -->
                <div id="qr-camera-container" style="display: none; margin-bottom: 1rem;">
                    <div class="qr-status ativo" id="qr-status">
                        <i class="fas fa-video me-2"></i>Câmera ativa - Posicione o QR Code na frente da câmera
                    </div>
                    <div id="qr-reader" style="max-width: 350px; margin: 0 auto;">
                        <div class="qr-loading">
                            <i class="fas fa-camera fa-3x"></i>
                            <div class="mt-2">Iniciando câmera...</div>
                        </div>
                    </div>
                </div>
                
                <button type="button" class="btn btn-qr" id="btn-toggle-qr">
                    <i class="fas fa-camera me-2"></i><span id="btn-text">INICIAR LEITOR QR CODE</span>
                </button>
                
                <div class="text-center mb-2">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Problemas com a câmera? Use o campo manual abaixo
                    </small>
                </div>
                
                <div class="mb-3 mt-3">
                    <label for="qr-manual" class="form-label">Ou digite o código manualmente:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="qr-manual" placeholder="Digite ou cole o código aqui">
                        <button class="btn btn-outline-primary" type="button" onclick="verificarQRManual()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Últimas Entregas -->
        <?php if (!empty($ultimasEntregas)): ?>
        <div class="card">
            <div class="card-body">
                <h6><i class="fas fa-history me-2"></i>Últimas Entregas Hoje</h6>
                <div class="ultimas-entregas">
                    <?php foreach ($ultimasEntregas as $entrega): ?>
                    <div class="entrega-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo htmlspecialchars($entrega['nome_participante'] ?? 'Nome não disponível'); ?></strong>
                                <br>
                                <small class="text-muted">
                                    Tipo <?php echo $entrega['tipo_qr']; ?> - 
                                    <?php echo date('H:i:s', strtotime($entrega['data_entrega'])); ?>
                                </small>
                            </div>
                            <span class="badge bg-success">
                                <i class="fas fa-check"></i>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Instruções -->
        <div class="card">
            <div class="card-body">
                <h6><i class="fas fa-info-circle me-2"></i>Instruções</h6>
                <ul class="list-unstyled small">
                    <li><i class="fas fa-check text-success me-2"></i>Leia o QR Code do participante</li>
                    <li><i class="fas fa-check text-success me-2"></i>Se liberado, confirme a entrega</li>
                    <li><i class="fas fa-check text-success me-2"></i>Cada pessoa pode retirar apenas 1 pulseira por dia</li>
                    <li><i class="fas fa-times text-danger me-2"></i>Tipos 30 e 40 NÃO podem retirar</li>
                </ul>
                
                <div class="mt-3">
                    <h6><i class="fas fa-qrcode me-2"></i>Formato do QR Code</h6>
                    <ul class="list-unstyled small">
                        <li><span class="badge bg-success me-2">10</span>Tipo 10.CODIGO - <strong>Pode retirar</strong> (ex: 10.ABC1234567)</li>
                        <li><span class="badge bg-success me-2">20</span>Tipo 20.ID - <strong>Pode retirar</strong> (ex: 20.12345678)</li>
                        <li><span class="badge bg-danger me-2">30</span>Tipo 30.VALOR - <strong>NÃO pega pulseira</strong></li>
                        <li><span class="badge bg-danger me-2">40</span>Tipo 40.VALOR - <strong>NÃO pega pulseira</strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- QR Code Reader -->
    <script src="../controle_sala/qrcodereader/dist/js/jsQR/jsQR.min.js"></script>
    <script src="../controle_sala/qrcodereader/dist/js/qrcode-reader.min.js?v=20240820" onerror="console.error('Falha ao carregar biblioteca QR')"></script>
    
    <script>
        let qrCodeReader = null;
        let cameraAtiva = false;
        let ultimoQRCode = '';
        let stream = null;
        let bibliotecaDisponivel = false;
        
        $(function(){
            // Aguardar carregamento completo da biblioteca
            setTimeout(function() {
                inicializarSistema();
            }, 1000);
        });
        
        function inicializarSistema() {
            // Verificar se a biblioteca foi carregada
            if (typeof $.qrCodeReader !== 'undefined') {
                bibliotecaDisponivel = true;
                console.log('Biblioteca QR Code carregada com sucesso');
                
                try {
                    $.qrCodeReader.jsQRpath = "../controle_sala/qrcodereader/dist/js/jsQR/jsQR.min.js";
                    $.qrCodeReader.beepPath = "../controle_sala/qrcodereader/dist/audio/beep.mp3";
                } catch (error) {
                    console.error('Erro na configuração da biblioteca QR:', error);
                    bibliotecaDisponivel = false;
                }
            } else {
                console.warn('Biblioteca QR Code não disponível, usando modo manual apenas');
                bibliotecaDisponivel = false;
                $('#btn-toggle-qr').html('<i class="fas fa-keyboard me-2"></i>MODO MANUAL APENAS');
                $('#btn-toggle-qr').removeClass('btn-qr').addClass('btn-warning');
                mostrarAviso('Câmera indisponível. Use o campo manual para inserir códigos.');
            }

            // Configurar o botão toggle
            $("#btn-toggle-qr").on('click', function(){
                if (!bibliotecaDisponivel) {
                    $('#qr-manual').focus();
                    mostrarAviso('Use o campo manual abaixo para inserir o código QR');
                    return;
                }
                
                if (cameraAtiva) {
                    pararCamera();
                } else {
                    iniciarCamera();
                }
            });
            
            // Auto-focus no campo manual
            $('#qr-manual').focus();
        }
        
        // Função para mostrar aviso (não erro)
        function mostrarAviso(mensagem) {
            const resultado = $('#resultado');
            resultado.removeClass('sucesso erro').addClass('resultado');
            resultado.css('background', '#fff3cd');
            resultado.css('color', '#856404');
            resultado.css('border', '1px solid #ffeaa7');
            resultado.html('<i class="fas fa-info-circle fa-2x mb-2"></i><br><strong>Informação</strong><br><div class="mt-2">' + mensagem + '</div>');
            resultado.show();
            
            setTimeout(() => {
                resultado.fadeOut();
            }, 5000);
        }

        // Função para iniciar a câmera
        function iniciarCamera() {
            if (cameraAtiva || !bibliotecaDisponivel) return;
            
            console.log('Iniciando câmera...');
            
            // Mostrar o container da câmera
            $('#qr-camera-container').show();
            
            // Tentar usar HTML5 nativo primeiro (mais controlável)
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia && typeof jsQR !== 'undefined') {
                iniciarCameraNativa();
            } else {
                // Fallback para biblioteca QR
                iniciarCameraBiblioteca();
            }
        }
        
        // Função para câmera HTML5 nativa (fica dentro do container)
        async function iniciarCameraNativa() {
            try {
                $('#qr-reader').html(`
                    <div style="text-align: center;">
                        <div style="background: #e7f3ff; color: #0066cc; padding: 8px; border-radius: 5px; margin-bottom: 10px;">
                            📹 Câmera ativa - Aproxime um QR Code
                        </div>
                        <video id="qr-video" autoplay playsinline style="width: 100%; max-width: 350px; border-radius: 8px; background: #000;"></video>
                        <canvas id="qr-canvas" style="display: none;"></canvas>
                    </div>
                `);
                
                const video = document.getElementById('qr-video');
                const canvas = document.getElementById('qr-canvas');
                const context = canvas.getContext('2d');
                
                const stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        facingMode: 'environment',
                        width: { ideal: 350 },
                        height: { ideal: 250 }
                    } 
                });
                
                video.srcObject = stream;
                await video.play();
                
                cameraAtiva = true;
                atualizarBotao();
                
                // Função de escaneamento
                function scan() {
                    if (!cameraAtiva) return;
                    
                    if (video.readyState === video.HAVE_ENOUGH_DATA) {
                        canvas.height = video.videoHeight;
                        canvas.width = video.videoWidth;
                        context.drawImage(video, 0, 0, canvas.width, canvas.height);
                        
                        const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                        const code = jsQR(imageData.data, imageData.width, imageData.height, {
                            inversionAttempts: "dontInvert",
                        });
                        
                        if (code && code.data !== ultimoQRCode) {
                            ultimoQRCode = code.data;
                            verificarQRCode(code.data);
                            
                            // Limpar após 2 segundos
                            setTimeout(() => {
                                ultimoQRCode = '';
                            }, 2000);
                        }
                    }
                    
                    requestAnimationFrame(scan);
                }
                
                requestAnimationFrame(scan);
                
            } catch (error) {
                console.error('Erro na câmera nativa:', error);
                iniciarCameraBiblioteca(); // Fallback
            }
        }
        
        // Função para biblioteca QR (modal)
        function iniciarCameraBiblioteca() {
            $('#qr-reader').html('<div class="qr-loading"><i class="fas fa-spinner fa-spin fa-2x"></i><div class="mt-2">Carregando câmera...</div></div>');
            
            try {
                setTimeout(() => {
                    try {
                        $('#qr-reader').qrCodeReader({
                            callback: function(code) {
                                console.log('QR detectado:', code);
                                if (code && code.trim() !== '' && code !== ultimoQRCode) {
                                    ultimoQRCode = code;
                                    verificarQRCode(code);
                                    
                                    setTimeout(() => {
                                        ultimoQRCode = '';
                                    }, 2000);
                                }
                            },
                            target: null,
                            audioFeedback: false,
                            skipDuplicates: false,
                            repeatTimeout: 1500
                        });
                        
                        setTimeout(() => {
                            $('#qr-reader').trigger('click');
                            cameraAtiva = true;
                            atualizarBotao();
                        }, 500);
                        
                    } catch (error) {
                        console.error('Erro ao configurar QR reader:', error);
                        mostrarErroCamera('Não foi possível acessar a câmera. Use o campo manual.');
                        pararCamera();
                    }
                }, 800);
                
            } catch (error) {
                console.error('Erro geral:', error);
                mostrarErroCamera('Erro ao inicializar câmera. Use o campo manual para inserir códigos.');
                pararCamera();
            }
        }

        // Função para parar a câmera
        function pararCamera() {
            console.log('Parando câmera...');
            
            try {
                // Parar câmera HTML5 nativa
                const video = document.getElementById('qr-video');
                if (video && video.srcObject) {
                    video.srcObject.getTracks().forEach(track => track.stop());
                    video.srcObject = null;
                }
                
                // Parar biblioteca QR
                if ($.qrCodeReader && $.qrCodeReader.instance) {
                    if (typeof $.qrCodeReader.instance.close === 'function') {
                        $.qrCodeReader.instance.close();
                    }
                }
            } catch (error) {
                console.log('Erro ao fechar câmera (ignorado):', error);
            }
            
            // Limpar completamente o container
            $('#qr-reader').empty();
            $('#qr-camera-container').hide();
            
            // Resetar variáveis
            cameraAtiva = false;
            qrCodeReader = null;
            stream = null;
            ultimoQRCode = '';
            
            atualizarBotao();
            console.log('Câmera parada');
        }

        // Função para mostrar erro da câmera
        function mostrarErroCamera(mensagem) {
            const resultado = $('#resultado');
            resultado.removeClass('sucesso').addClass('erro');
            resultado.html('<i class="fas fa-camera-slash fa-2x mb-2"></i><br><strong>Câmera Indisponível</strong><br><div class="mt-2">' + mensagem + '</div><div class="mt-3"><small><i class="fas fa-keyboard me-1"></i>Use o campo manual abaixo</small></div>');
            resultado.show();
            
            // Auto-hide após 6 segundos
            setTimeout(() => {
                resultado.fadeOut();
            }, 6000);
            
            // Focar no campo manual
            setTimeout(() => {
                $('#qr-manual').focus();
            }, 1000);
        }

        // Função para atualizar o texto e estilo do botão
        function atualizarBotao() {
            const btn = $('#btn-toggle-qr');
            const btnText = $('#btn-text');
            const icon = btn.find('i');
            
            if (cameraAtiva) {
                btn.removeClass('btn-qr').addClass('btn-qr camera-ativa');
                icon.removeClass('fa-camera').addClass('fa-times');
                btnText.text('PARAR LEITOR QR CODE');
            } else {
                btn.removeClass('camera-ativa').addClass('btn-qr');
                icon.removeClass('fa-times').addClass('fa-camera');
                btnText.text('INICIAR LEITOR QR CODE');
            }
        }

        // Função para verificar QR Code manualmente
        function verificarQRManual() {
            const codigo = $('#qr-manual').val().trim();
            if (codigo) {
                verificarQRCode(codigo);
            } else {
                alert('Digite um código para verificar');
            }
        }

        // Função principal para verificar QR Code
        function verificarQRCode(qrcode) {
            $('.loading').show();
            $('#resultado').hide();
            
            $.ajax({
                url: '',
                method: 'POST',
                data: {
                    action: 'verificar_qr',
                    qrcode: qrcode
                },
                dataType: 'json',
                success: function(response) {
                    $('.loading').hide();
                    
                    const resultado = $('#resultado');
                    
                    if (response.success && response.pode_retirar) {
                        resultado.removeClass('erro sucesso').addClass('confirmacao');
                        let html = '<i class="fas fa-hand-paper fa-3x mb-2"></i><br>';
                        html += '<strong style="font-size: 1.2em;">' + response.message + '</strong><br>';
                        
                        if (response.participante) {
                            html += '<div class="mt-2"><strong>👤 ' + response.participante + '</strong></div>';
                        }
                        
                        if (response.tipo_qr) {
                            let tipoDesc = '';
                            switch(response.tipo_qr) {
                                case 10: tipoDesc = 'Código de Inscrição'; break;
                                case 20: tipoDesc = 'Participante Específico'; break;
                                default: tipoDesc = 'Tipo ' + response.tipo_qr;
                            }
                            html += '<div class="mt-2"><small style="background: rgba(255,255,255,0.3); padding: 4px 8px; border-radius: 12px;">📱 QR: ' + tipoDesc + '</small></div>';
                        }
                        
                        if (response.detalhes) {
                            html += '<div class="mt-1"><small style="opacity: 0.8;">' + response.detalhes + '</small></div>';
                        }
                        
                        // Botão de confirmação
                        html += '<button type="button" class="btn btn-confirmar" onclick="confirmarEntrega(\'' + qrcode + '\')">';
                        html += '<i class="fas fa-check me-2"></i>CONFIRMAR ENTREGA DA PULSEIRA';
                        html += '</button>';
                        
                        resultado.html(html);
                        
                    } else {
                        resultado.removeClass('sucesso confirmacao').addClass('erro');
                        let html = '<i class="fas fa-times-circle fa-3x mb-2"></i><br>';
                        html += '<strong style="font-size: 1.2em;">NÃO PODE RETIRAR</strong><br>';
                        html += '<div class="mt-2">' + response.message + '</div>';
                        
                        if (response.participante) {
                            html += '<div class="mt-2"><strong>👤 ' + response.participante + '</strong></div>';
                        }
                        
                        if (response.ja_retirou && response.data_entrega) {
                            const dataEntrega = new Date(response.data_entrega);
                            const horario = dataEntrega.toLocaleTimeString('pt-BR');
                            html += '<div class="mt-2"><strong>⏰ Já retirou hoje às ' + horario + '</strong></div>';
                        }
                        
                        // Informações técnicas do QRCode
                        if (response.tipo_qr) {
                            let tipoDesc = '';
                            switch(response.tipo_qr) {
                                case 10: tipoDesc = 'Código de Inscrição'; break;
                                case 20: tipoDesc = 'Participante Específico'; break;
                                case 30: tipoDesc = 'Tipo 30 (Bloqueado)'; break;
                                case 40: tipoDesc = 'Tipo 40 (Bloqueado)'; break;
                                default: tipoDesc = 'Tipo ' + response.tipo_qr;
                            }
                            html += '<div class="mt-2"><small style="background: rgba(255,255,255,0.3); padding: 4px 8px; border-radius: 12px;">📱 QR: ' + tipoDesc + '</small></div>';
                        }
                        
                        if (response.detalhes) {
                            html += '<div class="mt-1"><small style="opacity: 0.8;">' + response.detalhes + '</small></div>';
                        }
                        
                        resultado.html(html);
                        
                        // Piscar fundo vermelho por 2 segundos
                        $('body').css('background', 'linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%)');
                        setTimeout(() => {
                            $('body').css('background', 'linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%)');
                        }, 2000);
                    }
                    
                    resultado.show();
                    
                    // Limpar campo manual
                    $('#qr-manual').val('');
                    
                    // Auto-hide resultado após 10 segundos
                    setTimeout(() => {
                        resultado.fadeOut();
                    }, 10000);
                },
                error: function() {
                    $('.loading').hide();
                    $('#resultado').removeClass('sucesso confirmacao').addClass('erro')
                        .html('<i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br><strong>Erro de comunicação</strong><br><small>Verifique sua conexão</small>')
                        .show();
                }
            });
        }

        // Função para confirmar entrega da pulseira
        function confirmarEntrega(qrcode) {
            $('.loading').show();
            $('#resultado').hide();
            
            $.ajax({
                url: '',
                method: 'POST',
                data: {
                    action: 'confirmar_entrega',
                    qrcode: qrcode
                },
                dataType: 'json',
                success: function(response) {
                    $('.loading').hide();
                    
                    const resultado = $('#resultado');
                    
                    if (response.success) {
                        resultado.removeClass('erro confirmacao').addClass('sucesso');
                        let html = '<i class="fas fa-check-circle fa-3x mb-2"></i><br>';
                        html += '<strong style="font-size: 1.2em;">' + response.message + '</strong><br>';
                        
                        if (response.participante) {
                            html += '<div class="mt-2"><strong>👤 ' + response.participante + '</strong></div>';
                        }
                        
                        if (response.horario_entrega) {
                            html += '<div class="mt-2"><strong>⏰ Entregue às ' + response.horario_entrega + '</strong></div>';
                        }
                        
                        if (response.tipo_qr) {
                            let tipoDesc = '';
                            switch(response.tipo_qr) {
                                case 10: tipoDesc = 'Código de Inscrição'; break;
                                case 20: tipoDesc = 'Participante Específico'; break;
                                default: tipoDesc = 'Tipo ' + response.tipo_qr;
                            }
                            html += '<div class="mt-2"><small style="background: rgba(255,255,255,0.3); padding: 4px 8px; border-radius: 12px;">📱 QR: ' + tipoDesc + '</small></div>';
                        }
                        
                        if (response.detalhes) {
                            html += '<div class="mt-1"><small style="opacity: 0.8;">' + response.detalhes + '</small></div>';
                        }
                        
                        resultado.html(html);
                        
                        // Som de sucesso (opcional)
                        if (typeof Audio !== 'undefined') {
                            try {
                                const audio = new Audio('../controle_sala/qrcodereader/dist/audio/beep.mp3');
                                audio.play().catch(() => {});
                            } catch(e) {}
                        }
                        
                        // Piscar fundo verde por 2 segundos
                        $('body').css('background', 'linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%)');
                        setTimeout(() => {
                            $('body').css('background', 'linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%)');
                            // Recarregar página para atualizar estatísticas
                            location.reload();
                        }, 2000);
                        
                    } else {
                        resultado.removeClass('sucesso confirmacao').addClass('erro');
                        let html = '<i class="fas fa-times-circle fa-3x mb-2"></i><br>';
                        html += '<strong style="font-size: 1.2em;">ERRO NA ENTREGA</strong><br>';
                        html += '<div class="mt-2">' + response.message + '</div>';
                        
                        if (response.participante) {
                            html += '<div class="mt-2"><strong>👤 ' + response.participante + '</strong></div>';
                        }
                        
                        resultado.html(html);
                        
                        // Piscar fundo vermelho por 2 segundos
                        $('body').css('background', 'linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%)');
                        setTimeout(() => {
                            $('body').css('background', 'linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%)');
                        }, 2000);
                    }
                    
                    resultado.show();
                    
                    // Auto-hide resultado após 8 segundos
                    setTimeout(() => {
                        resultado.fadeOut();
                    }, 8000);
                },
                error: function() {
                    $('.loading').hide();
                    $('#resultado').removeClass('sucesso confirmacao').addClass('erro')
                        .html('<i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br><strong>Erro de comunicação</strong><br><small>Verifique sua conexão</small>')
                        .show();
                }
            });
        }

        // Enter no campo manual
        $('#qr-manual').on('keypress', function(e) {
            if (e.which === 13) {
                verificarQRManual();
            }
        });
    </script>
</body>
</html>
