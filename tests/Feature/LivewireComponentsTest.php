<?php

namespace Tests\Feature;

use App\Enums\PublishState;
use App\Http\Livewire\Counter;
use App\Http\Livewire\Pagination;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireComponentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_counter_component_renders_and_increments(): void
    {
        Livewire::test(Counter::class)
            ->assertSee('counter__value', false)
            ->assertSee('counter__button', false)
            ->assertSee('0')
            ->call('increment')
            ->assertSee('1');
    }

    public function test_pagination_component_renders_items_and_pages(): void
    {
        Page::factory()->count(7)->create([
            'publish_state' => PublishState::PUBLISHED,
        ]);

        Livewire::test(Pagination::class, ['class' => Page::class])
            ->assertSee('livewire-pagination', false)
            ->assertSee('livewire-pagination__list', false)
            ->assertSee('livewire-pagination__item', false)
            ->assertSee('Page: 1')
            ->call('next')
            ->assertSee('Page: 2')
            ->call('previous')
            ->assertSee('Page: 1');
    }
}
