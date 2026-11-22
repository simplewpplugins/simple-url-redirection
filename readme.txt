=== Simple URL Redirection ===
Contributors: simplewpplugins
Tags: redirects, 301 redirect, 404, csv, url redirection
Requires at least: 5.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage WordPress URL redirects, import CSV mappings, log 404 requests and review suggested destinations.

== Description ==

Simple URL Redirection helps administrators manage URL changes without editing server configuration.

Features include:

* Add and remove permanent 301 redirect mappings.
* Import redirects from comma- or semicolon-separated CSV files.
* Export current redirect mappings as CSV.
* Log missing URLs and suggest matching posts, pages or WooCommerce products.
* Approve suggestions to create permanent redirects.
* Optionally enable automatic fuzzy matching, canonical host enforcement and HTTPS redirects.

Automatic fuzzy redirects and HTTPS enforcement are disabled by default.

== Installation ==

1. Upload the ZIP using Plugins > Add New > Upload Plugin, or copy the plugin folder to wp-content/plugins.
2. Activate Simple URL Redirection.
3. Open Simple URL Redirection in the admin menu.
4. Add a mapping manually or import a CSV file.
5. Review Settings before enabling optional redirects.

== Frequently Asked Questions ==

= What CSV columns are supported? =

Use old_url,new_url as a header, or put source paths and destinations in the first two columns. Example: /old-page/,/new-page/. Files must be CSV and are limited to 5 MB and 20,000 rows.

= Can I redirect to another domain? =

Yes. Explicit mappings can use external HTTP or HTTPS destinations.

= Will redirects override existing pages? =

No. Explicit mappings are applied to requests that WordPress reports as 404 errors.

= Are suggested matches redirected automatically? =

Not by default. Suggestions are logged for review. You can opt into automatic fuzzy redirects in Settings.

= Can I migrate data from an earlier plugin? =

Export mappings from the earlier plugin and import the resulting CSV. Automatic database migration is not included.

== Changelog ==

= 1.0.0 =

* Initial public release with manual and CSV redirects, 404 logs, suggestions and optional canonical redirects.
