# CI/CD Setup Guide

## Overview

This project uses GitHub Actions for Continuous Integration and Continuous Deployment. The workflows are designed to be robust and handle common production scenarios, especially database migrations that may not need to run on every deployment.

**Deployment Method:** FTP/SFTP with optional SSH for post-deployment tasks

## Workflows

### 1. CI Workflow (`ci.yml`)
**Triggers:** Push or Pull Request to `main` or `develop` branches

**Jobs:**
- **Tests**: Runs test suite on PHP 8.3 and 8.4 with both MySQL and SQLite
- **Code Quality**: Checks code style (Laravel Pint) and static analysis (PHPStan)
- **Frontend Build**: Validates Vite build process

**Key Features:**
- Non-blocking migrations: If migrations fail (e.g., already applied), the workflow continues
- Parallel test execution for faster feedback
- Cached dependencies for improved performance

### 2. CD Workflow (`cd.yml`)
**Triggers:** Push to `main` branch or manual dispatch

**Process:**
1. Build application (PHP + Node.js)
2. Run migrations (non-blocking)
3. **Deploy files via FTP/SFTP**
4. Execute remote commands via SSH (optional, for post-deployment tasks)
5. Notify deployment status

**Key Features:**
- FTP/SFTP deployment using `SamKirkland/FTP-Deploy-Action`
- Migrations won't stop deployment if they fail
- Optional SSH access for running Composer, migrations, cache, and service restarts
- Configuration caching for optimal performance

### 3. Complete CI/CD Pipeline (`cicd.yml`) ⭐ RECOMMENDED
**Triggers:** Push to `main`/`develop`, Pull Requests, or manual dispatch

**Phases:**

#### Phase 1: Validation & Testing
- Code quality checks (Pint, PHPStan)
- Test suite on multiple PHP versions
- Frontend build verification

#### Phase 2: Production Deployment
- Dependency installation (production mode)
- **Non-blocking migrations** with detailed logging
- **File deployment via FTP/SFTP**
- Remote server commands execution via SSH (optional)
- Health check with retries
- Deployment summary generation

#### Phase 3: Notifications
- Status notifications (configurable for Slack, Discord, etc.)

## Migration Handling Strategy

### Why Non-Blocking Migrations?

In production environments, migrations don't need to run on every deployment because:
- No new migrations may exist
- Migrations might have been applied manually
- Database schema could already be up-to-date
- Temporary connection issues shouldn't block deployment

### Implementation

All workflows use this pattern:

```yaml
- name: Run database migrations (non-blocking)
  continue-on-error: true
  run: |
    echo "🔄 Attempting to run database migrations..."
    if php artisan migrate --force; then
      echo "✅ Migrations executed successfully"
    else
      echo "⚠️ Migrations failed or already up-to-date - continuing deployment"
    fi
```

This ensures:
- ✅ Deployment continues even if migrations fail
- ✅ Clear logging of what happened
- ✅ No false negatives in CI/CD pipeline
- ✅ Manual migration capability when needed

## Required GitHub Secrets

Configure these secrets in your repository settings (`Settings > Secrets and variables > Actions`):

### FTP/SFTP Configuration (Required)

| Secret Name | Description | Example |
|------------|-------------|---------|
| `FTP_SERVER` | FTP/SFTP server hostname | `ftp.example.com` or `192.168.1.100` |
| `FTP_USERNAME` | FTP username | `user@example.com` |
| `FTP_PASSWORD` | FTP password | Your FTP password |
| `FTP_PORT` | FTP port (21 for FTP, 22 for SFTP) | `21` or `22` |
| `FTP_PROTOCOL` | Protocol type | `ftp` or `sftp` (default: ftp) |
| `FTP_REMOTE_PATH` | Remote directory path | `/public_html/` or `/var/www/linksvault/` |

### SSH Configuration (Optional - for post-deployment tasks)

| Secret Name | Description | Example |
|------------|-------------|---------|
| `SSH_HOST` | SSH server hostname | `example.com` or IP address |
| `SSH_USERNAME` | SSH username | `deploy` or `ubuntu` |
| `SSH_KEY` | Private SSH key | Content of private key file |
| `SSH_PORT` | SSH port | `22` |

**Note:** SSH is optional but recommended for running Composer, migrations, cache clearing, and service restarts after FTP deployment.

### Database Configuration

| Secret Name | Description |
|------------|-------------|
| `DB_CONNECTION` | Database driver (mysql, pgsql, sqlite) |
| `DB_HOST` | Database host |
| `DB_PORT` | Database port |
| `DB_DATABASE` | Database name |
| `DB_USERNAME` | Database username |
| `DB_PASSWORD` | Database password |

### Application Configuration

| Secret Name | Description | Example |
|------------|-------------|---------|
| `APP_URL` | Application URL for health checks | `https://linksvault.com` |

## FTP/SFTP Setup

### For FTP Deployment

1. **Get FTP credentials from your hosting provider:**
   - Server address
   - Username
   - Password
   - Port (usually 21)
   - Remote path to your application

2. **Add FTP secrets to GitHub:**
   - Go to `Settings > Secrets and variables > Actions`
   - Add all FTP-related secrets listed above

### For SFTP Deployment (Recommended)

SFTP is more secure than FTP. To use SFTP:

1. Set `FTP_PROTOCOL` to `sftp`
2. Set `FTP_PORT` to `22` (or your SFTP port)
3. Use your SFTP credentials

### Optional: SSH Access for Post-Deployment Tasks

After FTP deployment, you may want to run server-side commands. Configure SSH:

1. **Generate SSH key pair:**
   ```bash
   ssh-keygen -t rsa -b 4096 -C "github-actions@linksvault" -f ~/.ssh/github_actions
   ```

2. **Add public key to your server:**
   ```bash
   # On your production server
   cat ~/.ssh/github_actions.pub >> ~/.ssh/authorized_keys
   chmod 600 ~/.ssh/authorized_keys
   ```

3. **Add private key to GitHub Secrets:**
   ```bash
   cat ~/.ssh/github_actions
   ```
   Paste as `SSH_KEY` secret value.

4. **Add other SSH secrets:**
   - `SSH_HOST`: Your server hostname
   - `SSH_USERNAME`: SSH username
   - `SSH_PORT`: SSH port (usually 22)

## Deployment Process

### What Gets Deployed via FTP

The workflow uploads all files **except**:
- `.git` directories
- `node_modules`
- `.env` file (for security)
- `.github` workflows
- Test files
- Cache and log files

### Post-Deployment Tasks (via SSH)

If SSH is configured, the workflow will:
1. Install PHP dependencies via Composer
2. Run database migrations (non-blocking)
3. Clear and cache Laravel configurations
4. Restart queue workers
5. Restart PHP-FPM service

## Environment Protection

For the `cicd.yml` workflow, you can configure environment protection:

1. Go to `Settings > Environments`
2. Create a `production` environment
3. Enable "Required reviewers" if needed
4. Add branch protection rules

## Manual Deployment

You can trigger deployments manually:

1. Go to `Actions` tab in GitHub
2. Select the desired workflow
3. Click "Run workflow"
4. Choose the branch
5. Click "Run workflow"

## Troubleshooting

### FTP Connection Issues

If FTP deployment fails:

1. **Verify credentials:**
   - Test FTP connection manually using FileZilla or command line
   - Ensure firewall allows FTP/SFTP connections

2. **Check passive mode:**
   - Some servers require passive mode
   - Contact your hosting provider if needed

3. **Verify remote path:**
   - Ensure `FTP_REMOTE_PATH` exists
   - Check write permissions

### SFTP Connection Issues

If SFTP fails:

1. Verify SSH key format (should be OpenSSH format)
2. Check that the public key is added to `~/.ssh/authorized_keys`
3. Ensure correct port (usually 22)
4. Test connection manually:
   ```bash
   sftp -P 22 username@server.com
   ```

### Migrations Failing

If migrations consistently fail:

1. **Check migration status:**
   ```bash
   php artisan migrate:status
   ```

2. **Run migrations manually:**
   ```bash
   php artisan migrate --force
   ```

3. **Check logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Post-Deployment SSH Issues

If SSH commands fail:

1. Check SSH connectivity:
   ```bash
   ssh -i ~/.ssh/github_actions user@example.com
   ```

2. Verify user has necessary permissions
3. Check that required commands are available (composer, php, sudo)

### Health Check Failures

If health check fails but site is working:

1. Increase sleep time in health check step
2. Adjust `MAX_RETRIES` value
3. Check if URL is accessible from GitHub Actions runners

## Best Practices

1. **Always test on develop branch first** before merging to main
2. **Review migration changes** before deploying to production
3. **Backup database** before major migrations
4. **Monitor deployment logs** for any warnings
5. **Use environment protection** for production deployments
6. **Keep secrets updated** and rotate passwords/keys periodically
7. **Use SFTP instead of FTP** when possible for better security
8. **Configure SSH access** for complete post-deployment automation

## Security Considerations

### FTP vs SFTP

- **FTP**: Transmits data in plain text (less secure)
- **SFTP**: Encrypted connection (recommended)

If your hosting supports SFTP, always use it by setting:
```
FTP_PROTOCOL=sftp
FTP_PORT=22
```

### Protecting Sensitive Files

The workflow automatically excludes sensitive files:
- `.env` (contains database credentials, API keys)
- `.git` directories
- Test files
- Logs and caches

### SSH Key Security

- Use strong passphrases for SSH keys
- Rotate keys periodically
- Limit SSH access to specific IPs if possible
- Use dedicated deployment user with minimal permissions

## Monitoring

After deployment, verify:

- ✅ Application responds correctly
- ✅ Database migrations applied (if any)
- ✅ Queue workers running
- ✅ Cache cleared and rebuilt
- ✅ No errors in Laravel logs

## Customization

To customize workflows for your needs:

1. Modify timeout values
2. Add additional notification channels
3. Adjust PHP/Node versions
4. Add custom deployment steps
5. Configure different strategies per environment
6. Modify FTP exclude patterns

## Support

For issues or questions about CI/CD setup:

1. Check workflow run logs in GitHub Actions
2. Review this documentation
3. Consult GitHub Actions documentation
4. Check Laravel deployment best practices
5. Contact your hosting provider for FTP/SFTP specific issues
