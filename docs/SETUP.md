# PyroPaths Onboarding 0.7.0 — review and setup

This release adds product-based code registration and template discovery. It does not automatically unlock access for WooCommerce orders or change the child theme.

## Install

1. Back up the current plugin and WordPress database before testing. Upload `build/hotlines-onboarding.zip` through Plugins → Add New → Upload and replace the existing plugin. The directory and entry-point filename remain unchanged.
2. Keep WooCommerce, ACF, Groups, and the CPT definitions active. The existing ACF Access Code field group is still required; its original field keys are used when creating codes. No ACF import or replacement is needed on the supplied site.
3. Use the classic WordPress/WooCommerce product editor. The new **PyroPaths Access** metabox appears on product edit screens; compatibility with WooCommerce's experimental/new product editor has not been implemented.
4. Verify the WooCommerce My Account page is configured. Registration redirects to that configured page, rather than a hardcoded slug.

## Configure a product

1. Create a separate product-specific group in Groups, such as Mushroom Kit. Do not use Registered or a tier group for product access, and do not reuse a product group across different products.
2. Create and publish a Resource containing its template download link. Set the Resource's **Groups → Read** restriction to the product group. File URLs themselves remain ordinary, shareable URLs as requested.
3. Edit the WooCommerce product. Under **PyroPaths Access**, select its tutorial tier, product group, and published Resource. Save/update the product. Publish products whose resources should appear in Library.
4. Existing tier group IDs remain Yellow 2, Orange 3, Red 4, matching the supplied configuration. Mushroom Kit's legacy code value maps to group 5. If groups are recreated with different IDs, update `hotlines_group_id_map()` before using codes.
5. Product mappings are read at redemption time. Keep tier/group assignments fixed after issuing codes. Replacing a mapped Resource makes the new Resource appear for existing group members; update its Groups restriction too.

The plugin does not create groups, change their capabilities, or set Resource restrictions automatically. Admins retain those controls.

## Set up pages

- Existing registration pages retain `[hotlines_register]`. `[pyropaths_register]` is also supported.
- My Account's dashboard now includes a Register a product form for signed-in users.
- Replace the Library page's unfiltered resource links/query with `[pyropaths_library]`. The shortcode lists only published resources mapped to published products whose product group the current user directly belongs to. Manual group grants/revocations affect the list. Logged-out visitors see a login link.
- Leave Learn as the public landing page with free videos and links to restricted tier pages.
- Groups Read settings: yellow content permits Yellow, Orange, Red; orange permits Orange, Red; red permits Red only. The plugin grants the selected tier group, relying on these inclusive page settings.
- Exclude registration, Library, and My Account from full-page/CDN caching; personalized content and registration forms must reach WordPress.

## Generate and print codes

1. Open **Access Codes → Generate / Export** (administrator permission required).
2. Select the configured product, a quantity from 1 to 500, and an optional batch label. Quantity 1 supports on-demand fulfillment.
3. Generate the batch. Each saved code has a unique batch identifier and a product association. Codes use 16 random hexadecimal characters, grouped into four blocks, e.g. the format `xxxx-xxxx-xxxx-xxxx`. The code remains case-insensitive.
4. Export the saved batch as UTF-8 CSV. Columns: `code`, `product_id`, `product`, `tier`, `batch`, `status`. The export includes all published codes in that batch, including redeemed ones with their status; print only Available rows. Exporting never generates or redeems codes.
5. Use Brother P-touch Editor's database/text import to map the code and product columns to your label layout. Verify one label first; exact printer/layout and compatibility with your Editor version need a site-side check.
6. Batch identifiers remain listed for repeat downloads. Product and batch columns also appear in the Access Codes list.

Codes generated for a product use its mapping, overriding the legacy Groups to Grant checkbox. Existing codes without a product keep their legacy checkbox grants. An available legacy code can be associated with a product through its PyroPaths Product metabox; redeemed codes cannot be reassigned there.

## Redemption behavior

- New customers provide name, email, password, and code. A valid code is checked before account creation; configured access must be valid too.
- Successful redemption grants the product and selected tier groups, records Redeemed status, account ID, and an ACF-compatible date, signs in new users, and redirects them to My Account.
- Signed-in users can register additional products. Another code for the same product is consumed normally without duplicating memberships.
- Existing membership in other tiers/products is retained.
- A database lock serializes redemption and rechecks availability. Database writes use a transaction so a failed save rolls back membership/code changes. Participating posts, postmeta, usermeta, and Groups membership tables must use InnoDB. If not, generation/redemption fail rather than silently proceeding; no table conversions are performed.
- Code records are disabled in public REST and front-end queries. Admin editing remains available through CPT UI.

## Review on pyropaths.com

Start with a dummy Mushroom product and template, then confirm:

- A new account can redeem an available code and reaches My Account.
- An existing account can redeem another product and retains its prior access.
- A used code fails for both the same and another account.
- A second valid code for the same product is accepted without additional content.
- Yellow/Orange/Red page visibility follows the configured tier rules.
- Library shows only owned product templates; free Learn videos remain public.
- Manual product-group removal hides its template from Library.
- CSV opens correctly in P-touch Editor and the printed code can be redeemed.
- Existing child-theme styling and WooCommerce login/password recovery still work.

Test-account cleanup is separate from code reset. Do not reset production codes by deleting customers. Before launch, identify disposable code batches, accounts, memberships, and orders and agree how to remove them. No automatic cleanup is included.

## Rollback

Reinstall the prior plugin ZIP to restore its code. New product/code metadata remains in the database. The old plugin cannot redeem product-linked codes correctly, so pause registration and restore the pre-test database backup if rolling back a test installation. Restore the Library page content if removing its shortcode.
