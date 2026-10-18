# Discovery questions

Status: initial answers recorded in `project-context.md`; the questions below preserve the original intake, followed by focused follow-up questions. Do not include credentials or real customer codes.

## Confirmed from initial intake

- Priorities: strengthen WordPress onboarding, generate product codes in WordPress, and add product-specific downloadable file access alongside tutorial access.
- Tutorial levels remain cumulative.
- Codes ship with products. Access codes are a custom post type; ACF and Groups are in use.
- Small expected customer base; refunds/transfers are low priority.
- Site is live, not yet marketed, and has no users. Staging availability is unknown.

## Focused follow-up questions

Update after the second owner response: example products, one template per product, fixed levels, single-use non-expiring codes, possession-based registration, standard WordPress accounts, My Products destination, and no direct download protection are now recorded in `project-context.md`. The questions below are retained as intake history; answered portions should not be asked again.

## Remaining decisions after theme inspection

- Are templates limited to registered products while tutorial access remains tier-wide? Are any tutorials specific to a registered product rather than a tier?
- Can one account register multiple products? What should another valid code for the same product do?
- Where do products currently live: a custom post type, WooCommerce, or neither? What are the CPT names/slugs and ACF field definitions?
- Should admins select product and quantity to generate unique codes? What columns/file format does the label import need, and are QR codes needed?
- Does My Products already exist? Desired contents: product name/image, template link, tutorial links, and redeem-another-code form?
- Keep the current combined account/code form with a chosen password and immediate sign-in, or use emailed password setup? Any email verification requirement?
- Should new/replaced templates become available automatically to existing owners?
- What are the site/registration URLs, active plugin names/versions, and current tutorial restriction setup? Is staging available?

## Earlier focused intake

1. Provide two or three example products, each with its tutorial level and included files. Are products already represented in WordPress or an online store?
2. Should product files be available only for products a user registers, regardless of tutorial level? Can products share files?
3. Should adding or replacing a product's files automatically update downloads available to existing owners? Should changing its tutorial level update existing owners too?
4. Should each code be unique to one physical unit and redeemable by one account, without expiry? Can accounts register multiple products or multiple units of the same product?
5. Should admins generate batches by selecting a product and quantity? What export/printing format and code format are needed?
6. Is possession of a valid code enough, or must registration also collect/check purchase details or a serial number?
7. What does aligning onboarding to WordPress mean to the owner: native accounts, login/password recovery, emails, admin screens, or another requirement? Keep the combined code/account form or change the sequence?
8. Should users choose a password or receive a setup email? Is email verification required before granting access?
9. Where should users land after redemption, and should they have a page listing registered products, tutorials, and downloads?
10. Must direct file URLs enforce authorization, or is hiding download links sufficient? Where are files stored and how are video restrictions implemented today?
11. What are the exact plugin names/versions, WordPress/PHP versions, theme/page builder, site URL, and existing registration page? Can sanitized CPT/ACF/Groups configurations be supplied?
12. Is staging available, how is deployment handled, and are there test codes/content/configurations to preserve? Should the rebrand include internal identifiers or only visible branding?

## First round: goals and access rules

1. What are the first two or three refinements you want, and what currently feels broken or confusing? A short example of the desired customer experience helps.
2. Are levels cumulative (red includes orange and yellow; orange includes yellow), or does each level unlock only its own tutorials?
3. Which products unlock each level? Can a product unlock multiple levels, and can customers gain access through multiple purchases?
4. How do customers receive codes today: inside packaging, by email, at checkout, or another way? Who creates/imports the codes, and using what tool?
5. Is access intended to last forever? What should happen for refunds, returns, replacement products, lost codes, or code transfers?
6. Is this plugin live today? Is there a staging site, and what website address and registration page should we use as context?

## Second round: implementation and customer experience

7. What WordPress and PHP versions, theme/page builder, ACF edition/version, and Groups version are installed? Which tool defines the access-code post type and ACF fields? A sanitized field export would help later.
8. What controls tutorial restrictions today? Are Groups IDs 2/3/4 accurate, and where do users browse tutorials after registration?
9. Where are purchases made (for example, an online store or retail partners)? Should registration verify a purchase automatically, or is possession of a code sufficient?
10. Should new users choose a password, receive a password setup email, or use another sign-in flow? Is email verification required?
11. What should new and returning customers see after redemption? Do you have PyroPaths branding, wording, styling, accessibility, or mobile requirements?
12. What admin tools are needed: code generation/import/export, searching registrations, correcting access, reporting, or resending instructions?
13. Are there existing users, redeemed codes, integrations, or custom styles that must survive the rebrand? Is the intended scope visible branding only, or also internal names and the plugin folder?
14. How do you currently build, deploy, back up, and roll back the plugin? What checks must pass before a release?

## Acceptance criteria to establish per refinement

- Customer or administrator scenario and expected result.
- Relevant access rules and error/recovery behavior.
- Existing-data and compatibility requirements.
- How the result will be checked on staging, and who approves release.

## Latest answers and remaining implementation decisions

Tutorial versus template scope, duplicate-product registrations, WooCommerce use, account flow, and intended My Account/Learn/Library navigation are now confirmed in project-context.md. Earlier questions are historical where superseded.

Remaining decisions:
- Which WooCommerce order/payment status issues codes, where codes appear (email/account/order screen), and how retries avoid duplicate issuance.
- How online-order codes relate to printed labels shipped with the same physical product; gifting and checkout account requirements.
- Product quantity handling: ordinarily one code per unit, pending confirmation.
- Exact label import/export format and needed columns.
- Existing CPT/ACF/Groups configuration, including tier inheritance and product-group mapping.
- Actual page content/shortcodes and whether My Account needs a registration link or endpoint.
- Staging availability and deployment checks.

## Configuration follow-up

ACF export and owner-provided CPT/Groups settings captured under site-config. No further full configuration export is needed at this stage. Remaining questions: group parent hierarchy and actual restriction markup; paid-order trigger, checkout accounts, gifting/label handling; product mappings; label format; staging.

## Current outstanding discovery (supersedes earlier online-order questions)

Initial release: printed codes for both website and retail orders, existing account/code flow, page-level Groups restrictions, product templates, and administrator code generation/export. WooCommerce automatic access is a stretch goal; gifts are out of scope.

Remaining:
1. Should administrators configure each WooCommerce product with its tier and resource/template, using a product-specific Groups group for template access? This is a proposed model, not a confirmed implementation decision.
2. Will Learn link to separate tier-restricted video pages and Library link to product-group-restricted resource posts? Confirm listing visibility expectations: hide inaccessible items or show them as locked.
3. Is one product-specific group per product acceptable, with template restrictions managed through the existing Groups Read controls?
4. Generation/export proposal: choose product, quantity, optional batch; export CSV with code, product, batch. Confirm P-touch model/software and dimensions before defining print-specific output.
5. Confirm staging availability and deployment process before implementing changes on the live site.
6. Confirm desired visible PyroPaths branding changes while preserving legacy integration identifiers unless migration is planned.

## Latest answers

WooCommerce product-edit mapping is provisionally preferred. Library shows owned templates only. Learn is a landing page linking to tier pages and containing public/free videos. Codes are generated in batches with manual replenishment during fulfillment. Label software is Brother P-touch Editor. Testing will occur on pyropaths.com and test accounts will be purged before launch.

Remaining details can be resolved during design: product-group creation policy, batch/CSV layout and code format, product tier assignments/template examples, and test-data cleanup behavior. No further staging question is needed.

## Initial discovery closed

Owner confirmed manual administrator creation of Groups groups, My Account after registration, and individual or batch code generation. Do not re-ask these questions. Remaining details concern implementation: actual product mappings, label export format, placement of the additional-code form, and test-data cleanup. Product mapping on WooCommerce edit screens remains provisional.
