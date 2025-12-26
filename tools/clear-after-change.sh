#!/usr/bin/env bash
# Simple helper to run Laravel clear commands
# Usage: tools/clear-after-change.sh [files...]

php artisan optimize:clear

# if specific file types need special clears in future, inspect $@ and act accordingly
# e.g. if changes to resources/views -> php artisan view:clear
