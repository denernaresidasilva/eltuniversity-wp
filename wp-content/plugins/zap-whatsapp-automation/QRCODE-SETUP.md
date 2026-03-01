# QR Code Generator - Quick Setup Guide

## 📦 Installation

### Step 1: Install Composer Dependencies

After deploying the plugin files, run Composer to install the QR Code library:

```bash
cd wp-content/plugins/zap-whatsapp-automation
composer install --no-dev --optimize-autoloader
```

This will install:
- `chillerlan/php-qrcode` (QR Code generation)
- `chillerlan/php-settings-container` (required dependency)

### Step 2: Verify Installation

Check that the vendor directory was created:

```bash
ls -la vendor/
# Should show: chillerlan/
```

## 🔧 Configuration

### Step 1: Access Connection Page

1. Log in to WordPress admin
2. Go to **ZAP WhatsApp → Conexão**

### Step 2: Configure Evolution API

1. Select **Evolution API** as connection type
2. Fill in the following fields:
   - **URL da Evolution API**: Your Evolution API URL (e.g., `https://evolution.yourdomain.com/api/v2` or `/api/v1`)
   - **API Key**: Your Evolution API token
   - **Nome da Instância**: A unique name for this instance (e.g., `my-instance`)
3. Click **Salvar Configurações**

## 📱 Connecting WhatsApp

### Step 1: Create Instance and Generate QR Code

1. Click the button **"🚀 Criar Instância e Gerar QR Code"**
2. Wait for the QR Code to appear (a few seconds)

### Step 2: Scan QR Code

1. Open WhatsApp on your phone
2. Tap **Menu** or **Settings**
3. Select **Connected Devices** (or **Linked Devices**)
4. Tap **Connect a Device**
5. Point your phone at the screen to scan the QR Code

### Step 3: Connection Detected

1. The system will automatically detect when WhatsApp connects
2. You'll see a green success message
3. The page will reload after 3 seconds
4. Status will show **🟢 Conectado**

## 🎯 Features

### Auto-Refresh
- QR Code expires after **2 minutes**
- Timer shows remaining time
- Click **"🔄 Atualizar QR Code"** to generate a new one

### Connection Detection
- System checks connection status every **5 seconds**
- Automatic page reload when connected
- Real-time visual feedback

### Download QR Code
- Click **"💾 Baixar QR Code"** to save as PNG image
- Useful for sharing or printing

## 🔍 Troubleshooting

### QR Code Not Displaying

**Problem**: QR Code container shows "Erro ao carregar QR Code"

**Solutions**:
1. Verify Evolution API URL is correct and accessible
2. Check that API Key is valid
3. Ensure instance name doesn't contain special characters
4. Check PHP error logs for more details

### Instance Creation Endpoint (Step by Step)

**Problem**: Error creating instance (404 / endpoint not found)

**Steps**:
1. Confirm the base URL points to the API (e.g., `https://evolution.seudominio.com/api/v2`).
2. Test the creation endpoint manually:
   ```bash
   curl -X POST "https://evolution.seudominio.com/api/v2/instance/create" \
     -H "Content-Type: application/json" \
     -H "apikey: YOUR_API_KEY" \
     -H "Authorization: Bearer YOUR_API_KEY" \
     -d '{"instanceName":"my-instance","integration":"WHATSAPP-BAILEYS","qrcode":true}'
   ```
3. If it returns 404, adjust the URL to `/api`, `/api/v1`, `/api/v2`, or `/api/v3` and retry.
4. Check the Evolution API server logs to confirm the available route.

### Composer Dependencies Not Installed

**Problem**: "Class 'chillerlan\QRCode\QRCode' not found"

**Solution**:
```bash
cd wp-content/plugins/zap-whatsapp-automation
composer install --no-dev --optimize-autoloader
```

### Connection Not Detected

**Problem**: QR Code scanned but status remains "Desconectado"

**Solutions**:
1. Wait up to 10 seconds for detection
2. Manually refresh the page
3. Check Evolution API instance status
4. Verify WhatsApp is still showing as connected

### QR Code Expires Too Quickly

**Problem**: Not enough time to scan

**Solutions**:
1. Click **"🔄 Atualizar QR Code"** to generate a new one
2. Have WhatsApp app ready before generating QR Code
3. QR Codes expire after 2 minutes for security

## 🔒 Security Notes

1. **API Key Protection**: Never share your Evolution API key
2. **HTTPS Required**: Always use HTTPS for Evolution API URL
3. **Admin Only**: Only WordPress administrators can access connection settings
4. **Nonce Verification**: All AJAX requests are verified with security tokens

## 📊 Browser Compatibility

The QR Code generator is tested and works on:
- ✅ Chrome/Chromium (Desktop & Mobile)
- ✅ Firefox (Desktop & Mobile)
- ✅ Safari (Desktop & Mobile)
- ✅ Edge (Desktop)

## 🆘 Need Help?

If you encounter issues:

1. **Check PHP version**: Minimum PHP 7.4 required
2. **Check WordPress version**: Latest version recommended
3. **Review error logs**: Check WordPress debug log
4. **Verify API credentials**: Test Evolution API directly
5. **Clear cache**: Clear browser and WordPress cache

## 📝 Important Notes

### Option Names Changed

If you were using an older version of the plugin, note that option names have been standardized:

- `zapwa_api_url` → `zapwa_evolution_url`
- `zapwa_api_token` → `zapwa_evolution_token`
- `zapwa_instance_name` → `zapwa_evolution_instance`

**Action Required**: Re-save your settings in the Connection page to migrate to new option names.

### Vendor Directory

The `vendor/` directory is not included in the repository. You **must** run `composer install` after deployment.

### Deployment Checklist

- [ ] Files uploaded to server
- [ ] Run `composer install` in plugin directory
- [ ] Verify vendor directory exists
- [ ] Configure Evolution API settings
- [ ] Test QR Code generation
- [ ] Verify connection detection works

## ✅ Success Indicators

You'll know everything is working when:

1. ✅ QR Code displays correctly
2. ✅ Timer counts down from 2:00
3. ✅ Connection status updates to **🟢 Conectado** after scanning
4. ✅ No errors in browser console
5. ✅ Download button works

## 🔄 Updating

When updating the plugin:

1. Backup your current installation
2. Upload new files
3. Run `composer install` again (if composer.json changed)
4. Clear WordPress cache
5. Test connection functionality

---

**Version**: 1.0.0  
**Last Updated**: 2026-02-10  
**Compatibility**: WordPress 5.8+, PHP 7.4+
