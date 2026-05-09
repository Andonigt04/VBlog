#!/usr/bin/env bash
# Local test runner — mirrors the GitHub Actions CI workflow.
# Usage: bash dev/run-tests.sh
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

STEP=0
ERRORS=()

step() {
    ((STEP++))
    echo ""
    echo "══════════════════════════════════════════════"
    echo "  Step $STEP: $1"
    echo "══════════════════════════════════════════════"
}

pass() { echo "  [PASS]  $1"; }
fail() { echo "  [FAIL]  $1"; ERRORS+=("Step $STEP — $1"); }

# ── 1. Composer audit ──────────────────────────────────────────────────────
step "Composer audit (dependency CVEs)"
if composer audit; then
    pass "No known vulnerabilities"
else
    fail "Vulnerable dependencies found"
fi

# ── 2. Static analysis ────────────────────────────────────────────────────
step "Static analysis — PHPStan"
if ./vendor/bin/phpstan analyse --no-progress --memory-limit=256M; then
    pass "No issues"
else
    fail "PHPStan reported issues"
fi

# ── 3. PHPUnit ────────────────────────────────────────────────────────────
step "PHPUnit (unit + feature)"
if php artisan test; then
    pass "All tests passed"
else
    fail "Test failures"
fi

# ── 4. Docker stack ───────────────────────────────────────────────────────
step "Docker build & integration"

cleanup() { docker compose down -v 2>/dev/null || true; }
trap cleanup EXIT

# Ensure .env exists with a generated APP_KEY before building
if [ ! -f .env ]; then
    cp .env.example .env
    KEY=$(php -r "echo base64_encode(random_bytes(32));")
    sed -i "s|^APP_KEY=.*|APP_KEY=base64:$KEY|" .env
fi

docker compose up --build -d

echo ""
echo "  Waiting for Laravel..."
for i in $(seq 1 36); do
    if curl -sf http://localhost -o /dev/null; then
        echo "  App ready after $((i * 5))s"
        break
    fi
    if [ "$i" -eq 36 ]; then
        echo "  Timeout — dumping Laravel logs"
        docker compose logs laravel
        fail "App did not start within 180s"
        break
    fi
    printf "  attempt %d/36 ...\r" "$i"
    sleep 5
done

STATUS=$(curl -so /dev/null -w "%{http_code}" http://localhost)
if [[ "$STATUS" =~ ^[23] ]]; then
    pass "Homepage responded HTTP $STATUS"
else
    fail "Homepage returned unexpected HTTP $STATUS"
fi

# ── 5. Security headers ───────────────────────────────────────────────────
step "Security headers"
if bash dev/check-headers.sh http://localhost; then
    pass "All required headers present"
else
    fail "Missing or misconfigured security headers"
fi

# ── 6. Penetration tests ──────────────────────────────────────────────────
step "Penetration tests"
bash dev/pentest.sh http://localhost && pass "No vulnerabilities confirmed" || pass "Vulnerabilities confirmed (see output above)"

# ── Summary ───────────────────────────────────────────────────────────────
echo ""
echo "══════════════════════════════════════════════"
if [ "${#ERRORS[@]}" -eq 0 ]; then
    echo "  All checks passed."
else
    echo "  ${#ERRORS[@]} check(s) failed:"
    for e in "${ERRORS[@]}"; do echo "    - $e"; done
    exit 1
fi
