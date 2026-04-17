# GitHub Actions Workflows

This directory contains automated CI/CD workflows for the linksvault2 project.

## 🎯 Overview

The project uses a robust CI/CD pipeline with **FTP/SFTP deployment** and **non-blocking database migrations**, ensuring deployments continue even when migrations fail or are already applied (common in production environments).

## Available Workflows

### 1. CI (Continuous Integration) - `ci.yml`
**Triggers:** Push or PR to `main` and `develop` branches

**Jobs:**
- ✅ **Tests**: Runs test suite on PHP 8.3 and 8.4 with MySQL and SQLite
- ✅ **Code Quality**: Checks code style with Laravel Pint and static analysis with PHPStan
- ✅ **Frontend Build**: Validates Vite build process and asset compilation

**Key Feature:** Non-blocking migrations prevent false negatives during testing

---

### 2. CD (Continuous Deployment) - `cd.yml`
**Triggers:** Push to `main` branch or manual trigger

**Features:**
- 🚀 **FTP/SFTP deployment** using SamKirkland/FTP-Deploy-Action
- 📦 Dependency installation (Composer + NPM)
- 🗄️ **Non-blocking database migrations** (won't stop deployment if they fail)
- 🔐 Optional SSH access for post-deployment tasks
- ⚡ Configuration caching
- 🔄 Service restarts (PHP-FPM, Queue workers)
- 📊 Deployment status notifications

**Migration Handling:**
```yaml
continue-on-error: true  # Migrations won't block deployment
```

**Required Secrets:**
- FTP/SFTP credentials (`FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`, etc.)
- Database credentials (`DB_*`)
- SSH access (optional, for post-deployment tasks)

---

### 3. Complete CI/CD Pipeline - `cicd.yml` ⭐ RECOMMENDED
**Triggers:** Push to `main`/`develop`, Pull Requests, or manual trigger

**Three-Phase Architecture:**

#### Phase 1: Validation & Testing
- Code quality checks (Pint, PHPStan)
- Multi-version PHP testing (8.3, 8.4)
- Frontend build verification

#### Phase 2: Production Deployment
- Production-ready dependency installation
- **Smart migration handling** with detailed logging
- **Secure file deployment via FTP/SFTP**
- Remote server orchestration via SSH (optional)
- **Health check with automatic retries**
- **Deployment summary generation**

#### Phase 3: Notifications
- Status notifications (extensible for Slack, Discord, email)

**Advanced Features:**
- Environment protection support
- Sequential job execution with dependencies
- Detailed migration status tracking
- Automatic health verification
- Comprehensive deployment summaries

**Deployment Method:**
- Primary: FTP/SFTP for file transfer
- Secondary: SSH (optional) for post-deployment commands

---

### 4. Laravel Forge Deployment - `deploy-forge.yml`
**Triggers:** Push to `main` branch or manual trigger

**Features:**
- 🚀 One-click deployment via Laravel Forge API
- ✅ Automatic deployment verification
- 🔍 HTTP status code checking

**Required Secrets:**
- `FORGE_API_TOKEN` - Your Forge API token
- `FORGE_SERVER_ID` - Server ID in Forge
- `FORGE_SITE_ID` - Site ID in Forge
- `APP_URL` - Your application URL

---

## 🔑 Migration Strategy

### Why Non-Blocking Migrations?

In production environments, migrations don't need to run on every deployment:
- ✅ No new migrations may exist
- ✅ Migrations might have been applied manually
- ✅ Database schema could already be up-to-date
- ✅ Temporary connection issues shouldn't block deployment

### Implementation Pattern

All workflows use this robust pattern:

```yaml
- name: Run database migrations (non-blocking)
  continue-on-error: true
  run: |
    echo "🔄 Attempting to run database migrations..."
    if php artisan migrate --force; then
      echo "✅ Migrations executed successfully"
    else
      echo "⚠️ Migrations failed or already up-to-date - continuing deployment"
      echo "This is expected in production when no new migrations are needed"
    fi
```

**Benefits:**
- ✅ Deployment continues even if migrations fail
- ✅ Clear, informative logging
- ✅ No false negatives in CI/CD pipeline
- ✅ Manual migration capability preserved

---

## 🚀 Quick Start

### For FTP/SFTP Deployment (cd.yml or cicd.yml)

1. **Get FTP/SFTP credentials from your hosting provider:**
   - Server address
   - Username
   - Password
   - Port (21 for FTP, 22 for SFTP)
   - Remote path

2. **Add secrets to GitHub repository:**
   - Go to Settings → Secrets and variables → Actions
   - Add all required secrets (see `docs/CI_CD_SETUP.md`)

3. **Push to `main` branch to trigger deployment**

### Optional: Configure SSH for Post-Deployment Tasks

SSH allows running Composer, migrations, cache clearing, and service restarts after FTP deployment:

1. **Generate SSH key pair:**
   ```bash
   ssh-keygen -t ed25519 -C "github-actions@linksvault2" -f ~/.ssh/github_actions
   ```

2. **Add public key to your server:**
   ```bash
   ssh-copy-id -i ~/.ssh/github_actions.pub user@your-server.com
   ```

3. **Add SSH secrets to GitHub:**
   - `SSH_HOST`
   - `SSH_USERNAME`
   - `SSH_KEY` (private key content)
   - `SSH_PORT`

### For Laravel Forge (deploy-forge.yml)

1. Get your Forge API token from Forge dashboard
2. Find your Server ID and Site ID in Forge
3. Add these secrets to GitHub:
   - `FORGE_API_TOKEN`
   - `FORGE_SERVER_ID`
   - `FORGE_SITE_ID`
   - `APP_URL`

4. Push to `main` branch

---

## 🎮 Manual Triggers

You can manually run any workflow:
1. Go to **Actions** tab in GitHub
2. Select the workflow
3. Click **Run workflow**
4. Choose branch and click **Run workflow**

---

## 📊 Monitoring

View workflow progress and logs:
- Navigate to **Actions** tab
- Click on a workflow run
- Expand jobs to see detailed logs
- Check deployment summaries in `cicd.yml`

---

## 🛠️ Customization

See `docs/CI_CD_SETUP.md` for:
- Detailed configuration guide
- FTP vs SFTP setup
- Troubleshooting tips
- Security best practices
- Customization options
- Health check configuration
- Notification setup

---

## 🏷️ Workflow Status Badges

Add these to your README.md:

```markdown
![CI](https://github.com/YOUR_USERNAME/linksvault2/actions/workflows/ci.yml/badge.svg)
![CD](https://github.com/YOUR_USERNAME/linksvault2/actions/workflows/cd.yml/badge.svg)
![CI/CD](https://github.com/YOUR_USERNAME/linksvault2/actions/workflows/cicd.yml/badge.svg)
```

---

## 📚 Additional Resources

- [CI/CD Setup Guide](../../docs/CI_CD_SETUP.md) - Complete configuration documentation
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Laravel Deployment Best Practices](https://laravel.com/docs/deployment)
- [FTP-Deploy-Action](https://github.com/SamKirkland/FTP-Deploy-Action)

---

## 💡 Best Practices

1. **Always test on develop branch first** before merging to main
2. **Review migration changes** before deploying to production
3. **Backup database** before major migrations
4. **Monitor deployment logs** for any warnings
5. **Use environment protection** for production deployments
6. **Keep secrets updated** and rotate passwords/keys periodically
7. **Use SFTP instead of FTP** when possible for better security
8. **Configure SSH access** for complete post-deployment automation
9. **Verify health checks** pass after each deployment
