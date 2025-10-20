#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}  🔧 Git Hooks Installer${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Check if we're in a git repository
if [ ! -d ".git" ]; then
    echo -e "${RED}✗ Error: Not a git repository${NC}"
    echo -e "${YELLOW}  Please run this script from the root of your git repository${NC}"
    exit 1
fi

# Check if .githooks directory exists
if [ ! -d ".githooks" ]; then
    echo -e "${RED}✗ Error: .githooks directory not found${NC}"
    echo -e "${YELLOW}  Please ensure .githooks directory exists with hook files${NC}"
    exit 1
fi

echo -e "${BLUE}Installing Git hooks...${NC}"
echo ""

# Array of hooks to install
HOOKS=("pre-commit" "pre-push" "commit-msg")

INSTALLED=0
FAILED=0

for HOOK in "${HOOKS[@]}"; do
    SOURCE=".githooks/$HOOK"
    TARGET=".git/hooks/$HOOK"

    if [ -f "$SOURCE" ]; then
        # Backup existing hook if it exists
        if [ -f "$TARGET" ]; then
            BACKUP="${TARGET}.backup.$(date +%Y%m%d_%H%M%S)"
            echo -e "${YELLOW}→ Backing up existing $HOOK to $BACKUP${NC}"
            mv "$TARGET" "$BACKUP"
        fi

        # Copy the hook
        cp "$SOURCE" "$TARGET"

        # Make it executable
        chmod +x "$TARGET"

        echo -e "${GREEN}✓ Installed: $HOOK${NC}"
        INSTALLED=$((INSTALLED + 1))
    else
        echo -e "${RED}✗ Not found: $SOURCE${NC}"
        FAILED=$((FAILED + 1))
    fi
done

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ Successfully installed $INSTALLED hook(s)!${NC}"
    echo ""
    echo -e "${BLUE}Available hooks:${NC}"
    echo -e "  • ${GREEN}pre-commit${NC}  - Code quality checks (syntax, style, debug statements)"
    echo -e "  • ${GREEN}pre-push${NC}    - Run tests and security checks before pushing"
    echo -e "  • ${GREEN}commit-msg${NC}  - Validate commit message format"
    echo ""
    echo -e "${YELLOW}Tips:${NC}"
    echo -e "  • Use ${BLUE}--no-verify${NC} flag to skip hooks when needed"
    echo -e "  • Set ${BLUE}SKIP_TESTS=1${NC} to skip tests in pre-push hook"
    echo -e "  • Hooks run automatically on commit/push operations"
    echo ""
    echo -e "${GREEN}Happy coding! 🚀${NC}"
else
    echo -e "${YELLOW}⚠ Installation completed with $FAILED error(s)${NC}"
    echo -e "${YELLOW}  Successfully installed: $INSTALLED hook(s)${NC}"
    exit 1
fi

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

exit 0
