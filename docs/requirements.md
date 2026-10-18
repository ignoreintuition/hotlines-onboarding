# PyroPaths initial-release requirements

Status: initial requirements baseline established with the owner. Implemented locally in 0.7.0 for review; site configuration and site-side acceptance checks remain. See SETUP.md and validation.md.

## Customer access

- Use standard WordPress accounts and the existing combined account/code registration flow with immediate sign-in.
- Signed-in customers can redeem additional codes.
- A unique code represents a product and is redeemable once, without time-based expiration. Possession is sufficient; receipts and purchase verification are not required.
- Registering a product grants its tutorial tier and access to its template through Groups.
- Tutorials are tier-based: yellow includes yellow, orange includes yellow/orange, red includes all tiers.
- Templates are product-based and independent of tier. Registering one product must not grant another product's template.
- Customers can register multiple products. Another unit of the same product may be registered but grants no additional content.
- Product levels are assumed fixed. Initial examples: Frog, Mushroom, Radial Symmetry, Pets; actual levels are unassigned.

## Site experience

- My Account remains the WooCommerce account area for orders/downloads.
- Learn is a landing page with free videos for everyone and links to tier-restricted pages.
- Use existing Groups Read page restrictions for tier content: yellow permits all tiers, orange permits Orange/Red, red permits Red.
- Library lists only templates belonging to the customer's registered products and links to resource CPT content.
- Each product has one template. Direct file URL protection is not required.
- Successful new-account registration redirects to WooCommerce My Account. Placement of register-another-product remains a design detail.

## Administration and fulfillment

- Use existing ACF, CPT UI, Groups, and WooCommerce configuration where appropriate.
- Provisionally configure product mappings on WooCommerce product edit screens: tutorial tier, template/resource, and product access group. This location is not yet a final UI decision.
- Administrators continue to create Groups groups manually; the plugin selects existing groups rather than automatically creating them. Keep the plugin simple.
- Generate product codes individually or in batches, on demand during fulfillment.
- Export codes for Brother P-touch Editor labels attached to larger product labels. Exact export columns, format, code appearance, and label layout remain open.
- Website and retail purchases use the same printed-code fulfillment and redemption flow for the initial release.
- Template updates are managed through existing content/Groups administration.

## Deferred work

- Automatic access following verified WooCommerce payment is a stretch goal. Resolve printed-code duplication before implementing it.
- Gifts, dedicated refund/transfer workflows, and direct download protection are outside initial scope.

## Validation scenarios

1. A new customer with a valid code gets a WordPress account, expected tier access, and the product template.
2. Invalid or used codes do not grant access; a used code cannot be consumed again, including by another account or simultaneous submission.
3. A signed-in customer registers a different product and retains existing access while gaining its template and applicable tier access.
4. A second valid code for an already-owned product is consumed successfully without duplicate benefits.
5. A red customer can reach every tier; an orange customer can reach yellow/orange; a yellow customer cannot reach orange/red.
6. Owning a high-tier product does not expose other products' templates in Library.
7. Free Learn videos remain available without a purchase or account.
8. A generated batch has unique codes associated with the selected product and can be exported for the agreed label workflow.
9. Registration/redemption failure does not leave an incorrectly consumed code or falsely reported access grant.

## Test environment and launch

- Test on pyropaths.com as directed by the owner.
- Owner intends to purge test accounts before launch. Account deletion is not currently requested or performed.
- Before cleanup, decide how test-only code redemptions, Groups memberships, WooCommerce orders, and generated batches are handled. Deleting accounts alone must not be assumed to reset codes.
- Inspect supplied configuration and actual page settings when validating integrations; documentation inspection is not an integration test.
