# Supplied site configuration

Captured 2026-10-16 from owner-provided exports and table. This is documentation, not an importable CPT/Groups configuration. The adjacent ACF JSON is an unchanged copy of the supplied export (filename date preserved).

## CPT UI configuration summary

Both post types: public, admin UI enabled, REST enabled, nonhierarchical, no archive, cannot export through standard WordPress content export, capability type `post`, rewrite enabled, no assigned taxonomies.

| Slug | Labels | Publicly queryable | Navigation menus | Excluded from search | Supports |
| --- | --- | --- | --- | --- | --- |
| resource | Resources / Resource | Yes | Yes | No | title, editor, thumbnail, excerpt, custom-fields |
| access-codes | Access Codes / Access Code | No | No | Yes | title, custom-fields |

Access codes have REST enabled despite not being publicly queryable; verify actual REST visibility and exposed content before using real codes. This observation alone does not establish that the ACF code field is publicly exposed.

## Groups supplied by owner

| ID | Group | Members at snapshot | Capabilities |
| --- | --- | --- | --- |
| 1 | Registered | 8 | None |
| 2 | Yellow Tier | 4 | level_1 |
| 3 | Orange Tier | 2 | level_2 |
| 4 | Red Tier | 1 | level_3 |
| 5 | Mushroom Kit | 0 | level_1, mushroom_kit |

Orange description: Intermediate level tier. Red: Advanced Tier Users. Yellow: Beginner level tier. Parent relationships were not supplied. Membership counts need not represent customers; the earlier owner statement was that there are no customers/users yet.

## ACF observations

- One field group, HotLines Access Code Fields, targeting `access-codes`.
- `groups_to_grant`: checkbox values yellow, orange, red, mushroom_kit; defaults to yellow.
- Current PHP maps only yellow/orange/red. A code selecting mushroom_kit currently encounters an unknown group value, so product-template redemption is not yet supported by this source.
- `redeemed_at` is a date picker, but PHP writes a MySQL date/time string. Align the field and storage format when implementing refinements.
- Notes field has the stored name `noes`. Preserve the original export; any correction needs a data compatibility decision.
- Additional fields batch and noes are not used by the current PHP implementation.
- No product relationship or resource field group is included in this export.

## Pages and commerce

Owner confirms Learn and Library are ordinary pages. Learn embeds videos (Rabbit); Library links to the resource CPT. Exact restriction markup/settings are not yet supplied.

Online purchases should unlock access automatically. Printed single-use codes support purchases through other channels. Whether online purchases also need a transferable code remains undecided.

## Page restriction screenshots and scope clarification

Owner supplied screenshots of the Groups Read metabox: one page selects Red Tier; another selects Orange Tier, Red Tier, and Yellow Tier. This establishes page-level group selection, not group hierarchy. Desired cumulative visibility can be represented by permitted groups on each page.

WooCommerce automatic access is now explicitly a stretch goal. Initial website and retail fulfillment both use printed codes. Gifts are out of scope. No product-to-tier/resource/group mappings exist yet. Label printing will probably use P-touch, with a small label attached to a larger product label.
