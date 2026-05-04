# GitHub Release v6.0.0 - Creation Instructions

**Date:** 2026-05-04
**Status:** Ready to create
**Target:** T-3 Days (2026-05-07) for v7.0.0 launch (2026-05-10)

---

## Pre-Flight Checklist

- [x] Plugin ZIP packaged: `releases/pearblog-engine-v6.0.0.zip` (589 KB)
- [x] Release notes prepared: `GITHUB-RELEASE-v7.0.0.md`
- [x] Launch announcement ready: `LAUNCH-ANNOUNCEMENT.md`
- [ ] Git tag created
- [ ] GitHub Release published
- [ ] ZIP uploaded to release assets

---

## Step 1: Create Git Tag

From your local machine or GitHub Actions environment:

```bash
# Option A: Annotated tag (recommended)
git tag -a v6.0.0 -m "PearBlog Engine v6.0.0 - Production Release

- v7 Dark UI Kit complete design system
- Multi-model AI support (OpenAI, Anthropic, Google)
- Enterprise autopilot with 26 automated tasks
- 743/743 tests passing (100%)
- Production-ready with zero security vulnerabilities"

# Option B: Lightweight tag
git tag v6.0.0

# Push tag to origin
git push origin v6.0.0
```

---

## Step 2: Create GitHub Release

### Via GitHub Web Interface

1. **Navigate to Releases:**
   - Go to: https://github.com/AndyPearman89/PearBlog-Engine-/releases
   - Click: **"Draft a new release"**

2. **Configure Release:**
   - **Choose a tag:** Select `v6.0.0` (or create it if it doesn't exist)
   - **Target:** `main` branch
   - **Release title:** `🚀 PearBlog Engine v6.0.0 - Production Release`

3. **Release Description:**
   - Copy content from `GITHUB-RELEASE-v7.0.0.md`
   - OR use the abbreviated version below

4. **Upload Assets:**
   - Click **"Attach binaries by dropping them here or selecting them"**
   - Upload: `releases/pearblog-engine-v6.0.0.zip`

5. **Publish Options:**
   - [ ] Set as pre-release (leave unchecked for production)
   - [ ] Set as latest release (check this)
   - [x] Create a discussion for this release (recommended)

6. **Click:** **"Publish release"**

### Via GitHub CLI (if available)

```bash
# Create release with notes from file
gh release create v6.0.0 \
  --title "🚀 PearBlog Engine v6.0.0 - Production Release" \
  --notes-file GITHUB-RELEASE-v7.0.0.md \
  releases/pearblog-engine-v6.0.0.zip

# Or with inline notes (abbreviated)
gh release create v6.0.0 \
  --title "🚀 PearBlog Engine v6.0.0 - Production Release" \
  --notes "See full release notes: https://github.com/AndyPearman89/PearBlog-Engine-/blob/main/GITHUB-RELEASE-v7.0.0.md" \
  releases/pearblog-engine-v6.0.0.zip
```

---

## Abbreviated Release Notes (For Quick Publishing)

If you need a shorter version for the GitHub Release page:

```markdown
# 🚀 PearBlog Engine v6.0.0 - Production Release

**Release Date:** May 3, 2026
**Status:** Production Ready ✅
**Target Launch:** May 10, 2026

## ✨ Highlights

### 🎨 v7 Dark UI Kit
Complete design system with dark-first theme, vibrant accents (#4ADE80 green, #60A5FA blue), and 18px typography optimized for readability.

**Enable:** `wp option update pearblog_homepage_version v7`

### 🤖 Multi-Model AI
- **OpenAI**: GPT-4o, GPT-4o-mini
- **Anthropic**: Claude 3.5 Sonnet
- **Google**: Gemini 1.5 Pro

### ✅ Production Verified
- **743/743 tests passing** (100%)
- **Zero security vulnerabilities**
- **<30s pipeline, <200ms API**
- **Load tested: 1000 concurrent users**

### 🎯 Enterprise Features
- Topic Research Engine (GA4 + SERP + keyword clusters)
- Smart Scheduler (optimal publish times)
- A/B Testing Framework
- Advanced Analytics Dashboard
- 38+ WP-CLI commands

## 📦 Installation

```bash
# Download
wget https://github.com/AndyPearman89/PearBlog-Engine-/releases/download/v6.0.0/pearblog-engine-v6.0.0.zip

# Install
unzip pearblog-engine-v6.0.0.zip
cp -r mu-plugins/pearblog-engine /path/to/wp-content/mu-plugins/

# Configure
wp option update pearblog_homepage_version v7
```

## 📚 Documentation

- **[Complete Release Notes](https://github.com/AndyPearman89/PearBlog-Engine-/blob/main/GITHUB-RELEASE-v7.0.0.md)**
- **[Installation Guide](https://github.com/AndyPearman89/PearBlog-Engine-/blob/main/DEPLOYMENT.md)**
- **[API Documentation](https://github.com/AndyPearman89/PearBlog-Engine-/blob/main/API-DOCUMENTATION.md)**
- **[Documentation Index](https://github.com/AndyPearman89/PearBlog-Engine-/blob/main/DOCUMENTATION-INDEX.md)**

## 🎯 Requirements

- WordPress 6.5+
- PHP 8.1+
- OpenAI/Anthropic/Google API keys

---

**Full Changelog**: https://github.com/AndyPearman89/PearBlog-Engine-/blob/main/CHANGELOG.md

🚀 **Happy Blogging with PearBlog Engine v6.0!**
```

---

## Step 3: Verify Release

After publishing:

1. **Check release page:**
   - https://github.com/AndyPearman89/PearBlog-Engine-/releases/tag/v6.0.0

2. **Verify ZIP download:**
   ```bash
   wget https://github.com/AndyPearman89/PearBlog-Engine-/releases/download/v6.0.0/pearblog-engine-v6.0.0.zip
   unzip -t pearblog-engine-v6.0.0.zip
   ```

3. **Test installation:**
   - Follow DEPLOYMENT.md on a test WordPress site
   - Verify plugin activates without errors
   - Run: `wp pearblog stats`

---

## Post-Release Actions

After successful release:

- [x] Update LAUNCH-DAY-PLAN.md checklist
- [ ] Announce on social media (scheduled for launch day)
- [ ] Update README.md badge/version (if applicable)
- [ ] Notify beta testers
- [ ] Prepare ProductHunt listing

---

## Troubleshooting

### Tag already exists
```bash
# Delete and recreate
git tag -d v6.0.0
git push origin :refs/tags/v6.0.0
# Then recreate tag
```

### Release already published
- Edit existing release on GitHub
- Upload additional assets
- Update release notes

### ZIP upload failed
- Try uploading via web interface instead of CLI
- Ensure file size < 2GB (current: 589 KB ✓)
- Check GitHub status: https://www.githubstatus.com/

---

## Related Files

- `releases/pearblog-engine-v6.0.0.zip` - Plugin package
- `GITHUB-RELEASE-v7.0.0.md` - Full release notes
- `LAUNCH-ANNOUNCEMENT.md` - Launch day announcement
- `LAUNCH-DAY-PLAN.md` - Hour-by-hour runbook
- `PRE-LAUNCH-CHECKLIST.md` - Final verification checklist

---

**Created:** 2026-05-04
**For:** v6.0.0 / v7.0.0 Launch
**Status:** Instructions ready, awaiting execution
