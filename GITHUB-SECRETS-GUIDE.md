# GitHub Secrets Configuration Guide

## Overview

GitHub Secrets store sensitive credentials needed for automated deployment and CI/CD workflows. This guide covers all required secrets for deploying PearBlog Engine to production servers.

---

## 🔐 Required Secrets for Deployment

### Accessing GitHub Secrets

1. Go to your repository: `https://github.com/AndyPearman89/PearBlog-Engine-`
2. Navigate to: **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret** for each secret below

---

## Deployment Secrets (for poradnik.pro)

### 1. SSH_HOST
- **Description:** Server IP address or hostname
- **Value for poradnik.pro:** `wordpress2614653.home.pl`
- **Required for:** SSH connection to server
- **Example:**
  ```
  wordpress2614653.home.pl
  ```

### 2. SSH_USER
- **Description:** SSH username for server access
- **Value for poradnik.pro:** `wordpress2614653`
- **Required for:** SSH authentication
- **Example:**
  ```
  wordpress2614653
  ```

### 3. SSH_PRIVATE_KEY
- **Description:** Private SSH key for passwordless authentication
- **Required for:** Secure SSH connection without password
- **How to generate:**
  ```bash
  # On your local machine:
  ssh-keygen -t ed25519 -C "deploy-poradnik-pro" -f ~/.ssh/poradnik_deploy

  # Copy public key to server:
  ssh-copy-id -i ~/.ssh/poradnik_deploy.pub wordpress2614653@wordpress2614653.home.pl

  # Display private key (copy this to GitHub Secret):
  cat ~/.ssh/poradnik_deploy
  ```
- **Value format:**
  ```
  -----BEGIN OPENSSH PRIVATE KEY-----
  b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtzc2gtZW
  ... (multiple lines) ...
  -----END OPENSSH PRIVATE KEY-----
  ```
- **Important:** Copy the ENTIRE key including header and footer lines

### 4. WP_PATH
- **Description:** Absolute path to WordPress installation on server
- **Value for poradnik.pro:** `/wordpress2614653.home.pl/poradnik`
- **Required for:** Determining where to deploy files
- **Example:**
  ```
  /wordpress2614653.home.pl/poradnik
  ```

### 5. SSH_PORT (Optional)
- **Description:** SSH port if different from default
- **Default value:** `22`
- **Required for:** Custom SSH ports
- **Example:**
  ```
  22
  ```
- **Note:** Only add if your server uses non-standard SSH port

### 6. SSH_PASSWORD (Only if password auth is enabled)
- **Description:** SSH password for servers that explicitly allow password logins
- **Required for:** Password-based SSH deployments only
- **Important:** For the current `poradnik.pro` server, password auth is not accepted; use `SSH_PRIVATE_KEY` instead.

### 7. ROOT_PASSWORD
- **Description:** MySQL root password for database operations
- **Value:** Your MySQL root password
- **Required for:** Database creation and user management
- **Security:** ⚠️ CRITICAL - Keep this secret secure
- **Example:**
  ```
  your-strong-mysql-root-password-here
  ```
- **Usage:** Used by automated deployment scripts to:
  - Create WordPress database
  - Create database user
  - Grant privileges
  - Initialize database schema

---

## API & Service Secrets

### 8. OPENAI_API_KEY
- **Description:** OpenAI API key for GPT-4o-mini and DALL-E 3
- **Format:** `sk-proj-...`
- **Required for:** Content generation and image creation
- **How to get:**
  1. Visit: https://platform.openai.com/api-keys
  2. Create new secret key
  3. Copy key immediately (shown only once)
- **Example:**
  ```
  sk-proj-ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789
  ```
- **Cost monitoring:** Set usage limits at https://platform.openai.com/account/limits

### 9. SLACK_WEBHOOK_URL (Optional)
- **Description:** Slack webhook for deployment notifications
- **Format:** `https://hooks.slack.com/services/...`
- **Required for:** Slack alerts and notifications
- **How to get:**
  1. Visit: https://api.slack.com/apps
  2. Create app → Incoming Webhooks
  3. Activate and add to workspace
  4. Copy webhook URL
- **Example:**
  ```
  https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXX
  ```

### 10. DISCORD_WEBHOOK_URL (Optional)
- **Description:** Discord webhook for deployment notifications
- **Format:** `https://discord.com/api/webhooks/...`
- **Required for:** Discord alerts
- **How to get:**
  1. Go to Discord server → Server Settings
  2. Integrations → Webhooks
  3. Create webhook
  4. Copy webhook URL
- **Example:**
  ```
  https://discord.com/api/webhooks/123456789012345678/ABCDEFGHIJKLMNOPQRSTUVWXYZ
  ```

---

## CI/CD Pipeline Secrets

### 11. SITE_URL
- **Description:** Full URL of your WordPress site
- **Value for poradnik.pro:** `https://poradnik.pro`
- **Required for:** API calls and health checks
- **Example:**
  ```
  https://poradnik.pro
  ```

### 12. API_KEY
- **Description:** PearBlog Engine API authentication key
- **Format:** Random secure string
- **Required for:** REST API authentication
- **Generate:**
  ```bash
  openssl rand -base64 32
  ```
- **Example:**
  ```
  ABC123XYZ789RandomSecureString456DEF
  ```

### 13. HEALTH_SECRET
- **Description:** Shared secret for health endpoint checks
- **Required for:** Monitoring health endpoint without admin auth
- **Generate:**
  ```bash
  openssl rand -base64 32
  ```
- **Example:**
  ```
  HealthCheck123Secret456Monitor789
  ```

---

## Email Provider Secrets (Optional)

### 14. MAILCHIMP_API_KEY (Optional)
- **Description:** Mailchimp API key for email digests
- **Required for:** Email marketing integration
- **How to get:**
  1. Login to Mailchimp
  2. Account → Extras → API keys
  3. Create key
- **Example:**
  ```
  abc123def456ghi789jkl012mno345pqr678-us1
  ```

### 15. CONVERTKIT_API_KEY (Optional)
- **Description:** ConvertKit API key
- **Required for:** ConvertKit email integration
- **Example:**
  ```
  ck_1234567890abcdef
  ```

---

## Database Secrets

### 16. DB_BACKUP_FTP_HOST (Optional)
- **Description:** FTP host for automated database backups
- **Example:**
  ```
  ftp.backup-server.com
  ```

### 17. DB_BACKUP_FTP_USER (Optional)
- **Description:** FTP username for backups
- **Example:**
  ```
  backup_user
  ```

### 18. DB_BACKUP_FTP_PASS (Optional)
- **Description:** FTP password for backups
- **Example:**
  ```
  secure-ftp-password
  ```

---

## Quick Setup Checklist

### Minimum Required Secrets (Core Deployment)

```
✓ SSH_HOST            = wordpress2614653.home.pl
✓ SSH_USER            = wordpress2614653
✓ SSH_PRIVATE_KEY     = [SSH private key content]
✓ WP_PATH             = /wordpress2614653.home.pl/poradnik
✓ ROOT_PASSWORD       = [MySQL root password]
✓ OPENAI_API_KEY      = sk-proj-...
```

### Recommended Secrets (Full Features)

```
✓ SITE_URL            = https://poradnik.pro
✓ API_KEY             = [Generated API key]
✓ HEALTH_SECRET       = [Generated health secret]
✓ SLACK_WEBHOOK_URL   = https://hooks.slack.com/...
```

---

## Secrets Validation

### Test SSH Connection
```bash
# Test that SSH key works:
ssh -i ~/.ssh/poradnik_deploy wordpress2614653@wordpress2614653.home.pl "echo 'SSH connection successful'"
```

### Test OpenAI API Key
```bash
# Test OpenAI key:
curl https://api.openai.com/v1/models \
  -H "Authorization: Bearer sk-proj-YOUR_KEY_HERE"
```

### Verify MySQL Access
```bash
# Test on server:
mysql -u root -p -e "SELECT VERSION();"
```

---

## Security Best Practices

### 1. Key Rotation
- Rotate SSH keys every 90 days
- Rotate API keys quarterly
- Update ROOT_PASSWORD periodically

### 2. Access Control
- Limit who can view GitHub Secrets (repository admins only)
- Use separate keys for dev/staging/production
- Never commit secrets to code

### 3. Monitoring
- Monitor OpenAI API usage: https://platform.openai.com/usage
- Set up billing alerts
- Review GitHub Actions logs for failed authentications

### 4. Backup
- Keep encrypted backup of all credentials
- Document which secrets are used where
- Use password manager for credential storage

---

## Troubleshooting

### "SSH connection failed"
1. Verify SSH_HOST is correct
2. Check SSH_PRIVATE_KEY is complete (including header/footer)
3. Confirm public key is in `~/.ssh/authorized_keys` on server
4. Test SSH manually: `ssh wordpress2614653@wordpress2614653.home.pl`

### "MySQL access denied"
1. Verify ROOT_PASSWORD is correct
2. Test on server: `mysql -u root -p`
3. Check MySQL is running: `systemctl status mysql`
4. Verify user has proper permissions

### "OpenAI API error"
1. Verify API key format: starts with `sk-proj-`
2. Check API key is active at https://platform.openai.com/api-keys
3. Verify account has credits
4. Check usage limits: https://platform.openai.com/account/limits

### "Deployment fails with permission denied"
1. Verify WP_PATH exists and is writable
2. Check SSH_USER has proper permissions
3. Confirm `www-data` user exists on server

---

## Environment-Specific Secrets

### Production Site 1 (poradnik.pro)
```
SSH_HOST=wordpress2614653.home.pl
SSH_USER=wordpress2614653
WP_PATH=/wordpress2614653.home.pl/poradnik
SITE_URL=https://poradnik.pro
ROOT_PASSWORD=[MySQL root password]
OPENAI_API_KEY=sk-proj-...
```

### Production Site 2 (mucharski.pl)
```
MUCHARSKI_SSH_HOST=[Your server IP - TBD]
MUCHARSKI_SSH_USER=root
MUCHARSKI_WP_PATH=/var/www/mucharski.pl
MUCHARSKI_SITE_URL=https://mucharski.pl
MUCHARSKI_ROOT_PASSWORD=[MySQL root password]
MUCHARSKI_OPENAI_API_KEY=sk-proj-... (can share with poradnik.pro)
```

**Note for mucharski.pl:** Before deployment, update the `SERVER_IP` variable in `scripts/deploy-mucharski-pl.sh` and add the corresponding GitHub Secrets.

### Multi-Site Deployment Strategy

When deploying multiple sites with PearBlog Engine:

1. **Shared Secrets** (can be reused across sites):
   - `OPENAI_API_KEY` - Same API key for all sites
   - `SSH_PRIVATE_KEY` - If using same server

2. **Site-Specific Secrets** (unique per site):
   - `[SITE]_SSH_HOST` - Different servers = different IPs
   - `[SITE]_WP_PATH` - Different WordPress installations
   - `[SITE]_ROOT_PASSWORD` - Different databases
   - `[SITE]_SITE_URL` - Different domains

3. **Naming Convention:**
   - Prefix site-specific secrets with site identifier
   - Examples: `MUCHARSKI_*`, `PORADNIK_*`
   - Keeps secrets organized and prevents conflicts

### Staging (Optional)
```
SSH_HOST=staging.poradnik.pro
WP_PATH=/var/www/staging.poradnik.pro
SITE_URL=https://staging.poradnik.pro
```

### Development (Local)
- No GitHub Secrets needed
- Use `.env` file locally
- Never commit `.env` to repository

---

## GitHub Actions Workflow Usage

Secrets are accessed in workflows via:

```yaml
steps:
  - name: Deploy to server
    run: |
      rsync -avz \
        -e "ssh -p ${{ secrets.SSH_PORT }}" \
        ./mu-plugins/ \
        ${{ secrets.SSH_USER }}@${{ secrets.SSH_HOST }}:${{ secrets.WP_PATH }}/wp-content/mu-plugins/
    env:
      ROOT_PASSWORD: ${{ secrets.ROOT_PASSWORD }}
      OPENAI_API_KEY: ${{ secrets.OPENAI_API_KEY }}
```

---

## Additional Resources

- **GitHub Docs:** https://docs.github.com/en/actions/security-guides/encrypted-secrets
- **SSH Key Generation:** https://docs.github.com/en/authentication/connecting-to-github-with-ssh
- **OpenAI API:** https://platform.openai.com/docs/api-reference
- **MySQL Security:** https://dev.mysql.com/doc/refman/8.0/en/security-guidelines.html

---

**Last Updated:** 2026-05-02
**Repository:** AndyPearman89/PearBlog-Engine-
**For:** poradnik.pro deployment (wordpress2614653.home.pl)
