# Git Hooks Quick Reference

## 🚀 Quick Start

```bash
# Install hooks
make hooks-install

# Check hooks status
make hooks-info
```

## 📝 Commit Message Format

### Conventional Commits (Recommended)

```
type(scope): subject

feat(orders): add order cancellation
fix(auth): resolve token expiration
docs(readme): update setup instructions
refactor(clients): simplify service logic
perf(queries): optimize database queries
test(orders): add cancellation tests
```

**Valid types:** feat, fix, docs, style, refactor, perf, test, build, ci, chore, revert

### Simple Format

```
Add order cancellation feature
Fix authentication token bug
Update README installation steps
```

## 🔓 Bypass Hooks

```bash
# Skip all hooks for a commit
git commit --no-verify -m "message"

# Skip hooks for a push
git push --no-verify

# Skip only tests (keep other checks)
SKIP_TESTS=1 git push
```

## ⚡ Quick Commands

```bash
# Check code style
make pint-test

# Fix code style
make pint

# Run tests
make test

# View hook logs
git log --oneline --grep="Generated with Claude Code"
```

## 🔍 What Gets Checked

### Pre-Commit (on `git commit`)
- ✅ PHP syntax
- 🎨 Code style (Laravel Pint)
- 🚫 Debug statements
- ⚠️ Merge conflicts
- 📌 TODO comments

### Pre-Push (on `git push`)
- 🧪 PHPUnit tests
- 🔒 Sensitive data
- 📦 Large files
- 📁 Clean working directory

### Commit-Msg (on `git commit`)
- 📏 Message length
- 🔤 Capitalization
- ✍️ Imperative mood
- 📋 Format validation

## 🐛 Troubleshooting

```bash
# Hooks not running? Check if executable
ls -l .git/hooks/

# Make executable
chmod +x .git/hooks/pre-commit
chmod +x .git/hooks/pre-push
chmod +x .git/hooks/commit-msg

# Reinstall hooks
make hooks-uninstall
make hooks-install

# Clear caches before testing
make cache-clear
```

## 📚 More Info

- Full documentation: `.githooks/README.md`
- Uninstall hooks: `make hooks-uninstall`
- Report issues to the development team
