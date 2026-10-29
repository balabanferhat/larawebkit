<?php

namespace App\Modules\Import\Console;

use App\Models\User;
use App\Modules\Import\WordPress\FileSource;
use App\Modules\Import\WordPress\RestSource;
use App\Modules\Import\WordPress\WordPressImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportWordPressCommand extends Command
{
    protected $signature = 'larawebkit:import:wordpress
        {source : Site URL (https://example.com) or path to an export .json file}
        {--dry-run : Show what would be imported without changing anything}
        {--skip-category=* : Category slug whose posts are not imported (repeatable)}
        {--gone : Old URLs of skipped posts and categories answer 410 Gone instead of 404}
        {--keep-empty-pages : Also import pages without content (theme parts like slider or header are skipped by default)}
        {--locale= : Language of the imported content (default: the site default)}
        {--author= : E-mail of an existing user who becomes the author of everything}
        {--no-images : Keep image URLs instead of downloading them}
        {--media-host=* : Extra host images may be downloaded from, e.g. a CDN (repeatable)}';

    protected $description = 'Import posts, pages, categories, tags, authors and images from WordPress (REST API or export file)';

    public function handle(WordPressImporter $importer): int
    {
        $input = (string) $this->argument('source');
        $author = null;
        if ($this->option('author')) {
            $author = User::where('email', $this->option('author'))->first();
            if (! $author) {
                $this->components->error('No user with the e-mail address '.$this->option('author').'.');

                return self::FAILURE;
            }
        }
        $locale = $this->option('locale') ?: (string) setting('general.default_locale', 'en');
        if (! array_key_exists($locale, (array) config('larawebkit.locales'))) {
            $this->components->error("Unknown locale \"$locale\".");

            return self::FAILURE;
        }

        try {
            $source = preg_match('#^https?://#i', $input) ? new RestSource($input) : new FileSource($input);
            $this->components->info(($this->option('dry-run') ? 'Dry run: nothing is changed. ' : '').'Importing from '.$source->siteUrl());
            $result = $importer->run($source, [
                'dry_run' => (bool) $this->option('dry-run'),
                'skip_categories' => (array) $this->option('skip-category'),
                'gone' => (bool) $this->option('gone'),
                'keep_empty_pages' => (bool) $this->option('keep-empty-pages'),
                'locale' => $locale,
                'author' => $author,
                'images' => ! $this->option('no-images') && ! $this->option('dry-run'),
                'media_hosts' => (array) $this->option('media-host'),
            ]);
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(['', 'new', 'updated', 'unchanged', 'skipped'], collect($result['report'])
            ->map(fn ($r, $kind) => [$kind, $r['created'], $r['updated'], $r['unchanged'], $r['skipped']])->values()->all());
        $this->line('Redirects (301) added: '.$result['redirects'].($this->option('gone') ? ', 410 Gone: '.$result['gone'] : ''));
        if ($result['missing_alt'] !== []) {
            $this->components->warn(count($result['missing_alt']).' images without alt text in WordPress; add a description in the media library:');
            foreach (array_slice(array_unique($result['missing_alt']), 0, 30) as $image) {
                $this->line('  - '.$image);
            }
        }
        foreach (array_slice($result['warnings'], 0, 50) as $warning) {
            $this->components->warn($warning);
        }
        if (count($result['warnings']) > 50) {
            $this->components->warn((count($result['warnings']) - 50).' more warnings.');
        }

        return self::SUCCESS;
    }
}
