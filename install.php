<?php
/**
 * Script de instalação do Sistema de Entrega de Pulseiras
 * Salão do Turismo
 */

require_once 'config.php';

$entregaPulseiras = new EntregaPulseiras();
$pdo = $entregaPulseiras->getPdo();

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Instalação - Sistema de Pulseiras</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { 
            font-family: 'Arial', sans-serif; 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .container { max-width: 800px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .header { background: #9b59b6; color: white; padding: 2rem 0; text-align: center; }
        .success { color: #155724; background: #d4edda; padding: 0.75rem; border-radius: 5px; margin: 0.5rem 0; }
        .error { color: #721c24; background: #f8d7da; padding: 0.75rem; border-radius: 5px; margin: 0.5rem 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 0.75rem; border-radius: 5px; margin: 0.5rem 0; }
    </style>
</head>
<body>
    <div class='header'>
        <h1><i class='fas fa-hand-paper'></i> Sistema de Entrega de Pulseiras</h1>
        <p>Instalação e Configuração do Banco de Dados</p>
    </div>
    
    <div class='container mt-4'>
        <div class='card'>
            <div class='card-body'>
                <h3>Instalação do Sistema</h3>";

try {
    // Detectar versão do MySQL
    $stmt = $pdo->query("SELECT VERSION() as version");
    $versao = $stmt->fetch()['version'];
    $versaoMysql = floatval($versao);
    
    echo "<div class='info'><strong>🔍 MySQL Version: {$versao}</strong></div>";
    
    // Escolher arquivo SQL baseado na versão
    $arquivoSql = 'database.sql';
    if ($versaoMysql < 5.7) {
        $arquivoSql = 'database_compativel.sql';
        echo "<div class='info'><strong>📋 Usando SQL compatível para MySQL < 5.7</strong></div>";
    }
    
    // Ler o arquivo SQL
    $sql = file_get_contents(__DIR__ . '/' . $arquivoSql);
    
    if (!$sql) {
        throw new Exception("Arquivo {$arquivoSql} não encontrado");
    }
    
    echo "<div class='info'><strong>📂 Arquivo {$arquivoSql} carregado com sucesso</strong></div>";
    
    // Dividir o SQL em comandos individuais
    $comandos = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "<div class='info'><strong>🔧 Executando " . count($comandos) . " comandos SQL...</strong></div>";
    
    $sucessos = 0;
    $erros = 0;
    
    foreach ($comandos as $index => $comando) {
        if (empty($comando) || strpos($comando, '--') === 0) {
            continue;
        }
        
        try {
            $stmt = $pdo->prepare($comando);
            $resultado = $stmt->execute();
            
            if ($resultado) {
                $sucessos++;
                
                // Mostrar comando executado (apenas primeiras palavras para não poluir)
                $palavras = explode(' ', $comando);
                $inicio = implode(' ', array_slice($palavras, 0, 6));
                echo "<div class='success'>✅ Comando " . ($index + 1) . ": {$inicio}...</div>";
            } else {
                $erros++;
                $errorInfo = $stmt->errorInfo();
                echo "<div class='error'>❌ Comando " . ($index + 1) . " falhou: " . ($errorInfo[2] ?? 'Erro desconhecido') . "</div>";
                echo "<div class='error'>SQL: " . substr($comando, 0, 100) . "...</div>";
            }
            
        } catch (Exception $e) {
            $erros++;
            $palavras = explode(' ', $comando);
            $inicio = implode(' ', array_slice($palavras, 0, 6));
            echo "<div class='error'>❌ Erro no comando " . ($index + 1) . " '{$inicio}...': " . $e->getMessage() . "</div>";
            echo "<div class='error'>SQL completo: <code>" . htmlspecialchars($comando) . "</code></div>";
        }
    }
    
    echo "<hr>";
    echo "<div class='info'><strong>📊 Resumo da Instalação:</strong></div>";
    echo "<div class='success'>✅ Comandos executados com sucesso: {$sucessos}</div>";
    
    if ($erros > 0) {
        echo "<div class='error'>❌ Comandos com erro: {$erros}</div>";
    }
    
    // Verificar se as tabelas foram criadas
    echo "<hr>";
    echo "<div class='info'><strong>🔍 Verificando tabelas criadas:</strong></div>";
    
    $tabelas = ['entregas_pulseiras', 'pulseiras_config'];
    
    foreach ($tabelas as $tabela) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '{$tabela}'");
            $existe = $stmt->fetch();
            
            if ($existe) {
                // Contar registros
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM {$tabela}");
                $total = $stmt->fetch()['total'];
                
                echo "<div class='success'>✅ Tabela '{$tabela}' criada com sucesso ({$total} registros)</div>";
            } else {
                echo "<div class='error'>❌ Tabela '{$tabela}' não foi criada</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Erro ao verificar tabela '{$tabela}': " . $e->getMessage() . "</div>";
        }
    }
    
    // Testar a classe EntregaPulseiras
    echo "<hr>";
    echo "<div class='info'><strong>🧪 Testando sistema:</strong></div>";
    
    try {
        $estatisticas = $entregaPulseiras->obterEstatisticasHoje();
        echo "<div class='success'>✅ Sistema funcionando - Entregas hoje: " . $estatisticas['total_entregas'] . "</div>";
        
        // Testar validação de QR Code
        $teste = $entregaPulseiras->validarFormatoQRCode('10.123456789');
        if ($teste['valido']) {
            echo "<div class='success'>✅ Validação de QR Code funcionando</div>";
        } else {
            echo "<div class='error'>❌ Problema na validação de QR Code</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erro ao testar sistema: " . $e->getMessage() . "</div>";
    }
    
    if ($erros == 0) {
        echo "<hr>";
        echo "<div class='success text-center' style='font-size: 1.2em; padding: 1.5rem;'>";
        echo "<strong>🎉 INSTALAÇÃO CONCLUÍDA COM SUCESSO!</strong><br>";
        echo "<a href='index.php' class='btn btn-primary mt-3' style='background: #9b59b6; border: none;'>";
        echo "<i class='fas fa-hand-paper'></i> Acessar Sistema de Pulseiras";
        echo "</a>";
        echo "</div>";
    } else {
        echo "<hr>";
        echo "<div class='error text-center' style='font-size: 1.2em; padding: 1.5rem;'>";
        echo "<strong>⚠️ INSTALAÇÃO CONCLUÍDA COM ALGUNS ERROS</strong><br>";
        echo "Verifique os erros acima e tente novamente se necessário.<br>";
        echo "<a href='index.php' class='btn btn-warning mt-3'>";
        echo "<i class='fas fa-hand-paper'></i> Tentar Acessar Sistema";
        echo "</a>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'><strong>❌ Erro crítico na instalação:</strong><br>" . $e->getMessage() . "</div>";
    echo "<div class='info'>Verifique se:</div>";
    echo "<ul>";
    echo "<li>O arquivo database.sql existe na mesma pasta</li>";
    echo "<li>As configurações de banco estão corretas no config.php</li>";
    echo "<li>O usuário do banco tem permissões para criar tabelas</li>";
    echo "</ul>";
}

echo "            </div>
        </div>
    </div>
    
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
</body>
</html>";
?>
