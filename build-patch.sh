#!/bin/bash
# Workaround for https://github.com/vercel-community/php/issues/650
# Vercel's Rust/Node bootstrap (rolled out 2026-08-10) fails to resolve the
# AWS Lambda "<module>.<export>" handler convention used by vercel-php,
# crashing every invocation with ERR_MODULE_NOT_FOUND on "launcher.launcher".
# Patch the installed runtime to use the plain file-path handler instead.
set -e

PHP_FILE=$(find /vercel -name "index.js" -path "*vercel-php*" 2>/dev/null | head -1)

if [ -n "$PHP_FILE" ]; then
  sed -i "s/handler: 'launcher.launcher'/handler: 'launcher.js'/g" "$PHP_FILE"
  echo "Patched vercel-php handler in $PHP_FILE"
else
  echo "vercel-php runtime not found yet, skipping patch (may already be fixed upstream)"
fi
