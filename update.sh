#!/bin/bash

# Navigate to project dir
cd "$(dirname "$0")"

# Make sure any local changes to the file structure are reverted
git clean --force
git reset --hard
git checkout master

# Get the new application files from git
git pull

# Clear the cache
rm -rf ./cache

