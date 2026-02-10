# ZAP Events Tutor

Plugin WordPress que captura e padroniza eventos do Tutor LMS para automações externas via Zapier, n8n, Make e outras plataformas.

## 📋 Descrição

O **ZAP Events Tutor** é uma camada de integração profissional que monitora ações dos alunos no Tutor LMS e dispara eventos padronizados que podem ser consumidos por ferramentas de automação externas.

### Principais Funcionalidades

- ✅ **13 Eventos Rastreados**: Monitora todo o ciclo do aluno
- 🔗 **Webhooks**: Integração com Zapier, n8n, Make, etc
- 📊 **Dashboard de Estatísticas**: Visualize métricas em tempo real
- 🗃️ **Sistema de Logs**: Histórico completo com filtros avançados
- 🔄 **Fila de Processamento**: Background jobs para alto volume
- 🛡️ **API REST**: Acesso programático aos dados
- 🧹 **Limpeza Automática**: Gerenciamento de logs antigos
- ⚡ **Performance**: Retry automático em webhooks

## 📦 Requisitos

- WordPress 5.8 ou superior
- PHP 7.4 ou superior
- Tutor LMS (plugin ativo)

## 🚀 Instalação

1. Faça upload da pasta `zap-tutor-events` para `/wp-content/plugins/`
2. Ative o plugin através do menu 'Plugins' no WordPress
3. Acesse **ZAP Tutor Events** no menu do admin
4. Configure seus webhooks em **Configurações**

## 🎯 Eventos Disponíveis

### Usuário
- `tutor_student_signup` - Aluno cadastrado
- `tutor_student_login` - Aluno logado

### Curso
- `tutor_course_enrolled` - Aluno matriculado no curso
- `tutor_enrol_status_changed` - Status da matrícula alterado
- `tutor_course_progress_50` - Curso 50% concluído
- `tutor_course_completed` - Curso 100% concluído

### Conteúdo
- `tutor_lesson_completed` - Aula concluída
- `tutor_assignment_submitted` - Trabalho enviado

### Quiz
- `tutor_quiz_started` - Quiz iniciado
- `tutor_quiz_finished` - Quiz finalizado

### Pagamentos
- `tutor_order_payment_status_changed` - Status do pagamento alterado

### Sistema
- `zap_test_event` - Evento de teste

## ⚙️ Configuração

### Webhook

1. Acesse **ZAP Tutor Events > Configurações**
2. Insira a URL do seu webhook (Zapier, n8n, Make, etc)
3. Selecione quais eventos deseja enviar
4. Configure timeout e ative logs de webhook

**Formato do Payload:**
```json
{
  "event": "tutor_course_completed",
  "user_id": 123,
  "context": {
    "course_id": 456,
    "progress": 100
  },
  "timestamp": "2024-02-10 15:30:00",
  "site_url": "https://seu-site.com"
}
```

### Logs

Configure retenção de logs:
- 7, 30, 60, 90 dias
- Infinito (não limpar)

Limpeza automática diária ou manual via botão.

### Fila de Processamento

Ative o processamento em background para:
- Alto volume de eventos
- Evitar lentidão no site
- Processamento a cada minuto via WP Cron

### API REST

Chave de API gerada automaticamente. Use no header `X-API-Key`.

**Endpoints Disponíveis:**

#### GET /wp-json/zap-events/v1/logs
Lista eventos com filtros opcionais.

**Parâmetros:**
- `per_page` (padrão: 50)
- `page` (padrão: 1)
- `event_key` - Filtrar por tipo
- `user_id` - Filtrar por usuário
- `date_from` - Data inicial (YYYY-MM-DD)
- `date_to` - Data final (YYYY-MM-DD)

**Exemplo:**
```bash
curl -H "X-API-Key: sua-chave-aqui" \
  "https://seu-site.com/wp-json/zap-events/v1/logs?per_page=10"
```

#### GET /wp-json/zap-events/v1/stats
Estatísticas dos últimos N dias.

**Parâmetros:**
- `days` (padrão: 30)

#### GET /wp-json/zap-events/v1/events
Lista todos os tipos de eventos disponíveis.

#### POST /wp-json/zap-events/v1/test
Dispara um evento de teste.

**Parâmetros:**
- `user_id` (padrão: 1)

## 📊 Dashboard

Visualize métricas importantes:
- Total de eventos por tipo
- Linha do tempo de eventos
- Usuários mais ativos
- Taxa de conclusão de cursos
- Status de webhooks (sucesso/falha)

Período configurável: 7, 30, 60 ou 90 dias.

## 🔍 Logs Avançados

Filtros disponíveis:
- Por tipo de evento
- Por usuário
- Por período de data
- Paginação (50, 100, 200 registros)
- **Exportação CSV**

## 🐛 Modo Debug

Ative logs detalhados adicionando ao `wp-config.php`:

```php
define('ZAP_EVENTS_DEBUG', true);
```

Logs serão salvos em `wp-content/debug.log`.

## 🔗 Integração com Automações

### Zapier

1. Crie um novo Zap
2. Use "Webhooks by Zapier" como trigger
3. Escolha "Catch Hook"
4. Cole a URL do webhook em **Configurações**
5. Dispare um evento de teste
6. Configure suas ações

### n8n

1. Adicione um nó "Webhook"
2. Configure como "POST"
3. Cole a URL em **Configurações**
4. Teste o webhook
5. Processe os dados conforme necessário

### Make (Integromat)

1. Adicione um módulo "Webhooks > Custom Webhook"
2. Copie a URL gerada
3. Cole em **Configurações**
4. Adicione módulos para processar eventos

## 🔄 Como Funciona

```
Tutor LMS Event
      ↓
Events Class (captura)
      ↓
Dispatcher (processa)
      ↓
      ├→ Logger (salva no banco)
      ├→ Webhook (envia via HTTP)
      └→ WordPress Action (outros plugins)
```

### Retry Automático

Webhooks com falha são automaticamente tentados novamente:
- 1ª tentativa: Imediata
- 2ª tentativa: Após 2 segundos
- 3ª tentativa: Após 4 segundos

## 📁 Estrutura de Arquivos

```
zap-tutor-events/
├── includes/
│   ├── class-plugin.php         # Bootstrap principal
│   ├── class-events.php         # Hooks do Tutor LMS
│   ├── class-dispatcher.php     # Despachador de eventos
│   ├── class-logger.php         # Sistema de logs
│   ├── class-admin.php          # Páginas admin
│   ├── class-admin-test.php     # Teste de eventos
│   ├── class-webhook.php        # Sistema de webhooks
│   ├── class-settings.php       # Página de configurações
│   ├── class-queue.php          # Fila de processamento
│   ├── class-dashboard.php      # Dashboard de stats
│   └── class-api.php            # REST API
├── assets/
│   └── admin.css                # Estilos do admin
├── zap-tutor-events.php         # Arquivo principal
├── uninstall.php                # Limpeza na desinstalação
└── README.md                    # Esta documentação
```

## 🔧 Funções de Desenvolvedor

### Disparar Evento Customizado

```php
do_action('zap_evento', [
    'event'     => 'meu_evento_custom',
    'user_id'   => 123,
    'context'   => ['chave' => 'valor'],
    'timestamp' => time(),
]);
```

### Filtrar Lista de Eventos

```php
add_filter('zap_tutor_events_list', function($events) {
    $events['meu_evento'] = 'Meu Evento Customizado';
    return $events;
});
```

### Modificar Payload do Webhook

```php
add_filter('zap_webhook_payload', function($payload, $event_key) {
    $payload['custom_field'] = 'valor';
    return $payload;
}, 10, 2);
```

## ❓ FAQ

### Os webhooks estão lentos?

Ative a **Fila de Processamento** em **Configurações > Avançadas**.

### Como limpo logs antigos?

Configure a retenção em **Configurações > Logs** ou use o botão de limpeza manual.

### Posso desabilitar alguns eventos?

Sim, em **Configurações > Webhook** selecione apenas os eventos desejados.

### O webhook não está enviando?

1. Verifique a URL em **Configurações**
2. Veja os logs em **Logs de Webhooks**
3. Ative o modo debug
4. Teste com o endpoint `/test` da API

### Como vejo os logs de webhook?

Eles são salvos na tabela `wp_zap_webhook_logs`. Futuramente terão uma página dedicada.

## 📝 Changelog

### 1.1.0 - 2024-02-10
- ✨ Adicionado evento `tutor_course_completed`
- ✨ Adicionado evento `tutor_assignment_submitted`
- ✨ Sistema de Webhooks com retry automático
- ✨ Página de Configurações
- ✨ Filtros avançados nos logs
- ✨ Exportação de logs para CSV
- ✨ Dashboard de estatísticas
- ✨ Sistema de fila para background jobs
- ✨ API REST completa
- ✨ Modo debug
- ✨ Limpeza automática de logs
- ✨ Validação de dependências (PHP 7.4+, WP 5.8+)
- 📚 Documentação completa
- 🛡️ Melhorias de segurança

### 1.0.0 - 2024-01-15
- 🎉 Versão inicial
- 9 eventos do Tutor LMS
- Sistema básico de logs
- Página de teste de eventos

## 🤝 Suporte

Para suporte, abra uma issue no repositório ou entre em contato com a equipe ZAP Automação.

## 📄 Licença

Este plugin é proprietário e de uso interno. Não distribua sem autorização.

## 👨‍💻 Autor

**ZAP Automação**
- Site: https://seu-site.com
- Email: contato@seu-site.com

---

Desenvolvido com ❤️ para facilitar automações no Tutor LMS
