# Validation — PyroPaths Onboarding 0.7.0

Run locally on 2026-10-17 using an isolated Docker WordPress site. No tests or deployment were performed on pyropaths.com.

## Environment

- WordPress 7.1.3
- Web/PHP runtime 8.3.35
- ACF 6.8.10
- Groups 4.8.0
- WooCommerce 11.2.1
- MariaDB 11, InnoDB
- Supplied ACF export loaded as fixture field definitions; fixture CPT registration represents the resource and access-code types.

These are the test-container versions, not verified versions of the live site's plugins.

## Passed

- PHP syntax checks for every runtime and fixture PHP file; build shell syntax; Git whitespace checks.
- Product-linked grants, existing membership retention, multiple products, repeat product purchases, invalid mapping rejection, legacy Mushroom grants, and validation before partial grants.
- Single and batch generation, unique code formatting, ACF field reading/formatting, and ACF-compatible redemption dates.
- Used-code rejection for the same and another account.
- Two concurrent processes redeeming the same fresh code: exactly one success, one rejection.
- Database write failure injection: membership rollback, code preserved as Available, and subsequent successful retry.
- Generation failure injection: no partial batch/code records left in the database.
- First-registration failure injection: newly created account removed and code kept Available.
- Real HTTP account registration, authentication cookie, redirect to configured My Account, and additional-code form.
- Real HTTP Groups visibility for all nine combinations of Yellow/Orange/Red account and tier page.
- Library displays owned templates only, retains multiple products, reflects manual group revocation, and prompts anonymous users to log in.
- Free Learn page content remains public.
- Public access-code REST route is absent.
- Administrator form generates a code and exports the saved batch as correctly structured CSV.
- Invalid generation nonce rejected; subscriber generation/export requests rejected with HTTP 403.
- CSV formula-leading values escaped.

## Site-side checks still required

- Install and review on pyropaths.com using its actual versions, page content, caching, Groups settings, and child theme.
- Configure actual product tiers, existing product groups, and template resources.
- Replace Library's unfiltered links with the owned-template shortcode and verify Resource Read restrictions.
- Check native login/password recovery, registration styling, mobile presentation, and any existing form/custom-plugin integrations.
- Import CSV into Brother P-touch Editor and test actual label formatting/printing.
- Confirm the live database tables use InnoDB and permit advisory locks; the plugin fails closed for generation/redemption otherwise.
- Agree test-account/code/order cleanup before launch.

Reproduction commands and disposable-fixture notes are in `tests/README.md`. Automatic order-paid access and protected file URLs were deliberately outside this release.
