# CI/CD Configuration Guide

This guide explains how to set up and configure the CI/CD workflows for the linksvault2 project.

## Overview

The project includes two GitHub Actions workflows:

1. **CI (Continuous Integration)** - `.github/workflows/ci.yml`
   - Runs on every push and pull request to `main` and `develop` branches
   - Executes tests on PHP 8.3 and 8.4
   - Performs code quality checks (Pint, PHPStan)
   - Validates frontend build process

2. **CD (Continuous Deployment)** - `.github/workflows/cd.yml`
   - Runs on pushes to `main` branch or manual trigger
   - Builds and deploys the application to production
   - Runs migrations and caches configurations
   - Restarts services automatically

## Required GitHub Secrets

To enable the CD workflow, you need to configure the following secrets in your GitHub repository:

### Database Configuration
- `DB_CONNECTION` - Database driver (e.g., `mysql`, `pgsql`)
- `DB_HOST` - Database host address
- `DB_PORT` - Database port (e.g., `3306` for MySQL)
- `DB_DATABASE` - Database name
- `DB_USERNAME` - Database username
- `DB_PASSWORD` - Database password

### Deployment Server Configuration
- `DEPLOY_HOST` - Production server hostname or IP address
- `DEPLOY_USERNAME` - SSH username for the server
- `DEPLOY_SSH_KEY` - Private SSH key for authentication (generate with `ssh-keygen`)
- `DEPLOY_PORT` - SSH port (default: `22`)
- `DEPLOY_PATH` - Absolute path to the application on the server (e.g., `/var/www/linksvault2`)

## How to Add Secrets

1. Go to your GitHub repository
2. Navigate to **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret**
4. Add each secret with its corresponding value

## SSH Key Setup

### Generate SSH Key Pair

```bash
ssh-keygen -t ed25519 -C "github-actions@linksvault2" -f ~/.ssh/linksvault2_deploy
```

### Add Public Key to Server

```bash
# Copy the public key content
cat ~/.ssh/linksvault2_deploy.pub

# On your server, add it to authorized_keys
echo "PUBLIC_KEY_CONTENT" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

### Add Private Key to GitHub Secrets

```bash
# Copy the private key content (include BEGIN and END lines)
cat ~/.ssh/linksvault2_deploy
```

Paste the entire private key content into the `DEPLOY_SSH_KEY` secret.

## Workflow Triggers

### CI Workflow
- **Automatic**: Triggered on every push and pull request to `main` or `develop`
- **Manual**: Not configured (runs automatically)

### CD Workflow
- **Automatic**: Triggered on push to `main` branch
- **Manual**: Can be triggered from GitHub Actions tab using "Run workflow" button

## Customization Options

### Modify PHP Versions
Edit `ci.yml` to change tested PHP versions:
```yaml
matrix:
  php: ['8.3', '8.4']  # Add or remove versions
```

### Add Code Quality Tools
Install and configure additional tools:
```bash
composer require --dev laravel/pint phpstan/phpstan
```

### Configure Deployment Branch
Change the deployment branch in `cd.yml`:
```yaml
on:
  push:
    branches: [production]  # Change from main to your preferred branch
```

### Add Notification Channels
Integrate Slack, Discord, or email notifications by adding steps to the workflows.

## Troubleshooting

### Common Issues

1. **SSH Connection Failed**
   - Verify the SSH key is correctly added to GitHub secrets
   - Check that the public key is in the server's `~/.ssh/authorized_keys`
   - Ensure the server allows SSH connections from GitHub Actions IPs

2. **Database Migration Failed**
   - Verify database credentials in secrets
   - Ensure the database exists and is accessible
   - Check firewall rules allow connections from GitHub Actions

3. **Build Failures**
   - Review test output in the Actions tab
   - Check if all dependencies are properly installed
   - Verify environment configuration

### Viewing Workflow Logs

1. Go to the **Actions** tab in your GitHub repository
2. Click on a workflow run
3. Click on a specific job to see detailed logs
4. Expand each step to view output

## Best Practices

1. **Test Locally First**: Run `composer run test` before pushing
2. **Use Feature Branches**: Create PRs for review before merging to main
3. **Monitor Deployments**: Check workflow logs after each deployment
4. **Backup Before Deploy**: Consider adding a backup step to the CD workflow
5. **Environment Parity**: Keep `.env.example` updated with all required variables

## Security Considerations

- Never commit `.env` files or secrets to the repository
- Rotate SSH keys periodically
- Use separate database credentials for production
- Enable branch protection rules for `main` branch
- Review workflow runs regularly for unauthorized access

## Additional Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Laravel Deployment Guide](https://laravel.com/docs/deployment)
- [SSH Key Best Practices](https://www.ssh.com/academy/ssh/key)
