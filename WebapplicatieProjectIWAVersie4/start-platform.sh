#!/usr/bin/env bash
set -e
(cd "$(dirname "$0")/dashboard-app" && php -S localhost:8080 "$PWD/server-router.php")
