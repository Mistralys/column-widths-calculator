### v2.1.0 - Production bug-fix release
- Fixed `OverflowFixer::fix()` incorrectly casting adjusted values to `int` in float mode, breaking the decimal precision guaranteed by `setFloatValues()`. The fix now applies the `(int)` cast only in integer mode via an `isIntegerMode()` conditional.
- Fixed `SurplusRemover::remove()` lacking a recursion depth guard. A degenerate configuration where all columns are at `minWidth` and cannot absorb surplus would previously trigger a fatal stack overflow. A 100-iteration depth cap is now enforced via a private property, matching the safe upper bound for any realistic column setup.

### v2.0.0 - PHP 7.4 release
- Upgraded the PHP code to 7.4 standards.
- Loosened the `mistralys/application-utils` version constraint for more flexibility.
- Updated the naming scheme of test classes to PHPUnit standards.
- Minor PHPStan code analysis fixes.
- Added this changelog file.

### v1.0.3 - Bugfix & minor feature release
- Fixed some integer rounding issues in some cases.
- Added the `getPixelValues()` method for easy conversion to absolute pixel values.

### v1.0.2 - Dependency release
- Further downgraded the application-utils to ^1.0.10, the absolute minimum feature set.

### v1.0.1 - Dependency release
- Downgraded application-utils requirement to ^1.1 to allow for more flexibility when including the package.

### v1.0.0 - Initial feature set release
