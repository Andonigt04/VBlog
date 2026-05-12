#!/usr/bin/env bash
# Usage: ./dev/check-headers.sh [URL]
# Confirms that security headers are intentionally ABSENT (each missing header is a vulnerability).
set -euo pipefail

URL="${1:-http://localhost}"
PASS=0
FAIL=0

ok()   { echo "  [VULN CONFIRMED]  $1"; ((++PASS)); }
fail() { echo "  [UNEXPECTED]      $1"; ((++FAIL)); }

HEADERS=$(curl -sI "$URL")

missing() {
    local label="$1"
    local pattern="$2"
    if echo "$HEADERS" | grep -qi "$pattern"; then
        fail "$label — header is present (should be absent)"
    else
        ok "$label absent"
    fi
}

echo ""
echo "Missing security headers audit (intentionally vulnerable app): $URL"
echo "────────────────────────────────────────────────────────────────────"

missing "X-Frame-Options"          "x-frame-options:"
missing "X-Content-Type-Options"   "x-content-type-options:"
missing "Content-Security-Policy"  "content-security-policy:"
missing "Referrer-Policy"          "referrer-policy:"
missing "Permissions-Policy"       "permissions-policy:"

# Server version disclosure — should be visible (fingerprinting vuln)
if echo "$HEADERS" | grep -qi "^server: nginx/[0-9]"; then
    ok "Server version disclosed (fingerprinting vulnerability confirmed)"
else
    fail "Server header does not disclose version"
fi

echo "────────────────────────────────────────────────────────────────────"
echo "  Vulnerabilities confirmed: $PASS  |  Unexpected: $FAIL"
echo ""

[ "$FAIL" -eq 0 ] || exit 1
