#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}  🗑️  Git Hooks Uninstaller${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Check if we're in a git repository
if [ ! -d ".git" ]; then
    echo -e "${RED}✗ Error: Not a git repository${NC}"
    echo -e "${YELLOW}  Please run this script from the root of your git repository${NC}"
    exit 1
fi

echo -e "${BLUE}Uninstalling Git hooks...${NC}"
echo ""

# Array of hooks to uninstall
HOOKS=("pre-commit" "pre-push" "commit-msg")

REMOVED=0
NOT_FOUND=0

for HOOK in "${HOOKS[@]}"; do
    TARGET=".git/hooks/$HOOK"

    if [ -f "$TARGET" ]; then
        # Check if there's a backup to restore
        LATEST_BACKUP=$(ls -t ".git/hooks/${HOOK}.backup."* 2>/dev/null | head -n1)

        if [ -n "$LATEST_BACKUP" ]; then
            echo -e "${YELLOW}→ Restoring backup: $LATEST_BACKUP${NC}"
            mv "$LATEST_BACKUP" "$TARGET"
            echo -e "${GREEN}✓ Restored: $HOOK${NC}"
        else
            rm "$TARGET"
            echo -e "${GREEN}✓ Removed: $HOOK${NC}"
        fi

        REMOVED=$((REMOVED + 1))
    else
        echo -e "${YELLOW}⚠ Not found: $HOOK${NC}"
        NOT_FOUND=$((NOT_FOUND + 1))
    fi
done

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

if [ $REMOVED -gt 0 ]; then
    echo -e "${GREEN}✓ Successfully uninstalled $REMOVED hook(s)!${NC}"
else
    echo -e "${YELLOW}⚠ No hooks were uninstalled${NC}"
fi

if [ $NOT_FOUND -gt 0 ]; then
    echo -e "${YELLOW}  $NOT_FOUND hook(s) were not found${NC}"
fi

echo ""
echo -e "${BLUE}To reinstall hooks, run: ${GREEN}.githooks/install.sh${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

exit 0
