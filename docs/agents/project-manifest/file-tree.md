# File Tree

Auto-generated folders (`vendor/`) are collapsed. Only non-obvious directories carry annotations.

```
column-widths-calculator/
├── changelog.md                    # Human-readable version history
├── composer.json                   # Package definition and dependency manifest
├── LICENSE                         # MIT licence
├── phpunit.xml                     # PHPUnit configuration (test suites, bootstrap)
├── README.md                       # User-facing documentation with usage examples
├── run-tests.bat                   # Windows convenience script to run PHPUnit
│
├── docs/
│   ├── agents/
│   │   └── project-manifest/       # THIS manifest (AI agent source of truth)
│   ├── config/
│   │   └── phpstan.neon            # PHPStan static-analysis configuration
│   └── phpstan/
│       └── _readme.txt             # Notes on running PHPStan
│
├── src/
│   ├── Calculator.php              # Public entry point — the only class consumers use
│   └── Calculator/                 # Internal worker classes (not part of public API)
│       ├── Column.php              # Value object representing a single named column
│       ├── LeftoverFiller.php      # Distributes any remaining gap to reach exactly maxTotal
│       ├── MissingFiller.php       # Assigns values to columns that were given 0
│       ├── Operations.php          # Shared calculation helpers (totals, counts)
│       ├── OverflowFixer.php       # Proportionally scales values down when sum > maxTotal
│       └── SurplusRemover.php      # Removes excess when min-width pushes total above maxTotal
│
├── tests/
│   ├── bootstrap.php               # Autoloader bootstrap for the test run
│   ├── assets/
│   │   └── classes/
│   │       └── CalculatorTestCase.php  # Base test case with shared helpers
│   └── testsuites/
│       ├── FloatValueTests.php     # Tests for float output mode
│       ├── GetValueTests.php       # Tests for the main getValues() output
│       ├── MinWidthTests.php       # Tests for minimum-width enforcement
│       └── PixelValueTests.php     # Tests for getPixelValues() conversion
│
└── vendor/                         # Composer-managed dependencies (not committed)
```
