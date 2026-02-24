### v2.1.0 - Bugfix & Dependencies
- Core: Fixed decimal precision issues.
- Core: Added a recursion depth guard. 
- Tests: Added more tests to cover all aspects of the calculations.
- Docs: Added agentic coding support with the manifest and `AGENTS.md`.
- Code: Upgraded to PHP8.4 standards.
- Dependencies: Updated AppUtils to [v3.2.0](https://github.com/Mistralys/application-utils/releases/tag/3.2.0).

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
