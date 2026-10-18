# Element Lifecycle & Rendering

- [Shadow DOM: Attach and Build Once](#shadow-dom-attach-and-build-once)
- [Connect/Disconnect: One `AbortController` Per Connection](#connectdisconnect-one-abortcontroller-per-connection)
- [Registration](#registration)
- [Bootstrapping: The Entry Point Binds the Package](#bootstrapping-the-entry-point-binds-the-package)
- [Boot vs. Mount](#boot-vs-mount)
- [Icons Must Not Eagerly Load Their Element](#icons-must-not-eagerly-load-their-element)
- [Do's and Don'ts](#dos-and-donts)

This is the lifecycle every element follows. There is no legacy mode.

<a name="shadow-dom-attach-and-build-once"></a>
## Shadow DOM: Attach and Build Once

The constructor attaches the Shadow Root (`mode: 'open'`) and builds the element's complete
static subtree, once. The Custom Elements restriction is narrower than it is usually treated: a
constructor must not inspect its own attributes or light-DOM children, and must not **set** its
own attributes either — `setAttribute`, `this.tabIndex = 0`, `this.role = ...` on `this` throw
`NotSupportedError` at engine level. Anything that sets an attribute on the host belongs in
`connectedCallback`. Building your own Shadow DOM and configuring elements other than `this`
is exactly what a constructor is for, and the platform guarantees it runs exactly once, which
`connectedCallback` does not.

In a split element ([Coding Style](./coding-style#splitting-a-non-trivial-class)) this work is
the `References` class, constructed by the element's constructor:

```ts
export class TceNotepadReferences {
  public shadow: ShadowRoot;
  public $textarea: HTMLTextAreaElement;

  public constructor(host: HTMLElement) {
    this.shadow = host.attachShadow({ mode: 'open' });
    attach_styles(this.shadow, { css: applet_scss });   // see Styling Guide
    this.$textarea = document.createElement('textarea');
    this.$textarea.className = 'notepad__textarea';
    this.shadow.append(this.$textarea);                   // built exactly once
  }
}
```

`connectedCallback` fires every time the element is (re-)inserted — moving a window to another
container re-fires it. Rebuilding the subtree there destroys live DOM state (scroll position, an
in-progress transition, an unsaved input) for nothing. Static structure is constructor work.

<a name="connectdisconnect-one-abortcontroller-per-connection"></a>
## Connect/Disconnect: One `AbortController` Per Connection

`connectedCallback`/`disconnectedCallback` exist for what must restart with each attachment:
listeners, timers, observers. Create one `AbortController` per connection and hand its `signal`
to everything registered; teardown is one `abort()`:

```ts
public start(): void {                       // called from connectedCallback
  this.properties.abort_controller = new AbortController();
  const signal = this.properties.abort_controller.signal;

  this.references.$save_button.addEventListener('click', () => void this.save(), { signal });

  const timer = window.setInterval(() => this.tick(), 1000 / this.settings.frames_per_second);
  signal.addEventListener('abort', () => window.clearInterval(timer), { once: true });

  const observer = new ResizeObserver(() => this.fit());
  observer.observe(this.references.$canvas);
  signal.addEventListener('abort', () => observer.disconnect(), { once: true });
}

public stop(): void {                        // called from disconnectedCallback
  this.properties.abort_controller?.abort();
  this.properties.abort_controller = null;
}
```

Work that must happen once per element life (reading an `init-` attribute, loading a file) is
guarded by a flag in `Properties`, so re-attaching keeps the element's state.

<a name="registration"></a>
## Registration

`customElements.define` throws when called twice for one tag, and a package script can run more
than once on a page (an embed tag plus a package manager install). Registration is therefore
always guarded — and it is done in exactly one place, `TceElementPackage.init()`:

```ts
public async init(): Promise<TcePackageInterface> {
  if (!customElements.get(this.tag_name)) {
    customElements.define(this.tag_name, this.element_class);
  }
  return this;
}
```

An element never calls `customElements.define` itself.

<a name="bootstrapping-the-entry-point-binds-the-package"></a>
## Bootstrapping: The Entry Point Binds the Package

Every package has the same entry script, `src/index.ts`, and it is the only bootstrap there
is. Running it — as an embed `<script>` tag, injected by the package manager, or imported as a
module — does three things:

```ts
import { TceElementPackage, TceRoot } from '@tce-v3/common';
import { TceClock } from './TceClock';

const package_identifier = { namespace: 'tobidot', name: 'tce-clock' };
const tce_clock_package = new TceElementPackage('tce-clock', TceClock, { major: 0, minor: 1, patch: 0 });

TceRoot.bind_self(package_identifier, tce_clock_package, {   // 1. bind into the page's one loader
  kind: 'element',
  description: 'An analogue wall clock showing the local time.',
  width: 240,
  height: 240,
  dependencies: [],                                           //    declared, never auto-injected
});

void tce_clock_package.init()                                 // 2. boot: register the tag, assert contracts
  .catch((error: unknown) => TceRoot.report(error instanceof Error ? error : String(error)));

export * from './TceClock';                                   // 3. the module API, for the ES build
```

1. `TceRoot.bind_self()` binds the package under its identifier and version with its metadata.
   From now on anything on the page can `with_binding`/`load` it, and the package manager can
   list it as installed.
2. `init()` registers the tag immediately — tags already in the DOM upgrade right away — and
   performs the package's boot work. A failed boot is reported through `TceRoot.report`, never
   swallowed.
3. The exports make the ES build usable as a module and give consumers the types they vendor.

A library's entry script is the same with `kind: 'library'` and its own `TcePackage` subclass
as the instance. There is no other bootstrap sequence, no registry discovery in the SDK, and no
host-side code that a page must run before the first package script.

<a name="boot-vs-mount"></a>
## Boot vs. Mount

Two phases, never conflated:

1. **Boot (`init()`)**: called by the entry script, and by the loader when another package
   loads this one as a dependency (after the dependencies' own `init()`). Boot registers the tag,
   asserts every library function the package calls via `assert_contract`
   ([Interfacing](./interfacing#vendoring-and-asserting-a-contract)), and registers things like
   file handlers. It must stay cheap ([Assets & Build](./assets-and-build#init-stays-cheap-heavy-content-is-explicit)).
   A contract mismatch fails `init()` outright, with a message naming the function and both
   versions.
2. **Mount**: `document.createElement(name)` plus appending it — done by whoever shows the
   element (a page, the desktop opening a window). `init()` finishing says nothing about anything
   being on screen.

<a name="icons-must-not-eagerly-load-their-element"></a>
## Icons Must Not Eagerly Load Their Element

A desktop icon for a package resolves only lightweight metadata — `with_binding()` for
title/icon/width/height, or the registry entry — and never fetches, resolves dependencies for,
or `init()`s the package. The package is loaded on activation only (`TceRoot.fast_load` from
the double-click handler). A desktop with several icons for game-sized packages must never
download and initialize all of them just because they are visible.

<a name="dos-and-donts"></a>
## Do's and Don'ts

- ✅ Do build the static Shadow DOM once, in the constructor (the `References` class).
- ✅ Do bind one `AbortController` per `connectedCallback` and `abort()` it in
  `disconnectedCallback`; attach timers and observers to its signal.
- ✅ Do use `TceElementPackage` and `TceRoot.bind_self` in `src/index.ts`; subclass
  `TceElementPackage` for boot work and memoize it.
- ✅ Do report a failed boot with `TceRoot.report`.
- ✅ Do keep icon creation on `with_binding()`/registry metadata only.
- ❌ Don't read your own attributes or light-DOM children in the constructor, and don't set an
  attribute on `this` there — the platform throws.
- ❌ Don't rebuild `shadow.innerHTML` in `connectedCallback`.
- ❌ Don't call `customElements.define` outside `TceElementPackage.init()`.
- ❌ Don't assume `init()` means the element is visible.
- ❌ Don't `fast_load()` a package to draw its icon.
