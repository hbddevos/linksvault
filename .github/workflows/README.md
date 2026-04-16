# GitHub Actions Workflows

This directory contains automated CI/CD workflows for the linksvault2 project.

## Available Workflows

### 1. CI (Continuous Integration) - `ci.yml`
**Triggers:** Push or PR to `main` and `develop` branches

**Jobs:**
- ✅ **Tests**: Runs test suite on PHP 8.3 and 8.4 with MySQL and SQLite
- ✅ **Code Quality**: Checks code style with Laravel Pint and static analysis with PHPStan
- ✅ **Frontend Build**: Validates Vite build process and asset compilation

### 2. CD (Continuous Deployment) - `cd.yml`
**Triggers:** Push to `main` branch or manual trigger

**Features:**
- 🚀 Automated SSH deployment to production server
- 📦 Dependency installation (Composer + NPM)
- 🗄️ Database migrations
- ⚡ Configuration caching
- 🔄 Service restarts (PHP-FPM, Queue workers)
- 📊 Deployment status notifications

**Required Secrets:**
- Database credentials (`DB_*`)
- SSH access (`DEPLOY_HOST`, `DEPLOY_USERNAME`, `DEPLOY_SSH_KEY`, etc.)

### 3. Laravel Forge Deployment - `deploy-forge.yml`
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

## Quick Start

### For SSH Deployment (cd.yml)

1. Generate SSH key pair:
   ```bash
   ssh-keygen -t ed25519 -C "github-actions@linksvault2"
   ```

2. Add public key to your server:
   ```bash
   ssh-copy-id -i ~/.ssh/linksvault2_deploy.pub user@your-server.com
   ```

3. Add secrets to GitHub repository:
   - Go to Settings → Secrets and variables → Actions
   - Add all required secrets (see `docs/CI_CD_SETUP.md`)

4. Push to `main` branch to trigger deployment

### For Laravel Forge (deploy-forge.yml)

1. Get your Forge API token from Forge dashboard
2. Find your Server ID and Site ID in Forge
3. Add these secrets to GitHub:
   - `FORGE_API_TOKEN`
   - `FORGE_SERVER_ID`
   - `FORGE_SITE_ID`
   - `APP_URL`

4. Push to `main` branch

## Manual Triggers

You can manually run any workflow:
1. Go to **Actions** tab in GitHub
2. Select the workflow
3. Click **Run workflow**
4. Choose branch and click **Run workflow**

## Monitoring

View workflow progress and logs:
- Navigate to **Actions** tab
- Click on a workflow run
- Expand jobs to see detailed logs

## Customization

See `docs/CI_CD_SETUP.md` for:
- Detailed configuration guide
- Troubleshooting tips
- Security best practices
- Customization options

## Workflow Status Badges

Add these to your README.md:

```markdown
![CI](https://github.com/YOUR_USERNAME/linksvault2/actions/workflows/ci.yml/badge.svg)
![CD](https://github.com/YOUR_USERNAME/linksvault2/actions/workflows/cd.yml/badge.svg)
```
