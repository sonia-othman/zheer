#!/bin/bash

# Create git repo folder & set default git config
echo "=========================================="
echo 'Setting up git...'
echo "=========================================="

sudo git config --global init.defaultBranch main
sudo git config --global user.email "sonia.osman99@gmail.com"
sudo git config --global user.name "sonia-othman"

echo "=========================================="
echo 'git setup succeeded'
echo "=========================================="

echo "=========================================="
echo 'Installing Node.js, npm, and Yarn!'
echo "=========================================="

# Print colorful header
echo -e "\033[1;34m==== Node.js, npm, and Yarn Installation Script ====\033[0m"

# Error handling
set -e # Exit immediately if a command exits with non-zero status
trap 'echo -e "\033[1;31mAn error occurred during installation. Please check the output above.\033[0m"' ERR

# Install NVM (Node Version Manager)
echo -e "\033[1;36mInstalling NVM...\033[0m"
cd ~ && curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.5/install.sh | bash || {
    echo -e "\033[1;31mFailed to install NVM. Check your internet connection and try again.\033[0m"
    exit 1
}

# Load NVM
echo -e "\033[1;36mLoading NVM...\033[0m"
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"                   # This loads nvm
[ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion" # This loads nvm bash_completion

# Verify NVM installation
if ! command -v nvm &>/dev/null; then
    echo -e "\033[1;31mNVM installation failed or NVM is not in PATH.\033[0m"
    echo -e "\033[1;33mTry running: source ~/.bashrc\033[0m"
    exit 1
fi

# Install latest Node.js
echo -e "\033[1;36mInstalling latest Node.js...\033[0m"
nvm install node || {
    echo -e "\033[1;31mFailed to install Node.js\033[0m"
    exit 1
}

# Set default Node.js version
echo -e "\033[1;36mSetting up Node.js environment...\033[0m"
nvm alias default node
nvm use default

# Display Node.js version
NODE_VERSION=$(node -v)
echo -e "\033[1;32mNode.js version $NODE_VERSION has been installed\033[0m"

# Update npm to latest version
echo -e "\033[1;36mUpdating npm to latest version...\033[0m"
npm install -g npm@latest || {
    echo -e "\033[1;31mFailed to update npm\033[0m"
    exit 1
}

# Display npm version
NPM_VERSION=$(npm -v)
echo -e "\033[1;32mnpm version $NPM_VERSION has been installed\033[0m"

# Install Yarn
echo -e "\033[1;36mInstalling Yarn...\033[0m"
npm install -g yarn || {
    echo -e "\033[1;31mFailed to install Yarn\033[0m"
    exit 1
}

# Display Yarn version
YARN_VERSION=$(yarn -v)
echo -e "\033[1;32mYarn version $YARN_VERSION has been installed\033[0m"

# Final success message
echo -e "\033[1;42m==================================================\033[0m"
echo -e "\033[1;42m  Node.js, npm, and Yarn successfully installed!  \033[0m"
echo -e "\033[1;42m==================================================\033[0m"
