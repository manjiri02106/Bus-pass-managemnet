# Set error action preference
$ErrorActionPreference = "Stop"

Write-Host "=== Bus Pass Management Repo Setup Script ===" -ForegroundColor Cyan

# 1. Check if git is installed
try {
    $gitVersion = git --version
    Write-Host "Found git: $gitVersion" -ForegroundColor Green
} catch {
    Write-Host "Error: Git is not installed or not in system PATH." -ForegroundColor Red
    Write-Host "Please install Git and try again." -ForegroundColor Yellow
    Exit
}

# 2. Clone main branch if not exists
$repoUrl = "https://github.com/manjiri02106/Bus-pass-managemnet.git"
if (-not (Test-Path "main")) {
    Write-Host "Cloning 'main' branch..." -ForegroundColor Cyan
    git clone $repoUrl main
} else {
    Write-Host "'main' directory already exists." -ForegroundColor Yellow
}

# 3. Enter main directory and set up worktrees
cd main

Write-Host "Fetching all remote branches..." -ForegroundColor Cyan
git fetch --all --prune

# Get list of remote branches
$branches = git branch -r | ForEach-Object { $_.Trim() }

Write-Host "Setting up worktrees..." -ForegroundColor Cyan
foreach ($ref in $branches) {
    if ($ref -match "^origin/(.+)$") {
        $branch = $Matches[1]
        
        # Skip HEAD pointer and main branch
        if ($branch -eq "HEAD" -or $branch -eq "main" -or $branch -match "^HEAD ") {
            continue
        }
        
        # Sanitize name for directory (replace / with -)
        $dirName = $branch -replace '/', '-'
        $targetPath = "../$dirName"
        
        if (Test-Path $targetPath) {
            Write-Host "Worktree directory already exists: $dirName" -ForegroundColor Yellow
            continue
        }
        
        Write-Host "Adding worktree for branch '$branch' at '$targetPath'..." -ForegroundColor Green
        try {
            # Let git create worktree for the branch
            git worktree add $targetPath $branch
        } catch {
            Write-Host "Could not add worktree directly. Trying to create local branch tracking origin/$branch..." -ForegroundColor Yellow
            try {
                git branch $branch "origin/$branch"
                git worktree add $targetPath $branch
            } catch {
                Write-Host "Warning: Failed to create worktree for branch: $branch. Error: $_" -ForegroundColor Red
            }
        }
    }
}

Write-Host "`n=== Setup Complete! ===" -ForegroundColor Green
Write-Host "All branches have been successfully pulled and linked for testing." -ForegroundColor Green
Write-Host "List of folders in workspace:" -ForegroundColor Cyan
Get-ChildItem -Directory .. | Select-Object Name
