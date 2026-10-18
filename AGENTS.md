# Project guidance

Read `docs/project-context.md` and `docs/discovery.md` before changing behavior.

- The current product name is **PyroPaths**; HotLines is the legacy brand.
- Keep confirmed requirements, observations from code, and unresolved decisions distinct.
- Existing `hotlines_*` functions, constants, shortcode, CSS hooks, plugin path, ACF keys, and group IDs may be referenced by the live website. Identify compatibility and migration needs before renaming them.
- Tutorial levels are cumulative: red includes orange and yellow; orange includes yellow. Do not infer that this also grants other products' files.
- Do not store passwords, customer data, live access codes, or credentials in project documentation or fixtures.
- For behavior changes, document validation performed and any checks that require a real WordPress environment. Do not claim integration checks passed without running them.
- `build.sh` packages only runtime PHP and docs/SETUP.md into build/hotlines-onboarding.zip using temporary staging. Site configuration exports, tests, and context documents must not enter the release ZIP.
- Local integration checks live under tests/ and use an isolated Docker WordPress site; never run those fixtures on pyropaths.com. See tests/README.md.
