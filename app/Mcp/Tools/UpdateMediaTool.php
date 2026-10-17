<?php

namespace App\Mcp\Tools;

use App\Enums\PublishState;
use App\Mcp\Concerns\PresentsModels;
use App\Models\Attachment;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

use function Illuminate\Filesystem\join_paths;

#[Name('update_media')]
#[IsIdempotent]
#[Description(<<<'TEXT'
Update a media attachment's metadata: publish state and/or file name.
Renaming moves the file on disk and changes its url, so pages embedding the old url must be updated too (the old and new url are returned).
To replace the file content itself, upload a new file with upload_media and attach it instead.
TEXT)]
class UpdateMediaTool extends Tool
{
    use PresentsModels;

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
            'publish_state' => ['sometimes', Rule::enum(PublishState::class)],
            'file_name' => ['sometimes', 'string', 'max:200', 'regex:/^[A-Za-z0-9][A-Za-z0-9._\-]*$/'],
        ]);

        $attachment = Attachment::query()->find($validated['id']);
        if (! $attachment) {
            return Response::error('Attachment not found.');
        }

        $oldUrl = $attachment->url;

        if (array_key_exists('file_name', $validated) && $validated['file_name'] !== $attachment->file_name) {
            $disk = Storage::disk('public');
            $from = join_paths($attachment->path, $attachment->file_name);
            $to = join_paths($attachment->path, $validated['file_name']);
            if ($disk->exists($to)) {
                return Response::error("A file named '{$validated['file_name']}' already exists in this attachment's folder.");
            }
            if ($disk->exists($from)) {
                $disk->move($from, $to);
            }
            $attachment->file_name = $validated['file_name'];
            $attachment->url = join_paths('/storage', $attachment->path, $validated['file_name']);
        }

        if (array_key_exists('publish_state', $validated)) {
            $attachment->publish_state = PublishState::from($validated['publish_state']);
        }

        $attachment->save();

        return Response::structured([
            'attachment' => $this->presentAttachment($attachment),
            'previous_relative_url' => $oldUrl,
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required()->description('Attachment id.'),
            'publish_state' => $schema->string()->enum(array_column(PublishState::cases(), 'value')),
            'file_name' => $schema->string()->max(200)->description('New file name incl. extension, e.g. "hero-image.png".'),
        ];
    }
}
