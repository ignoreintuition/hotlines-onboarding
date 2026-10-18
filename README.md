# PyroPaths Onboarding

WordPress plugin for registering purchased products and granting tier-based tutorial access and product-specific templates. Previously branded HotLines; legacy plugin paths, shortcode, functions, and CSS hooks are preserved.

Version 0.7.0 adds WooCommerce product mappings, single/batch code generation, CSV export, atomic single-use redemption, My Account registration, and an owned-template Library shortcode. Website purchases still use printed codes; automatic access on payment is deferred.

| Level | Experience | Tutorial access |
| --- | --- | --- |
| Yellow | Beginner | Yellow |
| Orange | Intermediate | Yellow, Orange |
| Red | Expert | Yellow, Orange, Red |

Template access is independent of tutorial tier and belongs to the registered product.

## Review and setup

Read [SETUP.md](docs/SETUP.md) for installation, product/group configuration, page shortcodes, code generation/export, and site acceptance checks. See [validation](docs/validation.md) for local check results and limitations.

For everyday administration, use the [administrator guide](docs/ADMIN-GUIDE.md): creating products and template Resources, configuring groups, generating and printing access codes, and testing customer access.

Dependencies: WordPress, WooCommerce, ACF with the existing access-code fields, Groups, and the access-codes/resource custom post types. Administrators create groups and maintain page/resource Read restrictions. Product fields use the classic product editor.

- Registration: `[hotlines_register]` or `[pyropaths_register]`.
- Library: `[pyropaths_library]`, replacing unfiltered template listings.
- My Account includes a signed-in code redemption form automatically.
- Build: `bash build.sh` creates `build/hotlines-onboarding.zip` containing runtime files and the setup guide only.
- Local integration environment and checks: [tests/README.md](tests/README.md).

## Project context

[Requirements](docs/requirements.md), [project context](docs/project-context.md), [discovery history](docs/discovery.md), and [supplied site configuration](docs/site-config/configuration-notes.md) record the owner decisions and baseline. Historical statements are superseded by later confirmed decisions and the current setup guide.
