<?php
/**
 * Diagnóstico do Sistema de Entrega de Pulseiras
 * Salão do Turismo
 */

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Diagnóstico - Sistema de Pulseiras</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { 
            font-family: 'Arial', sans-serif; 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .container { max-width: 1000px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); margin-bottom: 1rem; }
        .header { background: #9b59b6; color: white; padding: 2rem 0; text-align: center; }
        .success { color: #155724; background: #d4edda; padding: 0.75rem; border-radius: 5px; margin: 0.5rem 0; }
        .error { color: #721c24; background: #f8d7da; padding: 0.75rem; border-radius: 5px; margin: 0.5rem 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 0.75rem; border-radius: 5px; margin: 0.5rem 0; }
        .warning { color: #856404; background: #fff3cd; padding: 0.75rem; border-radius: 5px; margin: 0.5rem 0; }
        pre { background: #f8f9fa; padding: 1rem; border-radius: 5px; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class='header'>
        <h1><i class='fas fa-stethoscope'></i> Diagnóstico do Sistema</h1>
        <p>Análise Completa de Funcionamento</p>
    </div>
    
    <div class='container mt-4'>";

// Diagnóstico 1: Informações do Sistema
echo "<div class='card'>
    <div class='card-body'>
        <h4>🖥️ Informações do Sistema</h4>";

echo "<div class='info'><strong>PHP Version:</strong> " . phpversion() . "</div>";
echo "<div class='info'><strong>Server:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "</div>";
echo "<div class='info'><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "</div>";
echo "<div class='info'><strong>Current Directory:</strong> " . __DIR__ . "</div>";

// Verificar extensões PHP necessárias
$extensoes = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
foreach ($extensoes as $ext) {
    if (extension_loaded($ext)) {
        echo "<div class='success'>✅ Extensão {$ext} carregada</div>";
    } else {
        echo "<div class='error'>❌ Extensão {$ext} NÃO carregada</div>";
    }
}

echo "</div></div>";

// Diagnóstico 2: Arquivos do Sistema
echo "<div class='card'>
    <div class='card-body'>
        <h4>📁 Arquivos do Sistema</h4>";

$arquivos = [
    'config.php' => 'Configuração principal',
    'index.php' => 'Interface principal',
    'database.sql' => 'Script de banco (versão nova)',
    'database_compativel.sql' => 'Script de banco (versão compatível)',
    'install.php' => 'Script de instalação',
    'teste.php' => 'Script de teste',
    '../config.php' => 'Configuração geral do sistema'
];

foreach ($arquivos as $arquivo => $descricao) {
    if (file_exists(__DIR__ . '/' . $arquivo)) {
        $tamanho = filesize(__DIR__ . '/' . $arquivo);
        echo "<div class='success'>✅ {$arquivo} ({$descricao}) - {$tamanho} bytes</div>";
    } else {
        echo "<div class='error'>❌ {$arquivo} ({$descricao}) - Arquivo não encontrado</div>";
    }
}

echo "</div></div>";

// Diagnóstico 3: Conexão com Banco
echo "<div class='card'>
    <div class='card-body'>
        <h4>🗄️ Conexão com Banco de Dados</h4>";

try {
    require_once '../config.php';
    echo "<div class='success'>✅ Arquivo config.php carregado</div>";
    
    if (function_exists('conectarBanco')) {
        $pdo = conectarBanco();
        echo "<div class='success'>✅ Função conectarBanco() encontrada</div>";
        
        // Testar conexão
        $stmt = $pdo->query("SELECT VERSION() as version, DATABASE() as database, USER() as user");
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<div class='info'><strong>MySQL Version:</strong> " . $info['version'] . "</div>";
        echo "<div class='info'><strong>Database:</strong> " . $info['database'] . "</div>";
        echo "<div class='info'><strong>User:</strong> " . $info['user'] . "</div>";
        
        // Verificar charset
        $stmt = $pdo->query("SHOW VARIABLES LIKE 'character_set_database'");
        $charset = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<div class='info'><strong>Charset:</strong> " . $charset['Value'] . "</div>";
        
    } else {
        echo "<div class='error'>❌ Função conectarBanco() não encontrada</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erro na conexão: " . $e->getMessage() . "</div>";
}

echo "</div></div>";

// Diagnóstico 4: Estrutura das Tabelas
echo "<div class='card'>
    <div class='card-body'>
        <h4>📋 Estrutura das Tabelas</h4>";

try {
    $tabelas = ['entregas_pulseiras', 'pulseiras_config'];
    
    foreach ($tabelas as $tabela) {
        echo "<h5>Tabela: {$tabela}</h5>";
        
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '{$tabela}'");
            $existe = $stmt->fetch();
            
            if ($existe) {
                echo "<div class='success'>✅ Tabela existe</div>";
                
                // Mostrar estrutura
                $stmt = $pdo->query("DESCRIBE {$tabela}");
                $campos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<table class='table table-sm'>";
                echo "<thead><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr></thead>";
                echo "<tbody>";
                foreach ($campos as $campo) {
                    echo "<tr>";
                    echo "<td>" . $campo['Field'] . "</td>";
                    echo "<td>" . $campo['Type'] . "</td>";
                    echo "<td>" . $campo['Null'] . "</td>";
                    echo "<td>" . $campo['Key'] . "</td>";
                    echo "<td>" . ($campo['Default'] ?? 'NULL') . "</td>";
                    echo "</tr>";
                }
                echo "</tbody></table>";
                
                // Contar registros
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM {$tabela}");
                $total = $stmt->fetch()['total'];
                echo "<div class='info'>📊 Total de registros: {$total}</div>";
                
            } else {
                echo "<div class='error'>❌ Tabela não existe</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Erro ao verificar tabela: " . $e->getMessage() . "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erro geral nas tabelas: " . $e->getMessage() . "</div>";
}

echo "</div></div>";

// Diagnóstico 5: Teste da Classe
echo "<div class='card'>
    <div class='card-body'>
        <h4>🔧 Teste da Classe EntregaPulseiras</h4>";

try {
    require_once 'config.php';
    echo "<div class='success'>✅ Arquivo config.php da pulseira carregado</div>";
    
    if (class_exists('EntregaPulseiras')) {
        echo "<div class='success'>✅ Classe EntregaPulseiras encontrada</div>";
        
        $obj = new EntregaPulseiras();
        echo "<div class='success'>✅ Objeto criado com sucesso</div>";
        
        // Testar métodos
        $metodos = [
            'validarFormatoQRCode',
            'buscarParticipante', 
            'verificarEntregaHoje',
            'registrarEntrega',
            'verificarPulseira',
            'entregarPulseira',
            'obterEstatisticasHoje',
            'listarUltimasEntregas'
        ];
        
        foreach ($metodos as $metodo) {
            if (method_exists($obj, $metodo)) {
                echo "<div class='success'>✅ Método {$metodo}() disponível</div>";
            } else {
                echo "<div class='error'>❌ Método {$metodo}() não encontrado</div>";
            }
        }
        
    } else {
        echo "<div class='error'>❌ Classe EntregaPulseiras não encontrada</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erro ao testar classe: " . $e->getMessage() . "</div>";
}

echo "</div></div>";

// Diagnóstico 6: Teste de QR Codes
echo "<div class='card'>
    <div class='card-body'>
        <h4>📱 Teste de Validação de QR Codes</h4>";

try {
    if (isset($obj)) {
        $qrs_teste = ['10.123456789', '20.12345678', '30.999999999', 'INVALIDO'];
        
        foreach ($qrs_teste as $qr) {
            echo "<h6>QR Code: {$qr}</h6>";
            try {
                $resultado = $obj->validarFormatoQRCode($qr);
                echo "<pre>" . print_r($resultado, true) . "</pre>";
            } catch (Exception $e) {
                echo "<div class='error'>Erro: " . $e->getMessage() . "</div>";
            }
        }
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Erro no teste de QR: " . $e->getMessage() . "</div>";
}

echo "</div></div>";

// Diagnóstico 7: Logs de Erro
echo "<div class='card'>
    <div class='card-body'>
        <h4>📊 Informações de Debug</h4>";

echo "<div class='info'><strong>Error Reporting:</strong> " . ini_get('error_reporting') . "</div>";
echo "<div class='info'><strong>Display Errors:</strong> " . (ini_get('display_errors') ? 'On' : 'Off') . "</div>";
echo "<div class='info'><strong>Log Errors:</strong> " . (ini_get('log_errors') ? 'On' : 'Off') . "</div>";
echo "<div class='info'><strong>Error Log:</strong> " . (ini_get('error_log') ?: 'Default') . "</div>";

echo "</div></div>";

// Botões de ação
echo "<div class='card'>
    <div class='card-body text-center'>
        <h4>🔧 Ações Disponíveis</h4>
        <a href='install.php' class='btn btn-primary me-2' style='background: #9b59b6; border: none;'>
            <i class='fas fa-download'></i> Executar Instalação
        </a>
        <a href='teste.php' class='btn btn-info me-2'>
            <i class='fas fa-vial'></i> Executar Testes
        </a>
        <a href='index.php' class='btn btn-success me-2'>
            <i class='fas fa-hand-paper'></i> Acessar Sistema
        </a>
        <a href='?refresh=1' class='btn btn-secondary'>
            <i class='fas fa-refresh'></i> Atualizar Diagnóstico
        </a>
    </div>
</div>";

echo "</div>
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
</body>
</html>";
?>
