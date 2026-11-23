# WHO Child Growth Standards — LMS reference tables

Used by `Modules\MCH\Classes\Services\GrowthZScoreService` to compute WHO
anthropometric Z-scores for children 0–5 years.

| File | Indicator | Index | Range |
|------|-----------|-------|-------|
| `weianthro.txt` | Weight-for-age (WAZ) | age in days | 0–1856 |
| `lenanthro.txt` | Length/height-for-age (HAZ) | age in days (`loh` L < 731 d, H after) | 0–1856 |
| `hcanthro.txt` | Head circumference-for-age (HCZ) | age in days | 0–1856 |
| `acanthro.txt` | MUAC-for-age (ACZ) | age in days | 91–1856 |
| `wflanthro.txt` | Weight-for-length (WHZ, < 2 y) | length cm, 0.1 steps | 45–110 |
| `wfhanthro.txt` | Weight-for-height (WHZ, ≥ 2 y) | height cm, 0.1 steps | 65–120 |

Columns: `sex` (1 = male, 2 = female), index, `l`, `m`, `s` (Box-Cox LMS).

**Source:** WHO Child Growth Standards (2006), as distributed in the World
Health Organization's `anthro` R package repository
(`https://github.com/WorldHealthOrganization/anthro`, `data-raw/growthstandards/`,
downloaded 2026-11-23; line endings normalised to LF, values unchanged). The
same LMS values are published by WHO in the expanded tables at
`https://www.who.int/tools/child-growth-standards/standards`. The `anthro`
repository is licensed GPL-3.0; confirm redistribution terms for your
deployment before shipping these files.
