Recorded WordPress REST API responses (`/wp-json/wp/v2/...`, trimmed to the fields the importer reads) for
`tests/Feature/Import/WordPressImportTest.php`. The tests never touch the network: `Http::fake()` serves these
files and generates the image bodies.
