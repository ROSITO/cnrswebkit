# PHP 8.3 migration

This maintenance release keeps the historical CNRS Web Kit theme operational on PHP 8.3 while tightening its public entry points.

## Requirements

* WordPress 6.4 or newer
* PHP 8.1 or newer; validation is performed with PHP 8.3.6
* A maintained Pods 3.x release compatible with the selected WordPress and PHP versions

## Compatibility changes

* `strftime()` and `utf8_encode()` are replaced with WordPress date and timezone APIs.
* Runtime-created pagination properties are declared explicitly.
* Pods values are checked before counting or iterating over them.
* The removed `FILTER_SANITIZE_STRING` filter is replaced with WordPress sanitization.

## Security changes

* Public AJAX requests require a WordPress nonce and validated selectors and identifiers.
* Contact, job and newsletter submissions are rate limited by source address.
* User-controlled email addresses are used only as `Reply-To`; the configured site address is the sender.
* Calendar downloads are served by `admin-post.php`, require a nonce and derive their contents from a published event instead of arbitrary POST fields.
* Media popup data is serialized with `wp_json_encode()` and no longer evaluated as JavaScript code.
* PHP sessions use strict mode, `HttpOnly`, `SameSite=Lax` and `Secure` cookies on HTTPS.

## Deployment

1. Back up the files and database.
2. Update WordPress and Pods on a staging copy.
3. Deploy the theme and clear WordPress, proxy and OPcache caches.
4. Test event lists, contacts, jobs, newsletters, media popups, outgoing mail and calendar downloads.
5. Keep `display_errors` disabled in production and monitor PHP and web-server logs after the rollout.
