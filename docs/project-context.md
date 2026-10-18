# PyroPaths project context

Last updated: 2026-10-16.

## Confirmed by the owner

- The website and plugin were originally named HotLines and have been rebranded as PyroPaths.
- The plugin's purpose is to let purchasers register their product and gain access to a particular level of tutorials on the website.
- Levels are yellow (beginner), orange (intermediate), and red (expert).
- Tutorial access is cumulative: orange includes yellow; red includes orange and yellow. This must remain true.
- Refine onboarding around WordPress-generated access codes shipped with products.
- A product code must grant both the appropriate tutorial categories and access to that product's specific downloadable files, such as templates.
- The site uses Advanced Custom Fields, a custom post type plugin (exact name unconfirmed), and Groups. Access codes are a custom post type; Groups grants access.
- The site is live but has not yet been marketed and has no users, according to the owner.
- The expected customer base is small. Refund and transfer workflows are not a current priority.
- Initial product examples: Frog, Mushroom, Radial Symmetry, and Pets. Current products are placeholders; tutorial levels are not assigned yet.
- Each product has one associated downloadable template and respective tutorials. Assume assigned tutorial levels will remain fixed.
- Each code is unique, can be activated/redeemed only once, and does not otherwise expire. Possession of a valid code is sufficient; purchase verification is not required.
- Administration uses Groups and the custom post type controls. Codes will be exported for import into the label workflow; exact export format remains to be established.
- Customers must create standard WordPress accounts; Groups assigns access.
- A My Products page is an acceptable destination after onboarding.
- Direct download protection is not required. Downloads may use ordinary file URLs; access to displayed links still needs product association.
- The child theme is in the sibling `../hotlines-child` directory.
- The current task is discovery and contextual documentation in preparation for refinements. No behavior changes have been requested yet.

## Observed in the repository

These describe the source, not verified production configuration.

- A single PHP file implements WordPress registration and code redemption, with plugin version `0.6.0`.
- `[hotlines_register]` displays account registration to visitors and a code-only form to signed-in users.
- New accounts use email as username and receive the `subscriber` role. A valid, available code is checked before account creation.
- Successful new registration sets an authentication cookie and redirects to `/learn`. Existing users receive an inline success message after redemption.
- Codes are published posts of type `access-codes`, found by ACF field `access_code`. Submitted codes are trimmed and lowercased.
- ACF fields used: `access_code`, `status`, `groups_to_grant`, `redeemed_by`, `redeemed_at`.
- Recognized status strings are `Available` and `Redeemed`.
- Selected values `yellow`, `orange`, and `red` map to Groups IDs 2, 3, and 4 respectively. Verify those IDs on each environment.
- Redemption adds selected group memberships and records status, user ID, and redemption time. There is no explicit level inheritance in this plugin.
- No product catalog, purchase verification integration, code generation interface, expiry logic, or content restriction configuration is implemented in the supplied PHP file.
- ACF schema and post-type registration are not included. It remains unknown where they are managed.
- The sibling child theme declares Astra as its parent, version `1.1.0`, and legacy HotLines naming. After synchronization with the supplied latest ZIP, its `functions.php` enqueues styles with cache versioning and registers the PyroPaths editor palette; no account, product, tutorial, or download behavior is defined there. The `templates` directory is empty.
- Child-theme CSS includes WooCommerce selectors, but that alone does not establish whether WooCommerce is installed or used for products/accounts.

## Areas to evaluate when refinements are scoped

These are inspection leads, not approved changes or a completed security audit.

- Preserve compatibility during the rebrand: shortcode, functions, CSS hooks, plugin directory, and stored identifiers may have external consumers.
- Confirm redemption consistency under simultaneous requests and failures. The current code separately validates availability, grants groups, and updates code fields; it does not check group creation or field update results.
- Confirm recovery if an account is created but redemption fails.
- Verify registration redirects and authentication in the site's real page rendering/caching setup.
- Establish a staging environment and validation approach before behavior changes.

## Decisions pending

See `discovery.md`. Product modeling, tutorial-to-product relationships, generation/export details, account flow details, and environment configuration still need decisions. Cumulative tutorial access is confirmed; cumulative access to other products' files is not.

## Confirmed in the latest discovery round

- Tutorials belong exclusively to tiers, not products. Yellow grants yellow; orange grants orange and yellow; red grants all three.
- Templates belong exclusively to products, independently of tutorial tiers.
- Accounts may register multiple products and additional units of the same product; duplicate product registrations grant no additional content.
- WooCommerce will handle website purchases. Website purchases should automatically generate an access code and deliver it immediately; the exact payment/order trigger and fulfillment handling remain unresolved.
- Other sales channels use pregenerated codes on printed labels, generated by an administrator for distribution.
- The website is https://pyropaths.com/. A public fetch during discovery failed; live configuration has not been verified.
- Logged-in customers use WooCommerce My Account for orders/downloads. Learn pages expose entitled tier videos; Library exposes entitled product templates. Preserve this intended navigation rather than assuming a separate My Products page.
- Keep the current combined account registration/code form and immediate sign-in flow.
- Template updates will be managed through Groups/content administration; no separate template-update distribution workflow is requested.
- Correct the example product name to Radial Symmetry in project documentation. Internal legacy identifiers still require compatibility planning before renaming.
- Owner will supply configuration from the live site; ACF/CPT definitions and Groups IDs, hierarchy, and capability mappings are needed before implementation.

## Site configuration received

See `site-config/configuration-notes.md` and the unchanged ACF export beside it for the configuration snapshot and implementation gaps.

- CPT slugs confirmed: `resource` and `access-codes`.
- Group IDs 2/3/4 match the current PHP tier map. Mushroom Kit is group 5 and has capabilities `level_1` and `mushroom_kit`.
- ACF allows `mushroom_kit`, but current PHP does not map it. Product template access needs implementation.
- Tier parent relationships and actual page restriction settings remain unverified; supplied capabilities alone do not establish cumulative access.
- Latest preference: online WooCommerce orders should unlock access automatically. Earlier code-delivery requirements for online purchases are superseded where automatic access makes redemption unnecessary; whether an additional code is needed remains open.
- Learn embeds videos (Rabbit); Library links to resource posts. Both are ordinary WordPress pages.

## Initial-release scope clarified

These decisions supersede earlier assumptions about required WooCommerce automation.

- Page visibility is controlled through the Groups Read metabox. Owner screenshots show a red page restricted to Red Tier and another page permitting Orange Tier, Red Tier, and Yellow Tier. Tier inclusion is configured through page group selections; no group-parent hierarchy has been established by these screenshots.
- Preserve page-based restrictions. For cumulative tutorial access, yellow content must permit Yellow/Orange/Red, orange content Orange/Red, and red content Red. Verify actual page selections during staging validation.
- Automatic WooCommerce access is a stretch goal, not an initial-release requirement. If implemented later, access should be granted once payment is verified.
- Website and retail orders will ship alike with printed access codes. Initial release uses code redemption for both channels.
- Gifts are out of scope.
- No product mappings currently exist. Mapping products to their tier, template/resource, and access group is initial-release work.
- Labels will probably be printed with Brother P-touch and attached within a space on a larger label. Exact printer/software, dimensions, import format, and columns are not yet confirmed.
- If automatic website access is revisited, resolve whether the shipped code remains redeemable by another account before implementing it.

## Latest owner decisions

- Product mapping on the WooCommerce product edit screen is provisionally preferred (owner: “probably”); final UI design remains open.
- Library must show only templates for products the signed-in customer owns.
- Learn is a landing page linking to separate tier pages and also contains free videos available to everyone.
- Codes are generated in batches, with additional codes manually generated as needed during fulfillment.
- Label software is Brother P-touch Editor. Printer model, label dimensions, and import format remain unspecified.
- pyropaths.com will serve as the test environment. Owner plans to purge test accounts before go-live. No purge has been requested or performed; associated redeemed codes, orders, and memberships need an explicit cleanup plan before that operation.
- See `requirements.md` for the consolidated initial-release scope and acceptance scenarios.

## Final initial-scope decisions

- Administrators continue to create Groups groups manually. The plugin should use selected existing groups and avoid automatic group creation or unnecessary complexity.
- Successful new-account registration lands on WooCommerce My Account, superseding the earlier My Products destination and current source redirect to `/learn`.
- Code generation supports both individual codes on demand and batches.
- Initial requirements discovery is complete enough for design/implementation planning. Remaining implementation inputs include product tier/group/resource mappings and label export details.

## Source archive alignment — 2026-10-17

- Compared Downloads/hotlines-onboarding.zip and Downloads/hotlines-child.zip with the local repositories.
- Plugin PHP and build.sh already matched exactly. Preserved the expanded local README and project documentation rather than replacing them with the archive's empty README.
- Synchronized the sibling child theme to its supplied archive: functions.php, style.css, brand CSS, screenshot, logo, and README.txt. Existing build.sh and .gitignore already matched.
- Backed up overwritten local theme files before syncing at `/var/folders/qk/cc7xjv69025cdhyyxsymj5n00000gn/T/pyropaths-theme-before-sync-ba1_p6qi`; sync-manifest.json identifies added versus replaced files. This is a temporary backup, not a release artifact.
- Verified archive file bytes after synchronization. Existing build directories were not rebuilt; local website deployment and visual/integration checks were not performed.

## Local implementation — 0.7.0

Initial scope implemented locally for owner review: WooCommerce product metabox mappings, product-linked code redemption, individual/batch generation and CSV export, My Account registration, and `[pyropaths_library]`. Admins continue to create groups and maintain Groups Read restrictions. Legacy shortcode, directory, function names, CSS classes, and ACF schema are retained. The child theme did not need changes.

New code storage: `_pyropaths_product` on access codes; `_pyropaths_tier`, `_pyropaths_group`, `_pyropaths_resource` on WooCommerce products. Codes without product metadata retain legacy explicit grants, including mushroom_kit group 5. See SETUP.md for configuration and validation.md for check results. Automatic WooCommerce order access remains deferred. Nothing has been deployed to pyropaths.com.
