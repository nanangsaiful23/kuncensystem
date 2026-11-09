# Local copy of laravelcollective/html

This is the official `laravelcollective/html` v6.4.1 source (MIT, see LICENSE.txt),
kept inside the project because upstream is abandoned and its composer
constraints stop at Laravel 10. It is loaded through a Composer "path"
repository in the root `composer.json`.

Changes from upstream:
- `composer.json`: require PHP >= 8.3 and illuminate/* ^11|^12|^13.
- `FormBuilder::__construct` / `HtmlBuilder::__construct`: explicit nullable
  types (`?Request`, `?UrlGenerator`) to avoid PHP 8.4 deprecations.

Long term, consider migrating views to `spatie/laravel-html`.
