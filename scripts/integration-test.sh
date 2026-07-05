#!/usr/bin/env bash
#
# Dawn integration test: prove that Dusk-style browser tests (including
# loginAs / assertAuthenticated) pass unchanged in a REAL Laravel application
# with only the base class swapped to Dawn\TestCase.
#
# Usage: scripts/integration-test.sh [laravel-constraint]   e.g. ^11.0
set -euo pipefail

LARAVEL_CONSTRAINT="${1:-^12.0}"
DAWN_PATH="$(cd "$(dirname "$0")/.." && pwd)"
WORKDIR="${INTEGRATION_DIR:-$(mktemp -d)}/app"
APP_PORT="${APP_PORT:-8787}"

# Older Laravel 10.x / 11.x releases now carry security advisories, and recent
# Composer refuses to load advisory-affected versions during resolution. This
# throwaway integration app only needs to boot and serve HTTP, so turn off
# advisory blocking. Use the config CLI (writes a schema-valid entry on the
# Composer versions that enforce the policy); guarded with `|| true` because
# older Composer does not know the key - and does not block, so it is moot.
composer config --global --no-plugins policy.advisories.block false >/dev/null 2>&1 || true

echo "==> Creating laravel/laravel ($LARAVEL_CONSTRAINT) in $WORKDIR"
composer create-project laravel/laravel "$WORKDIR" "$LARAVEL_CONSTRAINT" --no-interaction --prefer-dist --no-audit

cd "$WORKDIR"

echo "==> Requiring gawrys/dawn from path repository"
composer config repositories.dawn "{\"type\": \"path\", \"url\": \"$DAWN_PATH\", \"options\": {\"symlink\": true}}"
composer require --dev "gawrys/dawn:@dev" --no-interaction --quiet

echo "==> Installing Playwright engine dependencies"
vendor/bin/playwright-install --browsers >/dev/null

echo "==> Configuring the app (sqlite, base URL)"
php -r "
    \$env = file_get_contents('.env');
    \$env = preg_replace('/^APP_URL=.*/m', 'APP_URL=http://127.0.0.1:$APP_PORT', \$env);
    \$env = preg_replace('/^DB_CONNECTION=.*/m', 'DB_CONNECTION=sqlite', \$env);
    \$env = preg_replace('/^SESSION_DRIVER=.*/m', 'SESSION_DRIVER=file', \$env);
    \$env = preg_replace('/^(DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|DB_PASSWORD)=/m', '#\$1=', \$env);
    file_put_contents('.env', \$env);
"
touch database/database.sqlite
php artisan migrate --force --quiet

echo "==> Writing DuskTestCase (base class swap - the only migration step)"
mkdir -p tests/Browser
if [ -f tests/CreatesApplication.php ]; then
    CREATES_APPLICATION="    use CreatesApplication;"
else
    CREATES_APPLICATION=""
fi
cat > tests/DuskTestCase.php <<PHP
<?php

namespace Tests;

use Dawn\TestCase as BaseTestCase;

abstract class DuskTestCase extends BaseTestCase
{
$CREATES_APPLICATION
}
PHP

echo "==> Writing Dusk-style browser tests (bodies as in any Dusk suite)"
cat > tests/Browser/ExampleTest.php <<'PHP'
<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ExampleTest extends DuskTestCase
{
    public function test_basic_example(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertTitleContains('Laravel');
        });
    }
}
PHP

cat > tests/Browser/AuthenticationTest.php <<'PHP'
<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AuthenticationTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_login_as_and_logout(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->assertAuthenticated()
                ->assertAuthenticatedAs($user)
                ->visitRoute('dawn.user')
                ->assertPathIs('/_dawn/user')
                ->logout()
                ->assertGuest();
        });
    }
}
PHP

cat > tests/Browser/CookieTest.php <<'PHP'
<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CookieTest extends DuskTestCase
{
    public function test_encrypted_cookies_round_trip(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->cookie('encrypted', 'secret-value')
                ->assertHasCookie('encrypted')
                ->assertCookieValue('encrypted', 'secret-value');

            $this->assertSame('secret-value', $browser->cookie('encrypted'));

            $browser->plainCookie('plain', 'plain-value')
                ->assertPlainCookieValue('plain', 'plain-value')
                ->deleteCookie('plain')
                ->assertPlainCookieMissing('plain');
        });
    }
}
PHP

echo "==> Serving the application"
php artisan serve --host=127.0.0.1 --port="$APP_PORT" >/dev/null 2>&1 &
SERVER_PID=$!
trap 'kill $SERVER_PID 2>/dev/null || true' EXIT

for i in $(seq 1 100); do
    if php -r "exit(@fsockopen('127.0.0.1', $APP_PORT) ? 0 : 1);"; then break; fi
    sleep 0.1
done

echo "==> Running the browser suite"
DB_CONNECTION=sqlite DB_DATABASE="$WORKDIR/database/database.sqlite" SESSION_DRIVER=file \
    vendor/bin/phpunit tests/Browser --colors=always

echo "==> Integration test PASSED for laravel/laravel $LARAVEL_CONSTRAINT"
