# 🎟️ Sistema de Entrega de Pulseiras

Sistema web móvel para controle automatizado da entrega de pulseiras em eventos, com validação por QR Code e controle de uma entrega por dia por participante.

## 📋 Descrição

O **Sistema de Entrega de Pulseiras** é uma aplicação web otimizada para dispositivos móveis que permite o controle eficiente da distribuição de pulseiras em eventos. O sistema utiliza leitura de QR Code via câmera do dispositivo ou entrada manual, garantindo que cada participante retire apenas uma pulseira por dia.

Desenvolvido especificamente para o **Salão do Turismo**, o sistema oferece interface intuitiva, relatórios em tempo real e histórico completo de entregas.

## ✨ Funcionalidades

### 📱 **Interface Mobile-First**
- Design responsivo otimizado para smartphones
- Interface touch-friendly para operação rápida
- Funcionamento offline-first com sincronização automática

### 🔍 **Leitura de QR Code**
- **Câmera integrada**: Leitura automática via câmera do dispositivo
- **Entrada manual**: Campo para digitação manual do código
- **Validação em tempo real**: Verificação instantânea da validade do código

### 🎯 **Controles de Entrega**
- **Uma pulseira por dia**: Controle rígido para evitar entregas duplicadas
- **Múltiplos tipos de QR**: Suporte a diferentes formatos de códigos
- **Confirmação obrigatória**: Dupla confirmação antes da entrega
- **Registro detalhado**: Log completo com data, hora e detalhes

### 📊 **Dashboard e Relatórios**
- **Estatísticas em tempo real**: Contadores ao vivo das entregas
- **Separação por tipo**: Métricas detalhadas por categoria de QR Code
- **Histórico recente**: Lista das últimas entregas realizadas
- **Relatórios gerenciais**: Dados consolidados para análise

### 🔒 **Segurança e Auditoria**
- **Registro de IP**: Rastreamento do dispositivo de origem
- **User Agent**: Identificação do navegador/dispositivo
- **Log de ações**: Histórico completo para auditoria
- **Proteção contra duplicatas**: Controles robustos de integridade

## 🛠️ Tecnologias

- **Backend**: PHP 7.4+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Banco de Dados**: MySQL 5.7+ / MariaDB 10.3+
- **UI Framework**: Bootstrap 5.3
- **Ícones**: Font Awesome 6.4
- **QR Code Reader**: jsQR Library
- **PWA**: Service Worker para funcionamento offline

## 📝 Tipos de QR Code Suportados

| Formato | Exemplo | Status | Descrição |
|---------|---------|--------|-----------|
| `10.CODIGO` | `10.3731452383` | ✅ **Pode retirar** | Código de inscrição válido |
| `20.ID` | `20.12345678` | ✅ **Pode retirar** | ID do participante (8 dígitos) |
| `30.VALOR` | `30.1234567` | ❌ **Não pode retirar** | Código de palestrar/organização |
| `40.VALOR` | `40.9876543` | ❌ **Não pode retirar** | Código de expositor/patrocinador |

## 🚀 Instalação

### Pré-requisitos
```bash
- PHP 7.4 ou superior
- MySQL 5.7+ ou MariaDB 10.3+
- Extensões PHP: PDO, PDO_MySQL, JSON
- Navegador com suporte a getUserMedia() para câmera
```

### Instalação Rápida

1. **Clone o repositório**
```bash
git clone https://github.com/seu-usuario/sistema-pulseiras.git
cd sistema-pulseiras/pulseira
```

2. **Configure o banco de dados**
```bash
# Edite o arquivo de configuração
cp config.sample.php config.php
# Configure as credenciais do banco
```

3. **Execute a instalação automática**
```bash
# Acesse via navegador
http://seu-servidor/pulseira/install.php
```

4. **Inicie o sistema**
```bash
# Acesse a interface principal
http://seu-servidor/pulseira/
```

### Instalação Manual

1. **Criar as tabelas do banco**
```sql
-- Execute o script SQL incluído
mysql -u usuario -p database < database.sql
```

2. **Configurar permissões**
```bash
# Definir permissões adequadas para o diretório
chmod 755 pulseira/
chmod 644 pulseira/*.php
```

## 🗄️ Estrutura do Banco de Dados

### Tabela: `entregas_pulseiras`
```sql
CREATE TABLE IF NOT EXISTS `entregas_pulseiras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inscricao_id` int(11) NOT NULL COMMENT 'ID da inscrição (extraído do QRCode após o ponto)',
  `qrcode_lido` text NOT NULL COMMENT 'QRCode completo lido (ex: 10.3731452383)',
  `tipo_qr` int(2) NOT NULL COMMENT 'Tipo do QRCode: 10=código inscrição, 20=id participante',
  `data_entrega` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_entrega_dia` date GENERATED ALWAYS AS (DATE(`data_entrega`)) STORED,
  `status` enum('entregue','pendente') DEFAULT 'entregue',
  `observacoes` text NULL,
  `ip_dispositivo` varchar(45) NULL,
  `user_agent` text NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_inscricao_id` (`inscricao_id`),
  INDEX `idx_data_entrega` (`data_entrega`),
  INDEX `idx_status` (`status`),
  INDEX `idx_tipo_qr` (`tipo_qr`),
  INDEX `idx_data_entrega_dia` (`data_entrega_dia`),
  UNIQUE KEY `unique_inscricao_dia` (`inscricao_id`, `data_entrega_dia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Registra todas as entregas de pulseiras do evento';
```

### Tabela: `pulseiras_config`
```sql
CREATE TABLE IF NOT EXISTS `pulseiras_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sistema_ativo` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=sistema ativo, 0=inativo',
  `uma_pulseira_por_dia` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=apenas uma pulseira por dia por pessoa',
  `tipos_permitidos` varchar(50) NOT NULL DEFAULT '10,20' COMMENT 'Tipos de QR permitidos (separados por vírgula)',
  `data_atualizacao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `usuario_responsavel` varchar(100) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Configurações do sistema de entrega de pulseiras';
```

## 📱 Uso do Sistema

### Para Operadores

1. **Iniciar Leitura**
   - Abra o sistema no smartphone
   - Clique em "Iniciar Câmera" ou digite manualmente
   - Aponte a câmera para o QR Code

2. **Verificar Resultado**
   - ✅ **Verde**: Pode retirar pulseira
   - ❌ **Vermelho**: Não pode retirar (já retirou ou código inválido)

3. **Confirmar Entrega**
   - Se aprovado, clique em "Confirmar Entrega"
   - Sistema registra automaticamente a operação

### Para Administradores

1. **Acompanhar Estatísticas**
   - Dashboard com contadores em tempo real
   - Gráficos de distribuição por tipo de QR

2. **Verificar Histórico**
   - Lista das últimas entregas
   - Detalhes de cada operação realizada

3. **Diagnósticos**
   - Acesse `/diagnostico.php` para verificação do sistema
   - Relatórios técnicos e logs de erro

## 🔧 Configuração

### Parâmetros do Sistema
```php
// Em pulseiras_config
$sistema_ativo = true;              // Ativar/desativar sistema
$uma_pulseira_por_dia = true;       // Controle de uma por dia
$tipos_permitidos = '10,20';        // Tipos de QR aceitos
```

### Personalização
```php
// Em config.php
$titulo_sistema = 'Sistema de Pulseiras';
$evento_nome = 'Salão do Turismo 2024';
$cores_tema = ['primary' => '#007bff', 'success' => '#28a745'];
```

## 📊 API Endpoints

### POST `/`
**Verificar QR Code**
```json
{
  "action": "verificar_qr",
  "qrcode": "10.3731452383"
}
```

**Resposta de Sucesso:**
```json
{
  "success": true,
  "pode_retirar": true,
  "message": "PODE RETIRAR PULSEIRA",
  "participante": "Nome do Participante",
  "tipo_qr": 10,
  "qrcode": "10.3731452383"
}
```

### POST `/`
**Confirmar Entrega**
```json
{
  "action": "confirmar_entrega",
  "qrcode": "10.3731452383"
}
```

## 🤝 Contribuição

1. **Fork o projeto**
2. **Crie uma branch** (`git checkout -b feature/nova-funcionalidade`)
3. **Commit suas mudanças** (`git commit -am 'Adiciona nova funcionalidade'`)
4. **Push para a branch** (`git push origin feature/nova-funcionalidade`)
5. **Abra um Pull Request**

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo `LICENSE` para mais detalhes.

## 👨‍💻 Desenvolvimento

**Sistema de Entrega de Pulseiras v2.0**  
Desenvolvido para otimizar o controle de pulseiras em eventos.

### Roadmap
- [ ] PWA completa com cache offline
- [ ] Sincronização automática em background
- [ ] Dashboard administrativo expandido
- [ ] Relatórios em PDF/Excel
- [ ] Integração com sistemas de credenciamento
- [ ] Suporte a múltiplos eventos simultâneos

---

💡 **Dica**: Para melhor experiência, utilize em dispositivos com boa qualidade de câmera e conexão estável à internet.
- `user_agent` - Navegador utilizado

#### Tabela: `pulseiras_config`
- `id` - ID da configuração
- `sistema_ativo` - Se o sistema está ativo
- `uma_pulseira_por_dia` - Controle de uma pulseira por dia
- `tipos_permitidos` - Tipos de QR permitidos
- `data_atualizacao` - Última atualização
- `usuario_responsavel` - Usuário responsável

## Instalação

### 1. Preparação
```bash
# Copie os arquivos para a pasta pulseira/
# Certifique-se que o config.php principal está configurado
```

### 2. Instalação Automática
Acesse: `http://seudominio.com/pulseira/install.php`

O script irá:
- ✅ Criar as tabelas necessárias
- ✅ Inserir configurações padrão
- ✅ Testar o sistema
- ✅ Validar a instalação

### 3. Verificação
Acesse: `http://seudominio.com/pulseira/`

## Interface do Usuário

### 🎨 Design Responsivo
- **Cores**: Roxo (#9b59b6) como cor primária
- **Layout**: Baseado no sistema de controle de sala
- **Mobile First**: Otimizado para smartphones
- **Bootstrap 5**: Framework CSS moderno

### 📱 Fluxo de Uso
1. **Iniciar câmera** ou usar campo manual
2. **Ler QR Code** do participante
3. **Verificar dados** exibidos na tela
4. **Confirmar entrega** se liberado
5. **Visualizar confirmação** com horário

### 🎯 Estados Visuais
- **🟡 Amarelo**: Aguardando confirmação
- **🟢 Verde**: Pulseira entregue com sucesso
- **🔴 Vermelho**: Não pode retirar / erro
- **Efeitos**: Piscar fundo conforme resultado

## Regras de Negócio

### ✅ Pode Retirar Pulseira
- QR Code tipo **10** (código de inscrição)
- QR Code tipo **20** (ID do participante)
- Participante encontrado no sistema
- Não retirou pulseira hoje

### ❌ Não Pode Retirar Pulseira
- QR Code tipo **30** ou **40**
- Participante não encontrado
- Já retirou pulseira no dia atual
- Formato de QR Code inválido

### 🔒 Controles de Segurança
- **Único por dia**: Constraint UNIQUE na base de dados
- **Log completo**: Todos os acessos são registrados
- **Validação dupla**: Cliente e servidor validam
- **Histórico imutável**: Registros não podem ser alterados

## Tecnologias Utilizadas

### Frontend
- **HTML5**: Estrutura semântica
- **CSS3**: Estilos e animações
- **JavaScript/jQuery**: Interatividade
- **Bootstrap 5**: Framework CSS
- **Font Awesome**: Ícones
- **QR Code Reader**: Biblioteca para leitura de QR

### Backend
- **PHP 7.4+**: Linguagem principal
- **PDO**: Acesso ao banco de dados
- **MySQL/MariaDB**: Banco de dados
- **JSON**: Comunicação AJAX

## Manutenção

### 📊 Monitoramento
- Acompanhe as estatísticas em tempo real
- Verifique os logs de entrega regularmente
- Monitor performance da leitura de QR

### 🔧 Backup
```sql
-- Backup diário recomendado
mysqldump -u usuario -p base_dados entregas_pulseiras > backup_pulseiras_$(date +%Y%m%d).sql
```

### 🚨 Troubleshooting

#### Problema: Câmera não funciona
- **Verificar**: HTTPS é obrigatório para câmera
- **Solução**: Use campo manual como alternativa

#### Problema: QR Code não reconhecido
- **Verificar**: Formato XX.YYYYYYYY
- **Solução**: Validar dados de entrada

#### Problema: Erro de duplicata
- **Causa**: Participante já retirou hoje
- **Solução**: Sistema bloqueia automaticamente

## Compatibilidade

### 📱 Dispositivos Móveis
- ✅ Android 6.0+
- ✅ iOS 10.0+
- ✅ Tablets
- ✅ Smartphones

### 🌐 Navegadores
- ✅ Chrome 70+
- ✅ Firefox 65+
- ✅ Safari 12+
- ✅ Edge 79+

## Segurança

### 🔐 Medidas Implementadas
- **Validação servidor**: Todas as regras no backend
- **Prepared Statements**: Proteção contra SQL Injection
- **Sanitização**: Limpeza de dados de entrada
- **Log de auditoria**: Rastreamento completo

### 🛡️ Recomendações
- Use HTTPS em produção
- Configure firewall adequadamente
- Monitore logs de acesso
- Backup regular dos dados

## Suporte

Para problemas ou dúvidas:
1. Verifique a documentação completa
2. Consulte os logs do sistema
3. Execute o script de diagnóstico
4. Entre em contato com o suporte técnico

---

**Sistema de Entrega de Pulseiras v1.0**  
*Salão do Turismo - 2025*
