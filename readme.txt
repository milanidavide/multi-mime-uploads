=== Multi-MIME Uploads ===
Tags: upload, MIME types, file extensions, WordPress uploads
License: BSD-3-Clause license
License URI: https://opensource.org/licenses/BSD-3-Clause

Adds support for multiple MIME types per file extension during file uploads.

== Description ==

Multi-MIME Uploads enhances WordPress by allowing multiple MIME types to be associated with a single file extension during the upload process.

Usage:
```php
function add_mimes_callback( $mimes, $user ) {
    $mimes['json'] = 'application/json';
    $mimes['dwg'] = [
        'application/acad',
        'image/vnd.dwg',
    ];
    return $mimes;
}
add_filter( 'multimimeup_add_mimes', 'add_mimes_callback', 10, 2 );
```

== Changelog ==

= 0.1.0 =
* Initial release.

== Upgrade Notice ==

= 0.1.0 =
* This is the first release of the plugin.