# ZAP WhatsApp Automation

Plugin WordPress para automação de WhatsApp via Evolution API com geração de QR Code local.

## 📦 Instalação

### Para Usuários Finais (Clientes)

#### Opção 1: Via WordPress Admin (Recomendado)
1. Baixar o arquivo `zap-whatsapp-automation.zip`
2. WordPress Admin → Plugins → Adicionar Novo
3. Clicar em "Enviar Plugin"
4. Escolher o arquivo `.zip` baixado
5. Clicar em "Instalar Agora"
6. Clicar em "Ativar Plugin"

#### Opção 2: Via FTP/cPanel
1. Extrair `zap-whatsapp-automation.zip`
2. Fazer upload da pasta para `wp-content/plugins/`
3. WordPress Admin → Plugins → Ativar "ZAP WhatsApp Automation"

✅ **Não é necessário rodar `composer install`**  
✅ **Todas as dependências já estão incluídas**  
✅ **Funciona em qualquer hospedagem**

---

### Para Desenvolvedores

Se você clonar este repositório para desenvolvimento:

```bash
# Clonar repositório
git clone https://github.com/denernaresidasilva/eltuniversity-wp.git
cd wp-content/plugins/zap-whatsapp-automation

# Vendor/ já está incluído, mas se quiser atualizar:
composer update --no-dev --optimize-autoloader
```

#### Atualizando Dependências

```bash
# Atualizar para versões mais recentes
composer update --no-dev --optimize-autoloader

# Commitar mudanças
git add vendor/ composer.lock
git commit -m "chore: update dependencies"
```

---

## 🚀 Funcionalidades

- ✅ Geração de QR Code local (sem depender da Evolution API)
- ✅ Auto-refresh do QR Code (expira em 2 minutos)
- ✅ Detecção automática de conexão
- ✅ Timer visual de expiração
- ✅ Botão de download do QR Code
- ✅ Interface moderna e responsiva
- ✅ Sistema de fila para envio de mensagens
- ✅ Métricas e relatórios detalhados
- ✅ Logs de atividades
- ✅ Notificações por e-mail junto com o WhatsApp

---

## 📋 Requisitos do Sistema

- **PHP**: >= 7.4
- **WordPress**: >= 5.8
- **Extensões PHP**: mbstring, gd ou imagick (para QR Codes)

---

## 🔧 Configuração

1. Acesse WordPress Admin → ZAP WhatsApp
2. Configure as credenciais da Evolution API
3. Gere o QR Code e escaneie com seu WhatsApp
4. Comece a automatizar suas mensagens!

---


## 🔌 Integração com Zap Tutor Events

O listener do hook `zap_evento` é registrado **imediatamente** durante o `plugins_loaded`, antes do hook `init` do WordPress. Isso garante que nenhum evento disparado precocemente (como `tutor_course_completed`) seja perdido por condição de corrida de carregamento.

---

## ✉️ Notificações por E-mail

Cada automação de mensagem WhatsApp pode ser configurada para enviar também um e-mail ao mesmo tempo.

### Configuração

No admin de cada mensagem automática (metabox "Configurações da Automação"):

| Campo | Chave Meta | Descrição |
|---|---|---|
| Enviar e-mail junto | `zapwa_email_enabled` | Ativa/desativa o envio de e-mail |
| Assunto do e-mail | `zapwa_email_subject` | Assunto da mensagem (suporta variáveis) |
| Corpo do e-mail | `zapwa_email_body` | Corpo da mensagem (suporta variáveis) |
| Enviar como HTML | `zapwa_email_is_html` | Define Content-Type como `text/html` |

### Destinatário

O destinatário do e-mail é extraído automaticamente do payload do evento, tentando as seguintes chaves nesta ordem:

1. `payload['email']`
2. `payload['user_email']`
3. `payload['context']['email']`
4. `payload['context']['user_email']`
5. `payload['context']['recipient_email']`
6. E-mail do usuário WordPress (`user_id`)

Você pode sobrescrever o destinatário via filtro:

```php
add_filter('zapwa_event_email_recipient', function($email, $payload) {
    // Retorne um e-mail customizado
    return 'outro@exemplo.com';
}, 10, 2);
```

### Variáveis disponíveis

Além das variáveis padrão, o assunto e o corpo do e-mail suportam:

| Variável | Descrição |
|---|---|
| `{event}` | Nome do evento disparado |
| `{event_email}` | E-mail extraído do payload do evento |

### Transporte

O e-mail é enviado via `wp_mail()`, portanto é compatível com **WP Mail SMTP** e qualquer plugin de transporte de e-mail para WordPress.

---



Se a instância é criada, mas o QR Code não aparece automaticamente, siga este passo a passo:

1. **Confirmar URL base da Evolution API**
   - No plugin, teste uma destas bases:
     - `https://seu-dominio/api`
     - `https://seu-dominio/api/v1`
     - `https://seu-dominio/api/v2`

2. **Validar autenticação (API Key)**
   - Execute no terminal (troque URL e TOKEN):

   ```bash
   curl -s -H "apikey: SEU_TOKEN" "https://seu-dominio/api/instance/fetchInstances"
   ```

   - Se retornar `401`/`403`, a chave está inválida ou sem permissão.

3. **Validar se a instância existe**
   - Use o nome configurado no plugin em `zapwa_evolution_instance`:

   ```bash
   curl -s -H "apikey: SEU_TOKEN" "https://seu-dominio/api/instance/fetchInstances"
   ```

4. **Validar endpoint de conexão/QR**
   - Algumas instalações aceitam `GET`, outras `POST`:

   ```bash
   # GET
   curl -s -X GET -H "apikey: SEU_TOKEN" "https://seu-dominio/api/instance/connect/NOME_INSTANCIA"

   # POST
   curl -s -X POST -H "apikey: SEU_TOKEN" "https://seu-dominio/api/instance/connect/NOME_INSTANCIA"
   ```

   - O retorno pode vir como:
     - `code` (texto para gerar QR local), ou
     - `qrcode.base64` / `base64` / `qr` / `qrCode`.

5. **Validar estado da conexão**

   ```bash
   curl -s -H "apikey: SEU_TOKEN" "https://seu-dominio/api/instance/connectionState/NOME_INSTANCIA"
   ```

   - Quando conectado, o estado esperado geralmente é `open`.

6. **Ativar logs do WordPress**
   - Em `wp-config.php`, confirme:

   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

   - Depois confira `wp-content/debug.log` e procure por linhas iniciadas com `[ZapWA]`.

7. **Checklist rápido de ambiente**
   - PHP 8.2 ✅ (compatível)
   - Plugin ativo
   - Usuário admin com permissão `manage_options`
   - Sem bloqueio de firewall entre WordPress e Evolution API
   - SSL válido no domínio da Evolution API

Se ainda não funcionar, compartilhe os retornos dos endpoints dos passos 2, 4 e 5 (removendo tokens) para diagnóstico exato.

---

## 📚 Dependências

Este plugin utiliza as seguintes bibliotecas open-source:

- **chillerlan/php-qrcode**: ^4.3 (MIT License)
- **chillerlan/php-settings-container**: ^3.2 (MIT License)

Todas as licenças permitem uso comercial.

---

## 📄 Licença

Este plugin é proprietário. Para informações sobre licenciamento comercial, entre em contato.

---

## 🆘 Suporte

Para suporte técnico, entre em contato através de:
- Email: [seu-email@exemplo.com]
- Website: [seu-website.com]

---

## 🔄 Changelog

Veja [CHANGELOG.md](CHANGELOG.md) para histórico completo de versões.
