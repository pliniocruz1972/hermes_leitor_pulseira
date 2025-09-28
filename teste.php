<?php
/**
 * Teste do Sistema de Entrega de Pulseiras
 * Salão do Turismo
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Teste - Sistema de Pulseiras</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { 
            font-family: 'Arial', sans-serif; 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .container { max-width: 800px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); margin-bottom: 1rem; }
        .header { background: #9b59b6; color: white; padding: 2rem 0; text-align: center; }
        .success { color: #155724; background: #d4edda; padding: 0.75rem; border-radius: 5px; margin: 0.5rem 0; }
        .error { color: #721c24; background: #f8d7da; padding: 0.75rem; border-radius: 5px; margin: 0.5rem 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 0.75rem; border-radius: 5px; margin: 0.5rem 0; }
        .test-item { padding: 1rem; border-left: 4px solid #9b59b6; margin: 1rem 0; background: #f8f9fa; }
    </style>
</head>
<body>
    <div class='header'>
        <h1><i class='fas fa-hand-paper'></i> Teste do Sistema de Pulseiras</h1>
        <p>Verificação de Funcionalidades</p>
    </div>
    
    <div class='container mt-4'>
        <div class='card'>
            <div class='card-body'>
                <h3>🧪 Executando Testes do Sistema</h3>";

try {
    $entregaPulseiras = new EntregaPulseiras();
    echo "<div class='success'>✅ Classe EntregaPulseiras carregada com sucesso</div>";
    
    // Teste 1: Conexão com banco
    echo "<div class='test-item'>";
    echo "<h5>🔗 Teste 1: Conexão com Banco de Dados</h5>";
    try {
        $pdo = $entregaPulseiras->getPdo();
        $stmt = $pdo->query("SELECT 1");
        echo "<div class='success'>✅ Conexão com banco estabelecida</div>";
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erro na conexão: " . $e->getMessage() . "</div>";
    }
    echo "</div>";
    
    // Teste 2: Verificar tabelas
    echo "<div class='test-item'>";
    echo "<h5>📋 Teste 2: Verificação das Tabelas</h5>";
    $tabelas = ['entregas_pulseiras', 'pulseiras_config'];
    
    foreach ($tabelas as $tabela) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '{$tabela}'");
            $existe = $stmt->fetch();
            
            if ($existe) {
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM {$tabela}");
                $total = $stmt->fetch()['total'];
                echo "<div class='success'>✅ Tabela '{$tabela}' existe ({$total} registros)</div>";
            } else {
                echo "<div class='error'>❌ Tabela '{$tabela}' não existe</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Erro ao verificar '{$tabela}': " . $e->getMessage() . "</div>";
        }
    }
    echo "</div>";
    
    // Teste 3: Validação de QR Codes
    echo "<div class='test-item'>";
    echo "<h5>📱 Teste 3: Validação de QR Codes</h5>";
    
    $testesQR = [
        '10.123456789' => 'Deve ser válido',
        '20.12345678' => 'Deve ser válido', 
        '30.123456789' => 'Deve ser inválido (não pega pulseira)',
        '40.123456789' => 'Deve ser inválido (não pega pulseira)',
        '50.123456789' => 'Deve ser inválido (tipo não reconhecido)',
        'abc.123456789' => 'Deve ser inválido (formato)',
        '10' => 'Deve ser inválido (sem ponto)',
        '123456789' => 'Deve ser válido (apenas números = tipo 10)',
        '' => 'Deve ser inválido (vazio)'
    ];
    
    foreach ($testesQR as $qr => $esperado) {
        $resultado = $entregaPulseiras->validarFormatoQRCode($qr);
        $status = $resultado['valido'] ? '✅ Válido' : '❌ Inválido';
        $motivo = $resultado['motivo'];
        
        echo "<div class='info'>";
        echo "<strong>QR: '{$qr}'</strong> - {$status}<br>";
        echo "<small>Esperado: {$esperado} | Motivo: {$motivo}</small>";
        echo "</div>";
    }
    echo "</div>";
    
    // Teste 4: Estatísticas
    echo "<div class='test-item'>";
    echo "<h5>📊 Teste 4: Estatísticas do Sistema</h5>";
    try {
        $stats = $entregaPulseiras->obterEstatisticasHoje();
        echo "<div class='success'>✅ Estatísticas funcionando:</div>";
        echo "<ul>";
        echo "<li>Total de entregas hoje: " . $stats['total_entregas'] . "</li>";
        echo "<li>Tipo 10: " . $stats['tipo_10'] . "</li>";
        echo "<li>Tipo 20: " . $stats['tipo_20'] . "</li>";
        echo "</ul>";
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erro nas estatísticas: " . $e->getMessage() . "</div>";
    }
    echo "</div>";
    
    // Teste 5: Últimas entregas
    echo "<div class='test-item'>";
    echo "<h5>📝 Teste 5: Últimas Entregas</h5>";
    try {
        $entregas = $entregaPulseiras->listarUltimasEntregas(3);
        echo "<div class='success'>✅ Função de listagem funcionando</div>";
        echo "<div class='info'>Encontradas " . count($entregas) . " entregas hoje</div>";
        
        if (count($entregas) > 0) {
            echo "<ul>";
            foreach ($entregas as $entrega) {
                echo "<li>";
                echo "Nome: " . ($entrega['nome_participante'] ?? 'N/A') . " | ";
                echo "Tipo: " . $entrega['tipo_qr'] . " | ";
                echo "Horário: " . date('H:i:s', strtotime($entrega['data_entrega']));
                echo "</li>";
            }
            echo "</ul>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erro ao listar entregas: " . $e->getMessage() . "</div>";
    }
    echo "</div>";
    
    // Teste 6: Teste de QR Code real (se há dados de teste)
    echo "<div class='test-item'>";
    echo "<h5>🔍 Teste 6: Simulação de Verificação</h5>";
    try {
        $resultado = $entregaPulseiras->verificarPulseira('10.999999999');
        echo "<div class='info'>Teste com QR '10.999999999':</div>";
        echo "<ul>";
        echo "<li>Pode retirar: " . ($resultado['pode_retirar'] ? 'Sim' : 'Não') . "</li>";
        echo "<li>Motivo: " . $resultado['motivo'] . "</li>";
        echo "<li>Tipo QR: " . $resultado['tipo_qr'] . "</li>";
        echo "<li>Já retirou: " . ($resultado['ja_retirou'] ? 'Sim' : 'Não') . "</li>";
        echo "</ul>";
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erro na verificação: " . $e->getMessage() . "</div>";
    }
    echo "</div>";
    
    // Resumo final
    echo "<hr>";
    echo "<div class='success text-center' style='font-size: 1.2em; padding: 1.5rem;'>";
    echo "<strong>🎉 TESTES CONCLUÍDOS!</strong><br>";
    echo "<small>Se não há erros críticos acima, o sistema está pronto para uso.</small><br>";
    echo "<a href='index.php' class='btn btn-primary mt-3' style='background: #9b59b6; border: none;'>";
    echo "<i class='fas fa-hand-paper'></i> Acessar Sistema de Pulseiras";
    echo "</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'><strong>❌ Erro crítico nos testes:</strong><br>" . $e->getMessage() . "</div>";
    echo "<div class='info'>Verifique se:</div>";
    echo "<ul>";
    echo "<li>O sistema foi instalado corretamente</li>";
    echo "<li>As configurações de banco estão corretas</li>";
    echo "<li>As tabelas foram criadas</li>";
    echo "</ul>";
    echo "<a href='install.php' class='btn btn-warning'>🔧 Executar Instalação</a>";
}

echo "            </div>
        </div>
    </div>
    
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
</body>
</html>";
?>
