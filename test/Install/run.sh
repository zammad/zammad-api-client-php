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
    composer require --quiet 'zammad/zammad-api-client-php:^3.2' \
        || fail 'composer require failed'
    # Without a Flex recipe the bundle needs the two manual steps from the
    # README. Once the recipe exists (#182) these branches are no longer taken.
    if grep -q 'ZammadAPIClient\\Bridge\\SymfonyBundle::class' config/bundles.php; then
        ok 'bundle registered by Flex'
    else
        php -r '
            $file = "config/bundles.php";
            $bundle = "    ZammadAPIClient\\Bridge\\SymfonyBundle::class => [\"all\" => true],\n];";
            file_put_contents($file, preg_replace("/^\];/m", $bundle, file_get_contents($file)));
        '
        ok 'bundle registered by hand (no Flex recipe yet, #182)'
    fi
    if [ -f config/packages/zammad.yaml ]; then
        ok 'config/packages/zammad.yaml created by Flex'
    else
        echo 'zammad: ~' > config/packages/zammad.yaml
        ok 'config/packages/zammad.yaml created by hand (no Flex recipe yet, #182)'
    fi
    # `composer require` has just warmed the container cache, and the edits
    # above land within the same second. Symfony compares modification times
    # at one-second resolution and keeps the stale container, which even
    # `cache:clear` reuses for its warm-up. A user editing by hand never hits
    # this, so the cache is simply dropped.
    rm -rf var/cache
    ;;
esac

printf '  guzzlehttp/guzzle %s\n' "$(composer show guzzlehttp/guzzle | sed -n 's/^versions : \* //p')"
[ "$(resolve_$framework)" = 'ZammadAPIClient\ZammadClient' ] \
    || fail 'ZammadClient does not resolve from the container'
ok 'ZammadClient resolves from the container'
