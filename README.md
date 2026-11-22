# Simple URL Redirection

## Description

# Simple URL Redirection

**A lightweight WordPress plugin for managing URL redirects, monitoring 404 errors, and improving website navigation and SEO.**

Simple URL Redirection makes it easy to manage permanent redirects, import and export URL mappings, track broken links, and discover relevant destinations for missing pages. It also provides optional HTTPS enforcement and canonical domain redirection.

Developed and maintained by **[Simple Plugins](https://github.com/simplewpplugins)**.

## Features

- **URL Redirect Management:** Create and manage permanent 301 redirects for old or broken URLs.
- **CSV Import & Export:** Bulk import and export redirects using CSV files.
- **404 Error Monitoring:** Track missing URLs to identify broken links.
- **Smart Redirect Suggestions:** Suggest relevant destinations using fuzzy URL matching.
- **Manual Approval:** Review and approve suggested redirects before activation.
- **Automatic Redirection:** Optionally redirect missing URLs to similar pages (disabled by default).
- **External URL Support:** Redirect visitors to internal pages or external HTTP(S) URLs.
- **HTTPS Enforcement:** Optionally redirect HTTP requests to HTTPS.
- **Canonical Domain Management:** Enforce a consistent www or non-www domain.
- **WooCommerce Compatibility:** Include published WooCommerce products in redirect suggestions.
- **Secure Administration:** WordPress capability checks, nonce verification, and input validation.

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- WooCommerce (optional)

## Installation

1. Download the latest release from [GitHub](https://github.com/simplewpplugins/simple-url-redirection).
2. Log in to your WordPress admin dashboard.
3. Navigate to **Plugins → Add New → Upload Plugin**.
4. Upload the plugin ZIP file.
5. Click **Install Now** and activate the plugin.
6. Navigate to **Simple URL Redirection** in your WordPress admin menu.

## Usage

### Managing URL Redirects

Create redirect mappings by specifying the original URL path and its destination.

For example:

- Source: `/old-page/`
- Destination: `/new-page/`

When the original URL returns a 404 error, visitors are automatically redirected to the new destination using a 301 permanent redirect.

### Importing and Exporting Redirects

Import multiple redirects using a CSV file.

**Example CSV format:**

```csv
old_url,new_url
/old-page/,/new-page/
/old-product/,/new-product/
/contact-old/,https://example.com/contact/
```

The plugin supports comma- and semicolon-separated CSV files, with a maximum file size of 5 MB and a limit of 20,000 rows.

You can also export existing redirect mappings for backup or migration.

### 404 Monitoring

The plugin records missing URL paths and hostnames, helping administrators identify broken links.

Review logged 404 requests to discover URLs that need redirection.

### Smart Redirect Suggestions

Simple URL Redirection can suggest relevant destinations by comparing missing URL paths with published WordPress content, including posts, pages, and WooCommerce products.

Administrators can review suggested destinations and approve appropriate redirects.

Automatic fuzzy redirection is available as an optional feature and is disabled by default.

### HTTPS and Canonical Domain

The plugin includes optional settings for:

- Redirecting HTTP requests to HTTPS.
- Enforcing a preferred www or non-www hostname.
- Maintaining consistent website URLs.

**Important:** Verify your hosting, SSL certificate, and proxy configuration before enabling these settings to avoid redirect loops.

## Frequently Asked Questions

### Does the plugin support 301 redirects?

Yes. Explicitly configured redirect mappings use HTTP 301 permanent redirects.

### Can I redirect visitors to external websites?

Yes. The plugin supports external destinations using HTTP and HTTPS URLs.

### Can I import redirects from another plugin?

Yes. You can import redirects from a compatible CSV file containing source and destination columns.

### Does the plugin automatically redirect broken URLs?

Automatic fuzzy redirection is available but disabled by default. Administrators can enable it in the plugin settings.

### Does it work with WooCommerce?

Yes. Published WooCommerce products can be included when generating suggested redirect destinations.

### Does the plugin override existing WordPress pages?

No. Explicit redirect mappings are processed when WordPress identifies the requested URL as a 404. Existing working URLs are not overridden by these mappings.

### Does the plugin store visitor IP addresses?

No. The 404 logging functionality stores requested paths and hostnames without storing visitor IP addresses.

### Can I migrate from the original client-specific plugin?

Automatic database migration is not included. Export existing redirects before switching plugins.

## Security

Simple URL Redirection follows WordPress security practices, including:

- Administrator capability verification.
- Nonce verification for administrative actions.
- Input validation for redirect destinations.
- HTTP(S) protocol restrictions for external URLs.
- CSV import limits.
- Protection against CSV formula injection during export.

## Known Limitations

- Query strings are not included when matching redirect source paths.
- Redirect mappings do not override existing working WordPress URLs.
- Circular redirects and redirect chains should be avoided.
- HTTPS enforcement requires correct server and proxy configuration.
- Existing data from older client-specific installations is not automatically migrated.

## Changelog

### 1.0.0 — Initial Release

- Initial public release under the Simple Plugins brand.
- Added URL redirect management.
- Added CSV import and export.
- Added 404 request logging.
- Added smart redirect suggestions.
- Added optional automatic fuzzy redirection.
- Added HTTPS and canonical domain enforcement.
- Added internal and external redirect support.
- Improved validation and security defaults.
- Added documentation and release packaging.

## Contributing

Contributions, bug reports, and feature suggestions are welcome.

Visit the [GitHub repository](https://github.com/simplewpplugins/simple-url-redirection) to submit issues or pull requests once the repository is published.

## License

Simple URL Redirection is licensed under the **GNU General Public License v2.0 or later (GPL-2.0-or-later)**.

See the `LICENSE` file for details.

## Author

**Simple Plugins**

- GitHub: https://github.com/simplewpplugins
- Repository: https://github.com/simplewpplugins/simple-url-redirection

## Installation

## Installation

### Install via WordPress Dashboard

1. Download the latest version of **Simple URL Redirection** from [GitHub](https://github.com/simplewpplugins/simple-url-redirection/releases).
2. Log in to your WordPress admin dashboard.
3. Navigate to **Plugins → Add New Plugin → Upload Plugin**.
4. Click **Choose File** and select the downloaded ZIP file.
5. Click **Install Now**.
6. Once installation is complete, click **Activate Plugin**.
7. Navigate to **Simple URL Redirection** in your WordPress admin menu to configure the plugin.

### Install Manually via FTP

1. Download the plugin ZIP file and extract it.
2. Upload the `simple-url-redirection` folder to `/wp-content/plugins/`.
3. Log in to your WordPress admin dashboard.
4. Navigate to **Plugins → Installed Plugins**.
5. Find **Simple URL Redirection** and click **Activate**.
6. Open the plugin's admin menu to manage redirects and configure settings.

### Initial Configuration

After activation:

1. Review the plugin settings and configure your preferred redirect behavior.
2. Add URL redirects manually or import existing mappings using a CSV file.
3. Enable 404 monitoring to identify broken URLs.
4. Review smart redirect suggestions and approve relevant destinations.
5. Optionally enable automatic fuzzy redirects, HTTPS enforcement, or canonical domain redirection.

**Important:** Automatic fuzzy redirects and HTTPS enforcement are disabled by default. Test these features carefully before enabling them on a production website.

## Usage

## Usage

### 1. Managing URL Redirects

Simple URL Redirection allows you to manage permanent 301 redirects for broken or outdated URLs.

1. Navigate to **Simple URL Redirection** in your WordPress dashboard.
2. Open the redirect management section.
3. Enter the original URL path and its destination.
4. Save the redirect.

**Example:**
- Source URL: `/old-page/`
- Destination URL: `/new-page/`

When a visitor requests the old URL and WordPress returns a 404 error, the plugin redirects them to the new destination.

Both internal and external HTTP(S) destinations are supported.

### 2. Importing Redirects Using CSV

You can import multiple redirects at once using a CSV file.

1. Open the plugin's CSV import section.
2. Prepare a CSV file containing the source and destination URLs.
3. Upload the file and start the import.
4. Review the imported redirects.

**Example CSV:**

```csv
old_url,new_url
/old-page/,/new-page/
/old-product/,/new-product/
/old-contact/,https://example.com/contact/
```

The plugin supports comma- and semicolon-separated CSV files, with a maximum file size of 5 MB and a limit of 20,000 rows.

### 3. Exporting Redirects

Export your existing redirect mappings for backup or migration.

1. Open the redirect management section.
2. Select the CSV export option.
3. Download the generated CSV file.

The exported file can be used for backup or imported into another compatible installation.

### 4. Monitoring 404 Errors

The plugin helps identify broken URLs requested by visitors and search engines.

1. Open the 404 monitoring section.
2. Review recorded missing URL paths.
3. Identify outdated or broken links.
4. Create redirects for URLs that have suitable replacement pages.

This helps improve website navigation and reduce unnecessary 404 errors.

### 5. Smart Redirect Suggestions

Simple URL Redirection can suggest relevant destinations for missing URLs by comparing requested paths with existing WordPress content.

Supported content includes:

- WordPress posts
- WordPress pages
- WooCommerce products

Review the suggested destinations and approve appropriate redirect mappings.

### 6. Automatic Fuzzy Redirection

The plugin optionally supports automatic redirection when a missing URL closely matches existing content.

1. Open the plugin settings.
2. Enable automatic fuzzy redirection.
3. Configure the available matching options.
4. Save your settings.

**Note:** Automatic fuzzy redirection is disabled by default. Enable it only after testing to avoid redirecting visitors to unrelated pages.

### 7. HTTPS Enforcement

You can configure the plugin to redirect HTTP requests to HTTPS.

1. Open the plugin settings.
2. Enable HTTPS enforcement.
3. Save your changes.
4. Test your website using both HTTP and HTTPS URLs.

**Important:** Ensure your SSL certificate and hosting configuration support HTTPS before enabling this feature.

### 8. Canonical Domain Redirection

Maintain consistent website URLs by enforcing a preferred domain format.

The plugin supports:

- `www.example.com`
- `example.com`

Select your preferred hostname in the settings and save your changes.

Visitors accessing the alternative hostname will be redirected to the configured canonical domain.

### 9. Best Practices

- Use permanent 301 redirects when a page has permanently moved.
- Avoid creating redirect loops or unnecessary redirect chains.
- Regularly review 404 logs and redirect suggestions.
- Verify destination URLs before importing redirects.
- Test HTTPS and canonical domain settings before enabling them on production websites.
- Export your redirect mappings before making significant changes.

## Changelog

### 1.0.0 — 2026-11-22

## Simple URL Redirection v1.0.0 — Initial Release

The first public release of Simple URL Redirection, a lightweight WordPress plugin for managing URL redirects, monitoring 404 errors, and improving website navigation.

### Features
- Create and manage URL redirects.
- Bulk import and export redirects using CSV files.
- Monitor and log 404 errors.
- Suggest relevant pages for broken URLs using fuzzy matching.
- Optional automatic redirection based on URL similarity.
- Enforce HTTPS and canonical domains (www/non-www).
- Support internal and external redirect destinations.
- User-friendly WordPress admin interface.
- Security improvements, input validation, and safe default settings.

**Author:** Simple Plugins  
**Version:** 1.0.0  
**License:** GPL-2.0-or-later

## Changelog

### 1.0.0 — Initial Release

- Initial public release of Simple URL Redirection.
- Added URL redirect management with 301 redirects.
- Added bulk CSV import and export.
- Added 404 error monitoring.
- Added smart redirect suggestions using fuzzy matching.
- Added optional automatic redirection for missing URLs.
- Added HTTPS enforcement and canonical domain redirection.
- Added support for internal and external redirect destinations.
- Improved security, input validation, and default settings.
