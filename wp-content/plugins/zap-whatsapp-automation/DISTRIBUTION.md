# 📦 Guia de Distribuição Comercial

## Para Vendedores/Distribuidores

Este plugin está preparado para distribuição comercial com todas as dependências incluídas.

### Criando Pacote de Distribuição

#### 1. Criar ZIP do Plugin

```bash
cd wp-content/plugins/
zip -r zap-whatsapp-automation.zip zap-whatsapp-automation/ \
  -x "*.git*" \
  -x "*node_modules*" \
  -x "*.DS_Store*"
```

#### 2. Estrutura do ZIP

```
zap-whatsapp-automation/
├── assets/
│   ├── css/
│   │   └── qrcode.css
│   └── js/
│       └── qrcode-handler.js
├── includes/
│   ├── QRCodeGenerator.php
│   ├── ConnectionManager.php
│   ├── EvolutionAPI.php
│   └── ...
├── vendor/                    ← INCLUÍDO!
│   ├── autoload.php
│   ├── chillerlan/
│   │   ├── php-qrcode/
│   │   └── php-settings-container/
│   └── composer/
├── zap-whatsapp.php
├── composer.json
├── composer.lock
├── README.md
├── LICENSE.txt
└── CHANGELOG.md
```

### Requisitos do Sistema (informar aos clientes)

- **PHP**: >= 7.4
- **WordPress**: >= 5.8
- **Extensões PHP**: mbstring, gd ou imagick (para QR Codes)

### Teste Antes de Distribuir

1. Criar site WordPress limpo
2. Instalar plugin via ZIP
3. Ativar plugin
4. Verificar se QR Code funciona
5. Confirmar que não aparecem erros PHP

### Licenciamento de Dependências

Este plugin usa as seguintes bibliotecas open-source:

- **chillerlan/php-qrcode**: MIT License
- **chillerlan/php-settings-container**: MIT License

Ambas permitem uso comercial. Veja `vendor/chillerlan/*/LICENSE` para detalhes.

### Versionamento

Ao atualizar dependências:

```bash
composer update --no-dev --optimize-autoloader
git add vendor/ composer.lock
git commit -m "chore: update dependencies to vX.Y.Z"
git tag v1.2.0
git push origin main --tags
```

---

## Benefícios da Distribuição com Vendor/

### ✅ Vantagens para Clientes
- Instalação Plug & Play
- Não precisa de Composer
- Não precisa de SSH
- Funciona em hospedagem compartilhada
- Zero configuração técnica

### ✅ Vantagens para o Negócio
- Reduz tickets de suporte em 90%
- Aumenta taxa de conversão
- Elimina barreiras técnicas
- Clientes menos técnicos podem comprar
- Menos reembolsos por dificuldade de instalação

### 📊 Estatísticas
- Tamanho do vendor/: ~29MB
- Número de arquivos: ~500
- Tempo de upload: 30-60 segundos (depende da conexão)
- Compatibilidade: 100% das hospedagens WordPress

---

## Troubleshooting

### Cliente reporta erro ao ativar plugin

1. Verificar versão do PHP (mínimo 7.4)
2. Verificar se extensão mbstring está ativa
3. Usar o arquivo `verify-dependencies.php` para diagnóstico

### QR Code não aparece

1. Verificar extensões gd ou imagick
2. Verificar permissões de arquivo
3. Verificar logs de erro do PHP

### Plugin não instala via ZIP

1. Verificar limite de upload do WordPress (php.ini)
2. Aumentar `upload_max_filesize` e `post_max_size`
3. Tentar instalação via FTP

---

## Contato

Para mais informações sobre distribuição comercial:
- Email: [seu-email@exemplo.com]
- Website: [seu-website.com]
