# Fix SSH Key Encryption Issue

## Problem
The SSH private key added to GitHub Secrets is **encrypted with a passphrase**. GitHub Actions cannot use encrypted keys because there's no way to provide the passphrase during deployment.

**Error:**
```
Error loading key "(stdin)": error in libcrypto
```

---

## Solution: Provide an Unencrypted SSH Key

### Option 1: Remove Passphrase from Existing Key ✅ RECOMMENDED

If you have the original SSH key file on your local machine:

```bash
# Remove passphrase from existing key
ssh-keygen -p -f ~/.ssh/poradnik_deploy -N ""

# This will ask for the OLD passphrase, then set a blank (empty) passphrase
```

**Steps:**
1. Run the command above
2. Enter your current passphrase when prompted
3. The key will be saved without encryption
4. Display the unencrypted private key:
   ```bash
   cat ~/.ssh/poradnik_deploy
   ```
5. Copy the **entire output** (including headers)
6. Update GitHub Secret `SSH_PRIVATE_KEY` with the new unencrypted key

---

### Option 2: Generate a New Unencrypted Key

If you don't have access to the original key or prefer a fresh start:

```bash
# Generate new unencrypted SSH key
ssh-keygen -t ed25519 -C "github-deploy-poradnik" -f ~/.ssh/github_deploy -N ""
```

**The `-N ""` parameter is critical** - it creates the key without a passphrase.

**Steps:**
1. Run the command above
2. Copy the public key to the server:
   ```bash
   ssh-copy-id -i ~/.ssh/github_deploy.pub root@204.48.27.118
   ```
3. Test the connection:
   ```bash
   ssh -i ~/.ssh/github_deploy root@204.48.27.118
   ```
4. If successful, display the private key:
   ```bash
   cat ~/.ssh/github_deploy
   ```
5. Copy the **entire output** (including headers)
6. Update GitHub Secret `SSH_PRIVATE_KEY` with this key

---

## How to Update GitHub Secret

1. Go to: https://github.com/AndyPearman89/PearBlog-Engine-/settings/secrets/actions
2. Find `SSH_PRIVATE_KEY`
3. Click "Update" or "Remove" then "Add"
4. Paste the **entire unencrypted private key**
5. Save

---

## Verify Unencrypted Key Format

An **unencrypted** OpenSSH private key looks like this:

```
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtzc2gtZW
...
-----END OPENSSH PRIVATE KEY-----
```

**Key indicators:**
- Second line after header typically starts with `b3BlbnNzaC1rZXktdjEAAAAABG5vbmU=`
- The header `b3BlbnNzaC1rZXktdjE=` decodes to `openssh-key-v1`
- Followed by `AAAABG5vbmU=` which decodes to "none" (no encryption)

An **encrypted** key looks like this (YOUR CURRENT KEY):

```
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAACmFlczI1Ni1jdHIAAAAGYmNyeXB0AAAAGAAAABA...
-----END OPENSSH PRIVATE KEY-----
```

**Key indicators:**
- Contains `CmFlczI1Ni1jdHI=` which decodes to "aes256-ctr" (encryption cipher)
- Contains `YmNyeXB0` which decodes to "bcrypt" (key derivation function)

---

## After Updating the Secret

1. Go to: https://github.com/AndyPearman89/PearBlog-Engine-/actions/workflows/deploy.yml
2. Click "Run workflow"
3. Select branch: `claude/install-204-48-27-118` or `main`
4. Click "Run workflow"

The deployment should now succeed without the libcrypto error.

---

## Security Note

**Is it safe to use an unencrypted SSH key?**

✅ **YES** - For GitHub Actions deployment keys, this is the standard practice:
- The key is stored securely in GitHub Secrets (encrypted at rest)
- The key is never exposed in logs
- The key is used only by automated deployment workflows
- You can restrict the key on the server using `authorized_keys` options

**Best practice:** Create a dedicated deployment key with minimal permissions, separate from your personal SSH keys.

---

**Last Updated:** 2026-05-20
**Issue:** SSH key encryption preventing GitHub Actions deployment
**Status:** Awaiting unencrypted SSH_PRIVATE_KEY secret
