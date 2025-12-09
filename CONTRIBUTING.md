Contributing

Thanks for considering a contribution to FlagPole!

Development setup
1. Install PHP 8.1+ and Composer v2.
2. Install dependencies:
   ```
   composer install
   ```
3. Run the test suite:
   ```
   composer test
   ```
4. Static analysis and coding standards:
   - Analyse (PHPStan level 8):
     ```
     composer analyse
     ```
   - Lint (PHP-CS-Fixer dry run):
     ```
     composer lint
     ```
   - Auto-fix style issues where possible:
     ```
     composer fix
     ```

Guidelines
- Follow PSR-12 coding style (automated via PHP-CS-Fixer config).
- Include tests for new features or bug fixes.
- Keep public API changes minimal and document them in the README and CHANGELOG.
- For large changes, please open an issue for discussion before investing significant effort.

Releasing (maintainers)
- Ensure CI is green on main.
- Update CHANGELOG.md.
- Tag a release (e.g., `v0.1.0`) and push the tag.
