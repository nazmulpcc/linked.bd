<?php

namespace App\Console\Commands;

use App\Models\Link;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'links:prune {--sweep : Remove orphaned QR assets}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired and guest links past their TTL';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deleted = 0;
        $guestCutoff = now()->subDays(config('links.guest_ttl_days'));

        Link::query()
            ->where(function ($query) use ($guestCutoff) {
                $query->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now())
                    ->orWhere(function ($nested) use ($guestCutoff) {
                        $nested->whereNull('user_id')
                            ->where('created_at', '<=', $guestCutoff);
                    });
            })
            ->orderBy('id')
            ->chunkById(100, function ($links) use (&$deleted) {
                foreach ($links as $link) {
                    $this->deleteQr($link);
                    $link->delete();
                    $deleted++;
                }
            });

        if ($this->option('sweep')) {
            $this->sweepOrphanedQrAssets();
        }

        $this->components->info("Deleted {$deleted} links.");

        return Command::SUCCESS;
    }

    private function deleteQr(Link $link): void
    {
        if (! $link->qr_path) {
            return;
        }

        Storage::disk('qr_code')->delete($link->qr_path);
    }

    private function sweepOrphanedQrAssets(): void
    {
        $disk = Storage::disk('qr_code');
        $paths = $disk->allFiles();

        if ($paths === []) {
            return;
        }

        $active = Link::query()
            ->whereNotNull('qr_path')
            ->pluck('qr_path')
            ->all();

        $activeLookup = array_fill_keys($active, true);

        foreach ($paths as $path) {
            if (! isset($activeLookup[$path])) {
                $disk->delete($path);
            }
        }
    }
}
