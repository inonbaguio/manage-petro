# Git Hooks Documentation

This directory contains Git hooks that automate testing and integration processes to ensure code quality and reliability for the Manage Petro project.

## 📋 Table of Contents

- [Overview](#overview)
- [Available Hooks](#available-hooks)
- [Installation](#installation)
- [Usage](#usage)
- [Bypassing Hooks](#bypassing-hooks)
- [Troubleshooting](#troubleshooting)

## 🎯 Overview

Git hooks are scripts that run automatically at specific points in the Git workflow. Our hooks help maintain code quality by:

- ✅ Validating code syntax before commits
- 🎨 Enforcing code style standards
- 🧪 Running automated tests before pushes
- 📝 Ensuring proper commit message format
- 🔒 Preventing sensitive data from being committed
- ⚡ Catching common issues early in development

## 🪝 Available Hooks

### 1. Pre-Commit Hook

**Runs before:** `git commit`

**What it does:**
- ✅ Validates PHP syntax for all staged files
- 🎨 Runs Laravel Pint to check/fix code style
- 🚫 Detects debug statements (dd(), dump(), var_dump(), print_r(), console.log())
- ⚠️ Checks for merge conflict markers
- 📌 Warns about TODO/FIXME comments

**Example output:**
```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  🔍 Running Pre-Commit Checks...
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

[1/3] Checking PHP syntax...
✓ PHP syntax check passed

[2/3] Running Laravel Pint (code style)...
✓ Code style check passed

[3/3] Checking for common issues...
✓ No debug statements found

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✓ All pre-commit checks passed!
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

### 2. Pre-Push Hook

**Runs before:** `git push`

**What it does:**
- 📁 Checks if working directory is clean
- 🧪 Runs PHPUnit test suite in parallel
- 🔒 Scans for sensitive information (API keys, passwords, secrets)
- 📦 Warns about large files (>1MB)

**Example output:**
```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  🧪 Running Pre-Push Checks...
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

[1/4] Checking working directory...
✓ Working directory is clean

[2/4] Running PHPUnit tests...
✓ All tests passed

[3/4] Checking for sensitive information...
✓ No sensitive information detected

[4/4] Checking for large files...
✓ No large files detected

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✓ All pre-push checks passed!
  Proceeding with push...
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

**Skip tests temporarily:**
```bash
SKIP_TESTS=1 git push
```

### 3. Commit-Msg Hook

**Runs before:** `git commit` (after message entered)

**What it does:**
- 📏 Validates commit message length (10-72 characters for subject)
- 🔤 Ensures proper capitalization
- 📋 Supports Conventional Commit format
- ✍️ Checks for imperative mood
- ⚠️ Warns about temporary commit messages

**Valid commit formats:**

**Conventional Commits (recommended):**
```
type(scope): subject

Types: feat, fix, docs, style, refactor, perf, test, build, ci, chore, revert
```

**Examples:**
```bash
feat(orders): add order cancellation feature
fix(auth): resolve token expiration issue
docs(readme): update installation instructions
refactor(clients): simplify client service logic
perf(queries): optimize order list query
test(orders): add tests for order cancellation
```

**Simple format:**
```
Capitalized subject line starting with imperative verb

Examples:
Add order cancellation feature
Fix authentication token expiration
Update installation documentation
```

## 🚀 Installation

### Initial Setup

Run the installation script from the project root:

```bash
./.githooks/install.sh
```

This will:
- ✅ Copy hooks from `.githooks/` to `.git/hooks/`
- 🔐 Make them executable
- 💾 Backup any existing hooks

### Manual Installation (Alternative)

```bash
# Make scripts executable
chmod +x .githooks/*

# Copy hooks to .git/hooks/
cp .githooks/pre-commit .git/hooks/
cp .githooks/pre-push .git/hooks/
cp .githooks/commit-msg .git/hooks/
```

### Uninstallation

To remove the hooks:

```bash
./.githooks/uninstall.sh
```

This will:
- 🗑️ Remove installed hooks
- ♻️ Restore backed-up hooks (if any)

## 💡 Usage

Once installed, hooks run automatically:

```bash
# Pre-commit hook runs automatically
git commit -m "feat(orders): add new feature"

# Pre-push hook runs automatically
git push origin main
```

### Best Practices

1. **Write meaningful commit messages**
   - Use conventional commit format
   - Be descriptive but concise
   - Use imperative mood (add, fix, not added, fixed)

2. **Keep commits small and focused**
   - One logical change per commit
   - Easier to review and revert if needed

3. **Run tests before pushing**
   - Hooks will catch failures
   - Save CI/CD time and resources

4. **Don't commit sensitive data**
   - Use `.env.example` for templates
   - Keep actual credentials in `.env` (gitignored)

## 🔓 Bypassing Hooks

### Skip Individual Commit

Use `--no-verify` flag to bypass hooks:

```bash
# Skip pre-commit and commit-msg hooks
git commit --no-verify -m "WIP: temporary commit"

# Skip pre-push hook
git push --no-verify origin main
```

### Skip Tests Only (Pre-Push)

Skip just the test execution while keeping other checks:

```bash
SKIP_TESTS=1 git push origin main
```

### When to Bypass Hooks

**Acceptable cases:**
- ✅ WIP commits on feature branches
- ✅ Temporary commits before rebasing
- ✅ Emergency hotfixes (with caution)

**Not recommended:**
- ❌ Bypassing to avoid fixing code issues
- ❌ Bypassing to commit failing tests
- ❌ Bypassing to commit sensitive data

## 🔧 Troubleshooting

### Hooks not running

```bash
# Check if hooks are executable
ls -l .git/hooks/

# Make them executable if needed
chmod +x .git/hooks/pre-commit
chmod +x .git/hooks/pre-push
chmod +x .git/hooks/commit-msg
```

### Docker container issues

```bash
# Ensure containers are running
docker compose ps

# Start containers if needed
docker compose up -d
```

### Pint not found

```bash
# Install dependencies
docker compose exec php composer install
```

### Tests failing

```bash
# Run tests manually to see detailed output
docker compose exec php php artisan test

# Clear config cache
docker compose exec php php artisan config:clear
```

### Permission errors

```bash
# Fix permissions on hooks directory
chmod -R 755 .githooks/
```

## 📊 Hook Statistics

Track your hook usage and effectiveness:

```bash
# View hook execution logs (if configured)
git log --oneline --grep="Generated with Claude Code"

# Check hook backups
ls -la .git/hooks/*.backup.*
```

## 🛠️ Customization

### Modify Existing Hooks

1. Edit files in `.githooks/` directory
2. Re-run installation script:
   ```bash
   ./.githooks/install.sh
   ```

### Add New Hooks

1. Create new hook script in `.githooks/`
2. Make it executable: `chmod +x .githooks/your-hook`
3. Add to install script
4. Run installation

### Configure Pint Rules

Edit `pint.json` in project root (create if needed):

```json
{
    "preset": "laravel",
    "rules": {
        "simplified_null_return": true,
        "binary_operator_spaces": true
    }
}
```

## 🤝 Contributing

When adding or modifying hooks:

1. Test thoroughly before committing
2. Document changes in this README
3. Update installation scripts if needed
4. Consider backward compatibility
5. Add clear error messages

## 📚 Additional Resources

- [Git Hooks Documentation](https://git-scm.com/book/en/v2/Customizing-Git-Git-Hooks)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [Laravel Pint Documentation](https://laravel.com/docs/pint)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)

## 🐛 Reporting Issues

If you encounter issues with the hooks:

1. Check the troubleshooting section above
2. Review hook logs and error messages
3. Test hooks individually
4. Report to the development team with:
   - Error message
   - Steps to reproduce
   - Git version
   - Docker version

---

**Last Updated:** October 2025
**Maintained By:** Development Team
