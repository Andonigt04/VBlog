#!/usr/bin/env bash
# Usage: ./dev/check-headers.sh [URL]
# Checks that required security headers are present and correct.
set -euo pipefail

URL="${1:-http://localhost}"
PASS=0
FAIL=0

ok()   { echo "  [PASS]  $1"; ((PASS++)); }
fail() { echo "  [FAIL]  $1"; ((FAIL++)); }

HEADERS=$(curl -sI "$URL")

check() {
    local label="$1"
    local pattern="$2"
    if echo "$HEADERS" | grep -qi "$pattern"; then
        ok "$label"
    else
        fail "$label — not found or wrong value"
    fi
}

echo ""
echo "Security header audit: $URL"
echo "────────────────────────────────────────"

check "X-Frame-Options"          "x-frame-options:"
check "X-Content-Type-Options"   "x-content-type-options: nosniff"
check "Content-Security-Policy"  "content-security-policy:"
check "Referrer-Policy"          "referrer-policy:"
check "Permissions-Policy"       "permissions-policy:"

# HSTS is only meaningful over HTTPS — skip on plain HTTP
if [[ "$URL" == https://* ]]; then
    check "Strict-Transport-Security" "strict-transport-security:"
fi

# Warn if server version is disclosed
if echo "$HEADERS" | grep -qi "^server: nginx/[0-9]"; then
    fail "Server version disclosed in Server header (fingerprinting risk)"
else
    ok "Server header does not disclose version"
fi

echo "────────────────────────────────────────"
echo "  Passed: $PASS  |  Failed: $FAIL"
echo ""

[ "$FAIL" -eq 0 ] || exit 1
