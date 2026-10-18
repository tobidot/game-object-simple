<?php
/** @var \App\DTO\TceGeneratorConfig $config */
$class = $config->className;
?>
import { <?= $class ?>Properties } from './<?= $class ?>Properties';
import { <?= $class ?>References } from './<?= $class ?>References';
import { <?= $class ?>Renderer } from './<?= $class ?>Renderer';
import { <?= $class ?>Settings } from './<?= $class ?>Settings';

/**
 * Every decision: the frame loop, the bounce, the resize. `start()`/`stop()` are the element's
 * `connectedCallback`/`disconnectedCallback`; one `AbortController` per connection tears down
 * the timer and the observer ("Element Lifecycle & Rendering: Connect/Disconnect").
 */
export class <?= $class ?>Logic {
    public constructor(
        private readonly references: <?= $class ?>References,
        private readonly properties: <?= $class ?>Properties,
        private readonly renderer: <?= $class ?>Renderer,
        private readonly settings: <?= $class ?>Settings,
    ) {}

    public start(): void {
        this.properties.abort_controller = new AbortController();
        const signal = this.properties.abort_controller.signal;

        const observer = new ResizeObserver(() => this.fit_to_host());
        observer.observe(this.references.$canvas);
        signal.addEventListener('abort', () => observer.disconnect(), { once: true });

        const timer = window.setInterval(() => this.tick(), 1000 / this.settings.frames_per_second);
        signal.addEventListener('abort', () => window.clearInterval(timer), { once: true });

        this.fit_to_host();
    }

    public stop(): void {
        this.properties.abort_controller?.abort();
        this.properties.abort_controller = null;
    }

    private fit_to_host(): void {
        const width = Math.max(1, Math.floor(this.references.$canvas.clientWidth));
        const height = Math.max(1, Math.floor(this.references.$canvas.clientHeight));
        if (width === this.properties.width && height === this.properties.height) {
            return;
        }
        this.properties.width = width;
        this.properties.height = height;
        this.renderer.resize(width, height);
        this.renderer.paint(this.properties);
    }

    private tick(): void {
        const state = this.properties;
        const size = this.settings.square_size;
        state.x += state.dx;
        state.y += state.dy;
        if (state.x <= 0 || state.x + size >= state.width) {
            state.dx = -state.dx;
            state.x = Math.min(Math.max(state.x, 0), Math.max(0, state.width - size));
        }
        if (state.y <= 0 || state.y + size >= state.height) {
            state.dy = -state.dy;
            state.y = Math.min(Math.max(state.y, 0), Math.max(0, state.height - size));
        }
        this.renderer.paint(state);
    }
}
