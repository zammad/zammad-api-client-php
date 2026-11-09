#!/bin/sh
# Installs the client from this checkout into a fresh framework skeleton with a
# plain `composer require`, the way an application would, and checks that it
# works without editing a project file.
#
# The bridge suites under test/Bridge/ install the frameworks into a project of
# their own, so they cannot see conflicts with what a real skeleton locks —
# Guzzle 8 in Laravel 13 went unnoticed that way (#181).
#
# Usage: test/Install/run.sh laravel|symfony [skeleton constraint]
set -eu

framework=${1:?usage: test/Install/run.sh laravel|symfony [skeleton constraint]}
constraint=${2:-*}
root=$(cd "$(dirname "$0")/../.." && pwd)
app=$(mktemp -d)/app
export COMPOSER_NO_INTERACTION=1

ok()   { printf '  \342\234\224 %s\n' "$*"; }
fail() { printf '  \342\234\230 %s\n' "$*" >&2; exit 1; }

case $framework in
    laravel) skeleton=laravel/laravel ;;
    symfony) skeleton=symfony/skeleton ;;
    *) echo "unknown framework: $framework" >&2; exit 2 ;;
esac

echo "→ $skeleton:$constraint"
composer create-project --quiet "$skeleton:$constraint" "$app"
cd "$app"

# Offers this checkout under a release version, so that the constraint below is
# the one the README tells users to type, not a dev-branch alias.
composer config repositories.zammad \
    "{\"type\":\"path\",\"url\":\"$root\",\"options\":{\"symlink\":false,\"versions\":{\"zammad/zammad-api-client-php\":\"3.99.0\"}}}"

# Prints the class of the client the framework's container hands out.
resolve_laravel() {
    ZAMMAD_TOKEN=install-test php -r '
        require "vendor/autoload.php";
        $app = require "bootstrap/app.php";
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        echo get_class($app->make(ZammadAPIClient\ZammadClient::class));
    '
}

resolve_symfony() {
    ZAMMAD_TOKEN=install-test php -r '
        require "vendor/autoload.php";
        (new Symfony\Component\Dotenv\Dotenv())->bootEnv(".env");
        $kernel = new App\Kernel("dev", true);
        $kernel->boot();
        echo get_class($kernel->getContainer()->get("zammad_client"));
    '
}

case $framework in
laravel)
    before=$(cksum bootstrap/providers.php config/app.php)
    composer require --quiet 'zammad/zammad-api-client-php:^3.2' \
        || fail 'composer require failed'
    [ "$(cksum bootstrap/providers.php config/app.php)" = "$before" ] \
        || fail 'bootstrap/providers.php or config/app.php was changed'
    ok 'bootstrap/providers.php and config/app.php untouched'
    grep -q 'ZammadAPIClient\\\\Bridge\\\\LaravelServiceProvider' bootstrap/cache/packages.php \
        || fail 'provider not found by package discovery'
    ok 'provider found by package discovery'
    ;;
symfony)
    # Serves the Flex recipe the way the release assets do (#182), built by the
    # same script from the same source.
    endpoint=$(mktemp -d)
    php "$root/bin/build-flex-endpoint.php" "$endpoint" http://127.0.0.1:8099 >/dev/null
    php -S 127.0.0.1:8099 -t "$endpoint" >/dev/null 2>&1 &
    trap "kill $!" EXIT
    until php -r 'exit(@fsockopen("127.0.0.1", 8099) ? 0 : 1);'; do sleep 0.2; done
    composer config secure-http false   # the local endpoint is plain http
    composer config --json extra.symfony.endpoint '["http://127.0.0.1:8099/index.json", "flex://defaults"]'

    before=$(cksum config/bundles.php .env)
    composer require --quiet 'zammad/zammad-api-client-php:^3.2' \
        || fail 'composer require failed'
    grep -q 'ZammadAPIClient\\Bridge\\SymfonyBundle::class' config/bundles.php \
        || fail 'bundle not registered by the recipe'
    ok 'bundle registered by the recipe'
    [ -f config/packages/zammad.yaml ] \
        || fail 'config/packages/zammad.yaml not created by the recipe'
    ok 'config/packages/zammad.yaml created by the recipe'
    grep -q '^ZAMMAD_URL=' .env && grep -q '^ZAMMAD_TOKEN=' .env \
        || fail 'ZAMMAD_URL / ZAMMAD_TOKEN not added to .env'
    ok 'ZAMMAD_URL / ZAMMAD_TOKEN added to .env'
    ;;
esac

printf '  guzzlehttp/guzzle %s\n' "$(composer show guzzlehttp/guzzle | sed -n 's/^versions : \* //p')"
[ "$(resolve_$framework)" = 'ZammadAPIClient\ZammadClient' ] \
    || fail 'ZammadClient does not resolve from the container'
ok 'ZammadClient resolves from the container'

if [ "$framework" = symfony ]; then
    composer remove --quiet zammad/zammad-api-client-php \
        || fail 'composer remove failed'
    [ "$(cksum config/bundles.php .env)" = "$before" ] && [ ! -e config/packages/zammad.yaml ] \
        || fail 'composer remove left recipe changes behind'
    ok 'composer remove reverts the recipe'
fi
