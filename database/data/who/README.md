# WHO Child Growth Standards — LMS reference tables

Used by `Modules\MCH\Classes\Services\GrowthZScoreService` to compute WHO
anthropometric Z-scores for children 0–5 years.

| File | Indicator | Index | Range |
|------|-----------|-------|-------|
| `weianthro.txt` | Weight-for-age (WAZ) | age in days | 0–1856 |
| `lenanthro.txt` | Length/height-for-age (HAZ) | age in days (length < 731 d, height after) | 0–1856 |
| `hcanthro.txt` | Head circumference-for-age (HCZ) | age in days | 0–1856 |
| `acanthro.txt` | MUAC-for-age (ACZ) | age in days | 91–1856 |
| `wflanthro.txt` | Weight-for-length (WHZ, < 2 y) | length cm, 0.1 steps | 45–110 |
| `wfhanthro.txt` | Weight-for-height (WHZ, ≥ 2 y) | height cm, 0.1 steps | 65–120 |

Columns: `sex` (1 = male, 2 = female), index, `l`, `m`, `s` (Box-Cox LMS).

**Source:** WHO Child Growth Standards (2006) expanded LMS tables, taken from the
spreadsheets published by the World Health Organization at
`https://www.who.int/tools/child-growth-standards/standards` (downloaded
2026-11-24). Each file combines the boys and girls spreadsheets; `l`, `m` and `s`
are unchanged. WHO publishes its material under its own terms of use; check
them before redistributing these files outside the application.
