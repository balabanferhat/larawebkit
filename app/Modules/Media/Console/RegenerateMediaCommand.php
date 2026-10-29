<?php

namespace App\Modules\Media\Console;

use App\Modules\Media\ImagePipeline;
use App\Modules\Media\Models\Media;
use Illuminate\Console\Command;
use Throwable;

class RegenerateMediaCommand extends Command
{
    protected $signature = 'larawebkit:media:regenerate {--id=* : Only these media IDs}';

    protected $description = 'Rebuild WebP variants of every image from the private originals (run after changing config/larawebkit-media.php).';

    public function handle(ImagePipeline $pipeline): int
    {
        $query = Media::query()->when($this->option('id'), fn ($q, $ids) => $q->whereIn('id', $ids));
        $bar = $this->output->createProgressBar($query->count());
        $failed = 0;

        $query->lazyById()->each(function (Media $media) use ($pipeline, $bar, &$failed) {
            try {
                $pipeline->regenerate($media);
            } catch (Throwable $e) {
                $failed++;
                $this->newLine();
                $this->warn("#{$media->id}: ".$e->getMessage());
            }
            $bar->advance();
        });

        $bar->finish();
        $this->newLine();
        $this->info(__('larawebkit-media.regenerated', ['count' => $query->count() - $failed, 'failed' => $failed]));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
