# Local integration checks

These fixtures create/delete dummy products and create accounts/codes. Run only on the disposable Docker site at http://localhost:8097, never on pyropaths.com. PHP fixtures check the URL before mutating data. They use the supplied ACF schema and fixture CPT definitions, not production credentials or codes.

Requires Docker Compose and Python 3. Port 8097 must be available. The compose project uses its own database container and WordPress volume.

```sh
docker compose -f tests/compose.yml up -d wordpress
docker compose -f tests/compose.yml run --rm cli wp core install --url=http://localhost:8097 --title='PyroPaths isolated tests' --admin_user=test-admin --admin_password=local-test-admin-only --admin_email=admin@example.test --skip-email
docker compose -f tests/compose.yml run --rm cli wp plugin install advanced-custom-fields groups woocommerce --activate
docker compose -f tests/compose.yml run --rm cli wp plugin activate hotlines-onboarding
docker compose -f tests/compose.yml run --rm cli wp eval-file wp-content/plugins/hotlines-onboarding/tests/integration.php
docker compose -f tests/compose.yml run --rm cli wp eval-file wp-content/plugins/hotlines-onboarding/tests/pages.php
python3 tests/concurrency.py
python3 tests/browser_flow.py
python3 tests/admin_flow.py
```

Run the integration fixture again before rerunning concurrency/browser checks: it issues fresh disposable codes. The core install and plugin install commands are first-time setup only. The CLI image may print a cache-directory warning; this does not prevent installation.

PHP syntax checks:

```sh
docker compose -f tests/compose.yml exec -T wordpress sh -c 'find wp-content/plugins/hotlines-onboarding -name "*.php" -not -path "*/build/*" -exec php -l {} \;'
```

Stop the site without deleting its volume:

```sh
docker compose -f tests/compose.yml stop
```

The test admin password is public fixture data for a localhost-only disposable site. Never reuse it on an accessible site. These tests validate behavior, not visual compatibility with the live child theme or P-touch printing.
