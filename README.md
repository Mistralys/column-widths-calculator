# Column widths calculator

Small utility that can be used to convert arbitrary numeric values to column widths. Given a list of column names and values, it will intelligently convert the values to percentages, and fill empty columns with meaningful values.

**Features:**

  - Fills empty values with meaningful values
  - Converts any values to percentages, using proportional calculations
  - Ensures that the total always matches 100% exactly

## Installation

Using composer, simply require the package.

Via command line:

```
composer require mistralys/column-widths-calculator
```

Via composer.json:

```
"require" : 
{
    "mistralys/column-widths-calculator": "^1.0"
}
```

## Usage

Use the factory method to instantiate the calculator:

```php

$columns = array(
    'Col1' => 20,
    'Col2' => 0,
    'Col3' => 40
);

$calc =  Calculator::create($columns);

$converted = $calc->getValues();
```

This will return an array with the same keys, and the missing column value filled out:

```php
array(
    'Col1' => 20,
    'Col2' => 40,
    'Col3' => 40
);
```

## Fluent interface

All configuration methods return the `Calculator` instance, allowing calls to be chained:

```php
$calc = Calculator::create($columns)
    ->setMaxTotal(200)
    ->setMinWidth(10)
    ->setFloatValues();

$converted = $calc->getValues();
```

## Switching between integers and floats

By default, the calculator will produce integer values, even if all internal calculations are float based for precision. This can be turned off to retrieve float values:

```php
$calc = Calculator::create($columns);
$calc->setFloatValues();
```

In this case, no rounding is done at all - you will have to manually handle any rounding you wish to apply.

To check whether the calculator is currently operating in integer mode, use `isIntegerMode()`:

```php
$calc = Calculator::create($columns);
$calc->isIntegerMode(); // true (default)

$calc->setFloatValues();
$calc->isIntegerMode(); // false
```

## Handling minimum widths

When leaving columns empty to be automatically filled, there may not be enough width left for the columns to fill. Consider this configuration of columns:

```
Col1 = 80
Col2 = 20
Col3 = 0
```

By default, the calculator will guarantee that every column has a minimum width of `1`. As a result, the list would be adjusted like this, to allow the third column to have a width:

```
Col1 = 79
Col2 = 20
Col3 = 1
```

The surplus is subtracted from all non-empty columns, proportionally to their size (without going below the minimum width). 

### Setting the minimum size

The minimum size can be set like this:

```php
$calc = Calculator::create($columns);
$calc->setMinWidth(20);
```

This will ensure that all columns have a minimum size of 20%. 

Example:
 
```
Col1 = 80
Col2 = 20
Col3 = 0
```

Would give the following result:

```
Col1 = 60
Col2 = 20
Col3 = 20
```
 
NOTE: The maximum possible value for the minimum size depends on the amount of columns. Trying to set the minimum size to 40% for 3 columns for example, will throw an exception (40 x 3 is bigger than 100).


## Arbitrary numbering

The calculator can work with any number system, and converts the values proportionally.

Consider the following values:

```
Col1 = 1400
Col2 = 900
Col3 = 700
```

This will be converted to the following values:

```
Col1 = 38
Col2 = 33
Col3 = 30

= 100%
```

### Missing values

As long as there are more than one column with values, missing values will be filled with a value based on an average of the existing values, to get realistic results.

For example, the values:

```
Col1 = 1400
Col2 = 900
Col3 = 0
Col4 = 0 
```

Will be converted to this:

```
Col1 = 30
Col2 = 19
Col3 = 25
Col4 = 26

= 100%
```

## Changing the target value

By default, the Calculator assumes you want to work with percentages, and has the target value locked to 100. However, it is possible to adjust this value:

```php
$calc = Calculator::create($columns);
$calc->setMaxTotal(1000);
```

In the example above, the column widths will be calculated to reach a total of 1000, instead of the default 100.

## Edge cases

### Negative values

Column values that are negative are treated identically to `0` — they are considered **missing** and will be filled automatically, exactly like an empty column:

```php
$calc = Calculator::create(['A' => -10, 'B' => 50, 'C' => 0]);
$result = $calc->getValues();
// 'A' receives a positive width, same as if it were 0
```

### Empty column array

Passing an empty array to `Calculator::create([])` does not throw immediately. However, calling `getValues()` on an empty-array calculator will throw an `\InvalidArgumentException` with the error code `Calculator::ERROR_EMPTY_COLUMN_ARRAY` (61502):

```php
// Will throw \InvalidArgumentException (code 61502):
$calc = Calculator::create([]);
$result = $calc->getValues();
```

To handle this defensively:

```php
try {
    $result = Calculator::create([])->getValues();
} catch (\InvalidArgumentException $e) {
    if ($e->getCode() === Calculator::ERROR_EMPTY_COLUMN_ARRAY) {
        // No columns were provided
    }
}
```

Always ensure at least one column is present before calling `getValues()`.

---

## Converting to absolute pixel values

For convenience, the `getPixelValues()` method can convert the column percentages to absolute pixel values, given the maximum target width.

The following example converts the column widths to reach a width of 600px:

```php
$cols = array(
    'Col1' => 40,
    'Col2' => 40,
    'Col3' => 20
);

$calc = Calculator::create($columns);

$pixelWidths = $calc->getPixelValues(600);
```

The resulting widths will be:

```
Col1 = 207
Col2 = 207
Col3 = 186

= 600
```

---

## Development

### Running the test suite

```
composer test
```

Additional convenience aliases:

| Command | Purpose |
|---|---|
| `composer test` | Run the full PHPUnit test suite |
| `composer test-suite <name>` | Run a specific PHPUnit test suite by name |
| `composer test-filter <pattern>` | Run tests matching a name pattern |
| `composer test-group <group>` | Run tests belonging to a PHPUnit group |

### Static analysis

The project uses [PHPStan](https://phpstan.org/) at **level 9** (maximum strictness), configured via `docs/config/phpstan.neon`.

Run the analyser:

```
composer analyze
```

Or directly:

```
vendor/bin/phpstan analyse --configuration docs/config/phpstan.neon
```

To save the analysis report to `phpstan-result.txt` locally:

```
composer analyze-save
```

> **Note:** `analyze-save` suppresses the non-zero exit code (`|| true`) so that the report file is always written. This alias is **not suitable for CI pipelines** — use `composer analyze` there instead.

To clear the PHPStan result cache:

```
composer analyze-clear
```
