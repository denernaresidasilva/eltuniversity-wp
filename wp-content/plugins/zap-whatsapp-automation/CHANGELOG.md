# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Versionamento Semântico](https://semver.org/lang/pt-BR/).

---

## [1.1.0] - 2026-02-11

### Added
- ✅ Dependências do Composer incluídas no repositório
- ✅ Plugin agora é Plug & Play (não requer composer install)
- ✅ Arquivo `.gitattributes` para otimizar Git
- ✅ Arquivo `verify-dependencies.php` para diagnóstico
- ✅ Documentação completa de distribuição (`DISTRIBUTION.md`)
- ✅ README.md com instruções de instalação para usuários finais
- ✅ CHANGELOG.md para rastreamento de versões

### Changed
- 🔄 `.gitignore` atualizado para permitir `vendor/` e `composer.lock`
- 🔄 Plugin preparado para distribuição comercial

### Dependencies
- chillerlan/php-qrcode: ^4.3 (instalado: 4.4.2)
- chillerlan/php-settings-container: ^3.2 (instalado: 3.2.1)

### Notes
- 📦 Tamanho do vendor/: ~29MB
- ✅ Todas as dependências são MIT License (uso comercial permitido)
- ✅ Funciona em 100% das hospedagens WordPress

---

## [1.0.0] - 2026-02-10

### Added
- ✅ Geração de QR Code local (sem depender da Evolution API)
- ✅ Auto-refresh do QR Code (expira em 2 minutos)
- ✅ Detecção automática de conexão
- ✅ Timer visual de expiração
- ✅ Botão de download do QR Code
- ✅ Interface moderna e responsiva
- ✅ Sistema de fila para envio de mensagens
- ✅ Métricas e relatórios detalhados
- ✅ Logs de atividades
- ✅ Integração com Evolution API
- ✅ Suporte a variáveis personalizadas
- ✅ Anti-spam protection
- ✅ Sistema de health check

### Fixed
- 🐛 Padronização de nomes de opções (zapwa_*)
- 🐛 Métodos faltantes na classe Queue
- 🐛 Conflito entre sistemas de fila
- 🐛 CSRF protection em formulários
- 🐛 Intervalo de cron customizado

### Security
- 🔒 Validação de nonces em todas as requisições AJAX
- 🔒 Sanitização de inputs
- 🔒 Escape de outputs
- 🔒 Verificação de capabilities do usuário

---

## [0.9.0] - 2026-02-09 (Beta)

### Added
- ✅ Versão inicial do plugin
- ✅ Conexão básica com Evolution API
- ✅ Envio de mensagens via WhatsApp
- ✅ Painel administrativo básico

---

## Planejamento Futuro

### [1.2.0] - Planejado
- [ ] Suporte a templates de mensagem
- [ ] Integração com WooCommerce
- [ ] Envio de mensagens em massa
- [ ] Agendamento de mensagens
- [ ] Estatísticas avançadas
- [ ] Suporte a múltiplas instâncias

### [1.3.0] - Planejado
- [ ] API REST para integrações
- [ ] Webhooks personalizados
- [ ] Testes automatizados
- [ ] Documentação API completa

---

## Convenções de Commit

Este projeto segue o padrão [Conventional Commits](https://www.conventionalcommits.org/):

- `feat:` - Nova funcionalidade
- `fix:` - Correção de bug
- `docs:` - Mudanças na documentação
- `style:` - Formatação, ponto e vírgula faltando, etc
- `refactor:` - Refatoração de código
- `test:` - Adição de testes
- `chore:` - Atualização de tarefas de build, configs, etc

---

## Suporte

Para reportar bugs ou solicitar funcionalidades:
- Crie uma issue no repositório
- Entre em contato através do email de suporte
- Consulte a documentação

---

**Nota**: As datas e versões refletem o histórico do desenvolvimento do plugin.
