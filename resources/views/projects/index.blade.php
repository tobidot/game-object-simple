@php
    use App\Models\Project;
    use \Illuminate\Contracts\Pagination\LengthAwarePaginator;
@endphp
@php
    /**
     * @var LengthAwarePaginator $paginator
     */
@endphp
<x-layouts.app class="projects">
    <x-slot name="title">
        Project - Archive
    </x-slot>

    <x-pagination.section :paginator="$paginator"/>

    <x-archives.with-excerpts :paginator="$paginator"/>
</x-layouts.app>

