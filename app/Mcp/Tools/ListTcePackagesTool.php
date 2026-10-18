<?php

namespace App\Mcp\Tools;

use App\Http\Resources\TcePackageResource;
use App\Models\TcePackage;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list_tce_packages')]
#[IsReadOnly]
#[Description('List v3 TCE packages in the registry, sorted by namespace and name. By default only the latest version of each package; set all_versions to list every version, newest first.')]
class ListTcePackagesTool extends Tool
{
    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'namespace' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'all_versions' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $packages = TcePackage::query()
            ->with('dependencies')
            ->when($validated['namespace'] ?? null, fn ($query, string $namespace) => $query->where('namespace', $namespace))
            ->when($validated['search'] ?? null, fn ($query, string $search) => $query->where('name', 'like', "%$search%"))
            ->orderBy('namespace')->orderBy('name')
            ->orderByDesc('major')->orderByDesc('minor')->orderByDesc('patch')
            ->get();

        if (! ($validated['all_versions'] ?? false)) {
            $packages = $packages->unique(fn (TcePackage $package) => "$package->namespace/$package->name");
        }

        $per_page = $validated['per_page'] ?? 25;
        $page = $validated['page'] ?? 1;

        return Response::structured([
            'packages' => $packages->forPage($page, $per_page)
                ->map(fn (TcePackage $package) => (new TcePackageResource($package))->resolve())
                ->values()->all(),
            'current_page' => $page,
            'last_page' => max(1, (int) ceil($packages->count() / $per_page)),
            'total' => $packages->count(),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'namespace' => $schema->string()->description('Only packages of this publisher namespace.'),
            'search' => $schema->string()->description('Substring to match against the package name.'),
            'all_versions' => $schema->boolean()->description('List every version instead of only the latest. Defaults to false.'),
            'page' => $schema->integer()->min(1),
            'per_page' => $schema->integer()->min(1)->max(100)->description('Defaults to 25.'),
        ];
    }
}
