# PyroPaths administrator guide

For the product-based onboarding workflow in plugin version 0.7.0. Use an administrator account and the classic WooCommerce product editor. For installation and dependencies, see [SETUP.md](SETUP.md).

## How the pieces fit together

Each WooCommerce product selects a tutorial tier, a product-specific Groups group, and a Resource containing its template link. A customer redeems a single-use code associated with that product to receive both the tier group and product group.

| Item | Purpose | Example |
| --- | --- | --- |
| WooCommerce product | The item sold and its access mapping | Mushroom Kit |
| Product category | Store organization; does not grant access | Yellow |
| Tutorial tier | Which tier group redemption grants | Yellow |
| Product group | Access to this product's template | Mushroom Kit |
| Resource | Published page containing the template download link | Mushroom Template |
| Access code | One product registration, redeemable once | Generated in WordPress |

Tutorial tiers are cumulative: Orange includes Yellow; Red includes Orange and Yellow. They do not grant other products' templates. Website and retail orders both use printed codes; payment alone does not unlock access.

## 1. Create the product group

1. Open **Groups → Groups** and add a group named for the product, or edit its existing dedicated group.
2. Leave **Parent** unset (`--`) so the product group stays independent of tutorial tiers.
3. Optionally add a description such as “Access to this product's template.”
4. Save. If removing a previous parent, reopen the group to check the saved parent and inherited capabilities.

Use a different group for every product. Do not select Registered or a tier group as the product group. Keep any existing product capability, such as `mushroom_kit`; the onboarding plugin does not create or maintain capabilities. The workflow below uses group membership and Groups Read restrictions.

## 2. Create the template Resource

1. Upload the template through **Media → Add New** and copy its file URL, or use the existing file.
2. Open **Resources → Add New** and give it a clear title, such as **Mushroom Template**.
3. Add a download link or File block pointing to the template. Include any useful printing or usage instructions.
4. Under **Groups → Read**, select only this product's group, such as **Mushroom Kit**.
5. Publish the Resource. Only published Resources appear in the product's template selector.

Do not add Yellow, Orange, Red, or Registered to a product Resource's Read permissions: doing so would broaden access beyond that product's owners. File URLs remain shareable; the restriction protects the Resource page and displayed link, not direct requests for the file.

## 3. Configure the WooCommerce product

1. Open **Products → Add New**, or edit an existing product.
2. Configure its normal store details: name, description, image, price, inventory, and shipping as applicable.
3. Choose store categories as needed. A Yellow category does not automatically select Yellow tutorial access.
4. In **PyroPaths Access**, select all three fields:

   | Field | Mushroom example |
   | --- | --- |
   | Tutorial tier | Yellow |
   | Product group (not a tier group) | Mushroom Kit |
   | Template resource | Mushroom Template |

5. Publish or update the product, then reopen it to confirm the saved selections. Library listings require a published product and published Resource.

Keep tier and product-group assignments fixed after issuing codes. Codes read the current mapping when redeemed; changing it can give later customers different access and does not migrate existing memberships.

## 4. Check Library and tutorial pages

- Put `[pyropaths_library]` on the **Library** page in place of an unfiltered template listing. It shows mapped Resources only for product groups the signed-in customer directly belongs to.
- Keep `[hotlines_register]` or `[pyropaths_register]` on the registration page. Signed-in customers also get a registration form on My Account.
- Keep Learn as a public landing page with free videos and links to restricted tier pages.
- Set tier-page **Groups → Read** permissions as follows:

  | Content | Groups permitted to read |
  | --- | --- |
  | Yellow tutorials | Yellow Tier, Orange Tier, Red Tier |
  | Orange tutorials | Orange Tier, Red Tier |
  | Red tutorials | Red Tier |

Exclude registration, My Account, and Library from full-page/CDN caching so each customer receives their own forms and content.

## 5. Generate codes

Use the generator for both single codes and batches:

1. Open **Access Codes → Generate / Export**.
2. Select the configured, published WooCommerce product.
3. Set **Quantity** from **1 to 500**. Use 1 for a test or an individual fulfillment request.
4. Optionally enter a descriptive batch label, without customer information.
5. Click **Generate codes** and retain the resulting batch identifier. A unique suffix is added to the label.

The generator saves random codes associated with the chosen product. Codes are case-insensitive, single-use, and do not expire. Generating again creates additional codes; use export to retrieve an existing batch.

### If editing an access code manually

Select the WooCommerce product in the **PyroPaths Product** box, keep **Status: Available**, leave **Redeemed By** and **Redeemed At** blank, and publish the code. Product-linked codes use the product mapping instead of the legacy **Groups to Grant** checkboxes. Leave the product blank only when maintaining a legacy code.

Prefer generated codes over predictable, sequential codes for fulfillment. Redeemed codes cannot be reassigned through the product selector.

## 6. Export and print labels

1. On **Generate / Export**, select a saved batch or enter its identifier under **Export saved batch**.
2. Click **Download CSV**. Exporting retrieves saved codes; it does not create or redeem them.
3. The UTF-8 CSV contains `code`, `product_id`, `product`, `tier`, `batch`, and `status`.
4. Filter to **Available** rows before printing. Exports can include redeemed codes.
5. Import the CSV into Brother P-touch Editor and map the code and product columns into the label layout.
6. Print one label and check legibility and fit before printing the batch. Editor import steps and label dimensions still need verification with the actual printer/software.

Treat exported codes as fulfillment material: possession allows registration. Keep live codes and customer information out of project documentation and test fixtures.

## 7. Test a new product before fulfillment

Use a disposable code and a separate test account in a private/incognito window. Administrator access can conceal restriction problems. These are manual checks on pyropaths.com; never run the repository's automated fixtures against that site.

- [ ] New-account registration with the code succeeds and lands on WooCommerce My Account.
- [ ] Library shows the product's Resource and its download link works.
- [ ] The account can open the entitled tutorial tiers and cannot open higher tiers.
- [ ] Another product's template stays hidden and its Resource page remains restricted.
- [ ] Reusing the code fails, including from another account.
- [ ] In admin, the code is Redeemed and shows the account and redemption date.
- [ ] A signed-in account can register another product through My Account and retain its earlier access.
- [ ] Another valid code for the same product is consumed without granting extra content.
- [ ] Logged-out visitors can still view free Learn videos and cannot read the restricted template Resource.
- [ ] CSV import and the printed label work with the actual P-touch setup.

Record the date, scenario, and outcome without storing codes, passwords, or customer information. Do not describe a check as passed until it has actually been performed. Keep test batches separate from fulfillment batches; do not ship a code consumed during testing. Deleting a test account does not reset its codes. Agree on cleanup of disposable accounts, codes, memberships, and orders before removing test data.

## Routine updates and troubleshooting

| Situation | What to check |
| --- | --- |
| PyroPaths Access box missing | Updated plugin is active; use an administrator account and the classic product editor. |
| Resource missing from selector | Resource is published and uses the Resource post type. |
| Product missing from code selector | WooCommerce product is published. |
| Template missing from Library | Product and Resource are published; mapping is saved; customer directly belongs to the mapped product group; Library has the shortcode; caching is excluded. |
| Template listed but Resource denied | Resource's Groups Read setting permits the same product group. |
| Other owners can read a template | Resource Read settings include a tier, Registered, or another broad group; check public links and page content too. |
| Higher tier cannot open lower tutorials | Lower tutorial page permits all appropriate tier groups using the table above. |
| Code fails | Code is published and Available, product mapping is complete, and the code was entered exactly, including hyphens. Consult the displayed error before changing anything. |
| Old domain opens | Check WordPress Address/Site Address, menus, page buttons, and stored file links. The local child-theme runtime has no hardcoded site domain. |

To replace a template, update the existing Resource's download link. If selecting a different Resource on the product, publish it and apply the same product-group Read restriction. Existing group members then see the newly mapped Resource in Library. Review any old Resource's links and visibility separately.

This guide describes the implemented workflow and checks still required on the real site; it is not evidence that site integration or printing checks have passed.
