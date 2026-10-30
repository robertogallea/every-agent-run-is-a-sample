# Working agreement for this repository

This is a small Laravel 13 blog ("Notebook") with hand-written authentication and Pest tests.

## Acceptance criteria for the current task

{{CRITERIA}}

## Conventions

- Follow the existing style: controllers in `app/Http/Controllers`, policies in `app/Policies`, Blade views in `resources/views`, Pest feature tests in `tests/Feature`.
- Prefer Laravel's built-in features over custom code.
- Add Pest feature tests for the behaviour you add or change.
- Code must pass `./vendor/bin/pint --test` and `./vendor/bin/phpstan analyse` (level 6).

## You must not

- Edit or delete existing tests, or change `phpunit.xml` or `tests/Pest.php`. If an existing test fails, fix the code, not the test.
- Add, remove or update Composer packages, or edit `composer.json` / `composer.lock`.
- Modify or delete existing migrations. Add new migrations instead.
- Weaken authentication or authorization anywhere.
- Access the network.
