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
