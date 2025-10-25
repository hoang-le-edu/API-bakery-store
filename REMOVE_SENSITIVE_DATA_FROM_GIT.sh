#!/bin/bash
# Script to remove sensitive data from Git history
# ⚠️ WARNING: This will rewrite Git history! 
# Make sure all team members are aware before running.

echo "⚠️  WARNING: This will rewrite Git history!"
echo "Make sure all team members pull and backup their work first."
echo ""
read -p "Continue? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Aborted."
    exit 1
fi

echo ""
echo "==== Step 1: Installing BFG Repo Cleaner ===="
echo "Download from: https://rtyley.github.io/bfg-repo-cleaner/"
echo "Or use git-filter-repo (recommended): pip install git-filter-repo"
echo ""

# Create a file with patterns to remove
cat > sensitive-patterns.txt << 'EOF'
AIzaSyD-Ro7xqbcAy8azy4ROfppORgvOG_1wm8A
firebase_api_key
VITE_FIREBASE_API_KEY
EOF

echo "==== Step 2: Removing sensitive files from history ===="
echo ""
echo "Option A: Using BFG (faster)"
echo "----------------------------------------"
echo "java -jar bfg.jar --delete-files 'FIREBASE_AUTH_GUIDE.md' ."
echo "java -jar bfg.jar --delete-files 'AZURE_DEPLOYMENT_CHECKLIST.md' ."
echo "java -jar bfg.jar --delete-files '*_GUIDE.md' ."
echo "java -jar bfg.jar --replace-text sensitive-patterns.txt ."
echo ""

echo "Option B: Using git filter-repo (recommended)"
echo "----------------------------------------"
echo "git filter-repo --invert-paths --path FIREBASE_AUTH_GUIDE.md"
echo "git filter-repo --invert-paths --path AZURE_DEPLOYMENT_CHECKLIST.md"
echo "git filter-repo --invert-paths --path 'SWAGGER*.md'"
echo "git filter-repo --invert-paths --path 'HOW_TO*.md'"
echo "git filter-repo --invert-paths --path '*_SUMMARY.md'"
echo "git filter-repo --invert-paths --path 'API_DOCUMENTATION_TODO.md'"
echo ""

echo "Option C: Using git filter-branch (slowest, built-in)"
echo "----------------------------------------"
cat << 'EOF2'
git filter-branch --force --index-filter \
  "git rm --cached --ignore-unmatch FIREBASE_AUTH_GUIDE.md \
   AZURE_DEPLOYMENT_CHECKLIST.md \
   SWAGGER*.md \
   HOW_TO*.md \
   NEW_LOGIN_API_SUMMARY.md \
   API_DOCUMENTATION_TODO.md" \
  --prune-empty --tag-name-filter cat -- --all
EOF2

echo ""
echo "==== Step 3: Force push to remote (DANGEROUS!) ===="
echo "git push origin --force --all"
echo "git push origin --force --tags"
echo ""

echo "==== Step 4: Notify team ===="
echo "All team members need to re-clone the repository:"
echo "  cd .."
echo "  rm -rf API-bakery-store"
echo "  git clone <repo-url>"
echo ""

echo "==== Step 5: Verify ===="
echo "git log --all --full-history -- FIREBASE_AUTH_GUIDE.md"
echo "# Should return nothing"
echo ""

echo "Sensitive patterns saved to: sensitive-patterns.txt"
echo "Review and run the commands manually as needed."

