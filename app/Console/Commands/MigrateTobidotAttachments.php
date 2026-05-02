<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use App\Models\TobidotElement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MigrateTobidotAttachments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tobidot:migrate-attachments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate TobidotElement icon and code references to pivot-based attachments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // We use DB::table to access columns that might be dropped in the migration
        $elements = DB::table('tobidot_elements')->get();

        $this->info("Found " . $elements->count() . " elements to migrate.");

        foreach ($elements as $element) {
            $this->comment("Migrating element: {$element->name} (v{$element->major}.{$element->minor}.{$element->patch})");

            $model = TobidotElement::find($element->id);
            if (!$model) continue;

            // 1. Migrate Code Attachment
            if (isset($element->attachment_id) && $element->attachment_id) {
                $this->attachIfMissing($model, $element->attachment_id, 'code');
            } elseif ($element->content) {
                // Try to find attachment by path
                $path = $this->extractPathFromContent($element->content);
                if ($path) {
                    $attachment = Attachment::where('path', 'LIKE', "%$path%")->first();
                    if ($attachment) {
                        $this->attachIfMissing($model, $attachment->id, 'code');
                    }
                }
            }

            // 2. Migrate Icon Attachment
            if (isset($element->icon_attachment_id) && $element->icon_attachment_id) {
                $this->attachIfMissing($model, $element->icon_attachment_id, 'icon');
            } elseif ($element->icon) {
                // Try to find attachment by path
                $path = $this->extractPathFromIcon($element->icon);
                if ($path) {
                    $attachment = Attachment::where('path', 'LIKE', "%$path%")->first();
                    if ($attachment) {
                        $this->attachIfMissing($model, $attachment->id, 'icon');
                    }
                }
            }
        }

        $this->info("Migration completed.");
    }

    private function attachIfMissing(TobidotElement $model, $attachmentId, $relation)
    {
        $exists = DB::table('attachables')
            ->where('attachment_id', $attachmentId)
            ->where('attachable_id', $model->id)
            ->where('attachable_type', TobidotElement::class)
            ->where('relation', $relation)
            ->exists();

        if (!$exists) {
            $model->attachments()->attach($attachmentId, ['relation' => $relation]);
            $this->info("  Attached $relation (ID: $attachmentId)");
        } else {
            $this->line("  $relation already attached.");
        }
    }

    private function extractPathFromContent($content)
    {
        // /tobidot-elements/UUID/index.zip -> tobidot-elements/UUID
        $temp = array_filter(explode('/', $content), fn($item) => !empty($item));
        if (count($temp) > 0 && $temp[array_key_first($temp)] === 'storage') {
            array_shift($temp);
        }
        if (count($temp) > 1) {
            array_pop($temp);
            return implode('/', $temp);
        }
        return null;
    }

    private function extractPathFromIcon($icon)
    {
        // UUID/file.png or media/UUID/file.png
        $path = ltrim($icon, '/');
        if (Str::startsWith($path, 'media/')) {
            $path = Str::after($path, 'media/');
        }

        $temp = explode('/', $path);
        if (count($temp) > 1) {
            array_pop($temp);
            return implode('/', $temp);
        }
        return null;
    }
}
