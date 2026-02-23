# Public API Surface

Only public members are listed. Internal implementation details and private/protected members are omitted.

---

## `Mistralys\WidthsCalculator\Calculator`

The sole public entry point for consumers. Implements `AppUtils\Interface_Optionable`.

### Constants

```php
public const ERROR_INVALID_MIN_WIDTH = 61501;
public const ERROR_EMPTY_COLUMN_ARRAY = 61502;
```

### Factory

```php
public static function create(array<array-key,float> $columnValues) : Calculator
```

### Configuration

```php
public function getDefaultOptions() : array<string,mixed>
// Returns: ['maxTotal' => 100, 'minPerCol' => 1, 'integerValues' => true]

public function getMaxTotal() : float
public function setMaxTotal(float $total) : Calculator

public function getMinWidth() : float
public function setMinWidth(float $width) : Calculator
// Throws \InvalidArgumentException(ERROR_EMPTY_COLUMN_ARRAY) if column array is empty
// Throws \InvalidArgumentException(ERROR_INVALID_MIN_WIDTH) if $width > getMaxMinWidth()

public function getMaxMinWidth() : float
// Returns: getMaxTotal() / column count
// Returns 0.0 if the column array is empty (no division attempted)

public function isIntegerMode() : bool
public function setFloatValues(bool $enable = true) : Calculator
```

### Output

```php
public function getValues() : array<string,int|float>
// Returns column names mapped to their calculated widths.
// Triggers lazy calculation on first call; subsequent calls return the cached result (idempotent).
// @throws \InvalidArgumentException with code ERROR_EMPTY_COLUMN_ARRAY (61502) if created with an empty column array.

public function getPixelValues(int $targetWidth) : array<string,int>
// Converts percentage values to absolute pixel widths summing to $targetWidth.
```

### Internal Access (used by worker classes)

```php
public function getColumns() : Column[]
public function getOperations() : Operations
```

---

## `Mistralys\WidthsCalculator\Calculator\Column`

Value object representing a single named column with a numeric width value.

```php
public function __construct(string $name, float $value)

public function getName()    : string
public function getValue()   : float
public function setValue(float $value) : void
public function isMissing()  : bool
public function makeMissing() : void
```

A column is flagged as "missing" when its initial value is `<= 0`.

---

## `Mistralys\WidthsCalculator\Calculator\Operations`

Read-only calculation helpers shared by all worker classes. Not intended for consumer use.

```php
public function __construct(Calculator $calculator)

public function calcTotal()           : float   // Sum of all column values
public function calcTotalNotMissing() : float   // Sum of non-missing column values
public function countColumns()        : int     // Total number of columns
public function countMissing()        : int     // Number of missing (0-value) columns
```

---

## `Mistralys\WidthsCalculator\Calculator\MissingFiller`

Internal worker. Fills columns that have no value (value was 0).

```php
public function __construct(Calculator $calculator)
public function fill() : void
```

---

## `Mistralys\WidthsCalculator\Calculator\LeftoverFiller`

Internal worker. Distributes any remaining gap to reach exactly `maxTotal` after integer rounding.

```php
public function __construct(Calculator $calculator)
public function fill() : void
```

---

## `Mistralys\WidthsCalculator\Calculator\OverflowFixer`

Internal worker. Proportionally scales all non-missing column values down when their sum exceeds `maxTotal`.

```php
public function __construct(Calculator $calculator)
public function fix() : void
```

---

## `Mistralys\WidthsCalculator\Calculator\SurplusRemover`

Internal worker. Removes excess total that arises when minimum-width enforcement pushes the sum above `maxTotal`.

```php
public function __construct(Calculator $calculator)
public function remove() : void
```
