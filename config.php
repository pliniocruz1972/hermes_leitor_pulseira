<?php
/**
 * Configuração do Sistema de Entrega de Pulseiras
 * Salão do Turismo
 */

// Inclui a configuração principal
require_once '../config.php';

/**
 * Classe para gerenciar a entrega de pulseiras
 */
class EntregaPulseiras {
    private $pdo;
    
    public function __construct() {
        $this->pdo = conectarBanco();
    }
    
    /**
     * Método público para acessar o PDO (para debug)
     */
    public function getPdo() {
        return $this->pdo;
    }
    
    /**
     * Valida o formato do QRCode conforme as regras definidas
     */
    public function validarFormatoQRCode($qrcode) {
        // Remove espaços em branco
        $qrcode = trim($qrcode);

        // Se for apenas números, tratar como tipo 10 (código de inscrição)
        if (ctype_digit($qrcode)) {
            return [
                'valido' => true,
                'tipo' => 10,
                'motivo' => 'QR Code numérico tratado como tipo 10',
                'codigo_inscricao' => $qrcode,
                'inscricao_id' => null
            ];
        }

        // Verifica se contém um ponto (formato esperado: XX.YYYYYYYY)
        if (strpos($qrcode, '.') === false) {
            return [
                'valido' => false,
                'motivo' => 'Formato de QR Code inválido (deve conter um ponto ou ser apenas números)',
                'tipo' => null,
                'inscricao_id' => null
            ];
        }

        // Divide o código pelo ponto
        $partes = explode('.', $qrcode, 2);

        if (count($partes) != 2 || empty($partes[1])) {
            return [
                'valido' => false,
                'motivo' => 'Formato de QR Code inválido (formato esperado: XX.YYYYYYYY)',
                'tipo' => null,
                'inscricao_id' => null
            ];
        }

        $tipoQr = $partes[0];
        $inscricaoId = $partes[1];

        // Valida se o tipo é numérico e se a inscrição ID é válida
        if (!is_numeric($tipoQr) || !is_numeric($inscricaoId)) {
            return [
                'valido' => false,
                'motivo' => 'Tipo ou ID de inscrição não são numéricos',
                'tipo' => null,
                'inscricao_id' => null
            ];
        }

        $tipoQr = intval($tipoQr);
        $inscricaoId = intval($inscricaoId);

        // Determina se pode pegar pulseira baseado no tipo
        switch ($tipoQr) {
            case 10:
                return [
                    'valido' => true,
                    'tipo' => 10,
                    'motivo' => 'QR Code tipo 10 - Pode retirar pulseira',
                    'codigo_inscricao' => $inscricaoId,
                    'inscricao_id' => null // Será obtido após busca no banco
                ];

            case 20:
                return [
                    'valido' => true,
                    'tipo' => 20,
                    'motivo' => 'QR Code tipo 20 - Pode retirar pulseira',
                    'codigo_inscricao' => null,
                    'participante_id' => $inscricaoId
                ];

            case 30:
                return [
                    'valido' => false,
                    'tipo' => 30,
                    'motivo' => 'QR Code tipo 30 - Não pega pulseira',
                    'codigo_inscricao' => null,
                    'inscricao_id' => null
                ];

            case 40:
                return [
                    'valido' => false,
                    'tipo' => 40,
                    'motivo' => 'QR Code tipo 40 - Não pega pulseira',
                    'codigo_inscricao' => null,
                    'inscricao_id' => null
                ];

            default:
                return [
                    'valido' => false,
                    'tipo' => $tipoQr,
                    'motivo' => "QR Code tipo {$tipoQr} - Tipo não reconhecido",
                    'codigo_inscricao' => null,
                    'inscricao_id' => null
                ];
        }
    }
    
    /**
     * Busca dados do participante baseado no tipo de QRCode
     */
    public function buscarParticipante($validacao) {
        if (!$validacao['valido']) {
            return null;
        }
        
        try {
            if ($validacao['tipo'] == 10) {
                // Tipo 10: buscar por código de inscrição
                $codigoInscricao = $validacao['codigo_inscricao'];
                $sql = "SELECT * FROM inscricoes WHERE codigo_inscricao = ? OR codigo_inscricao = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$codigoInscricao, strval($codigoInscricao)]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
                
            } elseif ($validacao['tipo'] == 20) {
                // Tipo 20: buscar por ID do participante com 8 dígitos
                $participanteId = str_pad($validacao['participante_id'], 8, '0', STR_PAD_LEFT);
                $sql = "SELECT * FROM participantes WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$participanteId]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Erro ao buscar participante: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verifica se já retirou pulseira hoje
     */
    public function verificarEntregaHoje($inscricaoId) {
        try {
            $sql = "SELECT * FROM entregas_pulseiras 
                    WHERE inscricao_id = ? 
                    AND DATE(data_entrega) = CURDATE() 
                    ORDER BY data_entrega DESC 
                    LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$inscricaoId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erro ao verificar entrega hoje: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra a entrega de pulseira
     */
    public function registrarEntrega($inscricaoId, $qrcode, $tipoQr, $observacoes = null) {
        try {
            // Verificar novamente se já não retirou hoje (segurança dupla)
            $jaRetirou = $this->verificarEntregaHoje($inscricaoId);
            if ($jaRetirou) {
                return false; // Já retirou hoje
            }
            
            $sql = "INSERT INTO entregas_pulseiras 
                    (inscricao_id, qrcode_lido, tipo_qr, data_entrega, status, observacoes, ip_dispositivo, user_agent) 
                    VALUES (?, ?, ?, NOW(), 'entregue', ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $resultado = $stmt->execute([
                $inscricaoId,
                $qrcode,
                $tipoQr,
                $observacoes,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            return $resultado;
            
        } catch (Exception $e) {
            error_log("Erro ao registrar entrega: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se pode retirar pulseira
     */
    public function verificarPulseira($qrcode) {
        // 1. Validar formato do QRCode
        $validacao = $this->validarFormatoQRCode($qrcode);
        
        if (!$validacao['valido']) {
            return [
                'pode_retirar' => false,
                'motivo' => $validacao['motivo'],
                'tipo_qr' => $validacao['tipo'],
                'dados' => null,
                'ja_retirou' => false
            ];
        }
        
        // 2. Buscar dados do participante
        $dadosParticipante = $this->buscarParticipante($validacao);
        
        if (!$dadosParticipante) {
            return [
                'pode_retirar' => false,
                'motivo' => 'Participante não encontrado no sistema',
                'tipo_qr' => $validacao['tipo'],
                'dados' => null,
                'ja_retirou' => false
            ];
        }
        
        // 3. Verificar se já retirou hoje
        // Para tipo 10: usar o ID da tabela inscricoes
        // Para tipo 20: usar o ID da tabela participantes (já padronizado com 8 dígitos)
        if ($validacao['tipo'] == 10) {
            $inscricaoId = $dadosParticipante['id']; // ID da tabela inscricoes
        } else {
            $inscricaoId = $dadosParticipante['id']; // ID da tabela participantes
        }
        $entregaHoje = $this->verificarEntregaHoje($inscricaoId);
        
        if ($entregaHoje) {
            return [
                'pode_retirar' => false,
                'motivo' => 'Pulseira já retirada hoje',
                'tipo_qr' => $validacao['tipo'],
                'dados' => $dadosParticipante,
                'ja_retirou' => true,
                'data_entrega' => $entregaHoje['data_entrega']
            ];
        }
        
        // 4. Pode retirar
        return [
            'pode_retirar' => true,
            'motivo' => 'Pode retirar pulseira',
            'tipo_qr' => $validacao['tipo'],
            'dados' => $dadosParticipante,
            'ja_retirou' => false,
            'inscricao_id' => $inscricaoId
        ];
    }
    
    /**
     * Processo completo de entrega de pulseira
     */
    public function entregarPulseira($qrcode) {
        // Verificar se pode retirar
        $verificacao = $this->verificarPulseira($qrcode);
        
        if (!$verificacao['pode_retirar']) {
            return $verificacao;
        }
        
        // Registrar a entrega
        $sucesso = $this->registrarEntrega(
            $verificacao['inscricao_id'],
            $qrcode,
            $verificacao['tipo_qr'],
            'Pulseira entregue via sistema mobile'
        );
        
        if ($sucesso) {
            $verificacao['entrega_registrada'] = true;
            $verificacao['motivo'] = 'Pulseira entregue com sucesso!';
            return $verificacao;
        } else {
            $verificacao['pode_retirar'] = false;
            $verificacao['motivo'] = 'Erro ao registrar entrega da pulseira';
            return $verificacao;
        }
    }
    
    /**
     * Obter estatísticas do dia
     */
    public function obterEstatisticasHoje() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_entregas,
                        COUNT(CASE WHEN tipo_qr = 10 THEN 1 END) as tipo_10,
                        COUNT(CASE WHEN tipo_qr = 20 THEN 1 END) as tipo_20
                    FROM entregas_pulseiras 
                    WHERE DATE(data_entrega) = CURDATE()";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erro ao obter estatísticas: " . $e->getMessage());
            return ['total_entregas' => 0, 'tipo_10' => 0, 'tipo_20' => 0];
        }
    }
    
    /**
     * Listar últimas entregas
     */
    public function listarUltimasEntregas($limite = 10) {
        try {
            $sql = "SELECT ep.*, 
                           COALESCE(i.nome, p.nome) as nome_participante,
                           ep.data_entrega
                    FROM entregas_pulseiras ep
                    LEFT JOIN inscricoes i ON ep.inscricao_id = i.id
                    LEFT JOIN participantes p ON ep.inscricao_id = p.id
                    WHERE DATE(ep.data_entrega) = CURDATE()
                    ORDER BY ep.data_entrega DESC 
                    LIMIT ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$limite]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erro ao listar últimas entregas: " . $e->getMessage());
            return [];
        }
    }
}
?>
