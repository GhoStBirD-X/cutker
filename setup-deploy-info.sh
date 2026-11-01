#!/bin/bash

echo "================================================"
echo "  Setup Info untuk GitHub Actions Deploy"
echo "================================================"
echo ""

# 1. URL Repo dari git remote
REPO_URL=$(git remote get-url origin 2>/dev/null | sed 's/git@github.com:/https:\/\/github.com\//' | sed 's/\.git$//')
if [ -z "$REPO_URL" ]; then
    REPO_URL="tidak terdeteksi"
fi

# 2. Branch saat ini
CURRENT_BRANCH=$(git branch --show-current 2>/dev/null || echo "tidak terdeteksi")

# 3. Semua branch yang ada
ALL_BRANCHES=$(git branch -a 2>/dev/null | grep -v HEAD | sed 's/remotes\/origin\///' | sed 's/^\*//' | tr -d ' ' | sort -u | tr '\n' ', ' | sed 's/,$//')

# 4. Cek package.json untuk build script
BUILD_SCRIPT=$(cat package.json 2>/dev/null | grep '"build"' | head -1 | sed 's/.*"build": "\(.*\)".*/\1/')
if [ -z "$BUILD_SCRIPT" ]; then
    BUILD_SCRIPT="tidak terdeteksi"
fi

# 5. Cek node version
NODE_VERSION=$(node -v 2>/dev/null || echo "tidak terdeteksi")

# 6. Cek npm/pnpm/yarn
if [ -f "pnpm-workspace.yaml" ] || [ -f "pnpm-lock.yaml" ]; then
    PKG_MANAGER="pnpm"
elif [ -f "yarn.lock" ]; then
    PKG_MANAGER="yarn"
else
    PKG_MANAGER="npm"
fi

# Output hasil
echo "================================================"
echo "  OUTPUT - Salin dan kirim ke Claude"
echo "================================================"
echo ""
echo "Repo URL       : $REPO_URL"
echo "Branch aktif   : $CURRENT_BRANCH"
echo "Semua branch   : $ALL_BRANCHES"
echo "Build script   : $BUILD_SCRIPT"
echo "Node version   : $NODE_VERSION"
echo "Package manager: $PKG_MANAGER"
echo ""
echo "================================================"
