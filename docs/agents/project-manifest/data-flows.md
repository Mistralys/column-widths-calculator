# Key Data Flows

## 1. Normal Flow — All Columns Have Values

Consumer provides a map of column names to positive widths.

```
Calculator::create(['A' => 40, 'B' => 60])
  └─► getValues()
        └─► calculate()               (guarded: runs only once)
              ├─ OverflowFixer::fix()  SKIP — total ≤ maxTotal
              ├─ MissingFiller::fill() SKIP — no missing columns
              ├─ SurplusRemover::remove() SKIP — no surplus
              ├─ convertToInteger()    Floor all values (integer mode)
              └─ LeftoverFiller::fill() Fill any gap caused by flooring
                    └─► returns array<string, int|float>
```

---

## 2. Missing Column Flow — Some Columns Are Zero

Columns with value `0` are treated as "missing" and filled automatically.

```
Calculator::create(['A' => 40, 'B' => 0])
  └─► getValues()
        └─► calculate()
              ├─ OverflowFixer::fix()     SKIP — no overflow
              ├─ MissingFiller::fill()
              │     Distributes (maxTotal − existing total) equally
              │     across all missing columns.  → B gets 60
              ├─ SurplusRemover::remove()
              │     If total now exceeds maxTotal (can happen with
              │     minWidth), proportionally reduces non-missing
              │     columns (respecting minWidth). Runs recursively
              │     until surplus is fully absorbed.
              │     Safety: recursion is capped at 100 iterations
              │     via a private $depth counter (resets on new
              │     Calculator instance). In normal operation,
              │     convergence occurs in 1–3 passes.
              ├─ convertToInteger()
              └─ LeftoverFiller::fill()   Fill any rounding gap
                    └─► returns array<string, int|float>
```

---

## 3. Overflow Flow — Input Values Sum Above maxTotal

When the raw input exceeds `maxTotal`, it is treated as an arbitrary numbering system and converted proportionally.

```
Calculator::create(['A' => 1400, 'B' => 900, 'C' => 700])
  └─► getValues()
        └─► calculate()
              ├─ OverflowFixer::fix()
              │     total = 3000, maxTotal = 100
              │     For each non-missing column:
              │       percentage = value * 100 / total
              │       adjusted   = floor(maxTotal * percentage / 100)
              │     Integer mode: setValue((int)$adjusted)  → A=46, B=30, C=23
              │     Float mode:   setValue($adjusted)       → whole-number floats
              │                   (floor() is retained; precision is preserved)
              ├─ MissingFiller::fill() SKIP
              ├─ SurplusRemover::remove() SKIP
              ├─ convertToInteger()
              └─ LeftoverFiller::fill()  → adds 1 to reach 100
```

---

## 4. Overflow + Missing Flow — Combinations

`OverflowFixer` accounts for missing columns by reducing the effective `maxTotal` for non-missing columns, leaving headroom for `MissingFiller` to fill in later.

```
Calculator::create(['A' => 1400, 'B' => 900, 'C' => 0])
  └─► calculate()
        ├─ OverflowFixer::fix()
        │     maxTotal for non-missing = 100 / (3 - 1) = 50
        │     Scales A and B proportionally into [0, 50]
        ├─ MissingFiller::fill()  → C gets remaining headroom
        ├─ SurplusRemover::remove()
        ├─ convertToInteger()
        └─ LeftoverFiller::fill()
```

---

## 5. Pixel Values Flow

`getPixelValues()` reuses the full pipeline by delegating to a fresh `Calculator` instance.

```
$calc->getPixelValues(600)
  └─► getValues()                       → percentage array
  └─► Calculator::create(percentages)   → new internal instance
        └─► setMaxTotal(600)
        └─► getValues()                 → pixel values summing to 600
              └─► (int) cast each value
                    └─► returns array<string, int>
```
