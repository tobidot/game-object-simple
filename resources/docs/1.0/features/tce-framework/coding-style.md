# Coding Style

- [Naming](#naming)
- [DOM References Are `$`-Prefixed](#dom-references-are-dollar-prefixed)
- [The One Exception: Platform APIs](#the-one-exception-platform-apis)
- [Explicit Accessibility, Always](#explicit-accessibility-always)
- [Splitting a Non-Trivial Class](#splitting-a-non-trivial-class)
- [Libraries Stay One Class](#libraries-stay-one-class)
- [Validate External Input](#validate-external-input)
- [Tooling](#tooling)
- [Worked Example](#worked-example)
- [Do's and Don'ts](#dos-and-donts)

<a name="naming"></a>
## Naming

TCE TypeScript follows a C++-inspired convention:

| Identifier kind                    | Case                     | Example                                      |
|------------------------------------|--------------------------|----------------------------------------------|
| Variable, field                    | `snake_case`             | `abort_controller`, `applet_scss`            |
| Function parameter                 | `snake_case`             | `element_name`, `on_progress`                |
| Function / method                  | `snake_case`             | `attach_styles`, `with_binding`, `set_count` |
| Class / interface / type           | `StudlyCase`             | `TceRoot`, `TceNotepadLogic`, `TceSettingValue` |
| zod schema (a value)               | `snake_case` + `_schema` | `package_meta_schema`, `window_request_detail_schema` |
| Event name constant                | `UPPER_SNAKE` + `_EVENT` | `TCE_WINDOW_REQUEST_EVENT`                   |

This applies to the SDK, every element and every library — one rule, not a per-package choice.

<a name="dom-references-are-dollar-prefixed"></a>
## DOM References Are `$`-Prefixed

Any variable — field or local — whose value *is* an `HTMLElement` (or a subtype) is prefixed
with `$`, on top of snake_case: `$icons_layer`, `$title`, `$save_button`. This makes "I can
call `.style`/`.addEventListener` on this" visible at every call site. It does **not** apply to
a `ShadowRoot` (not an `HTMLElement`), a `CanvasRenderingContext2D`, or a value that merely
*contains* elements (`windows: Map<string, HTMLElement>`):

```ts
public $titlebar: HTMLDivElement;         // is an element — prefixed
public windows: Map<string, HTMLElement>; // holds elements — not prefixed
public shadow: ShadowRoot;                // not an HTMLElement — not prefixed
```

<a name="the-one-exception-platform-apis"></a>
## The One Exception: Platform APIs

Identifiers you don't choose keep their native casing:

- Custom Element lifecycle: `constructor`, `connectedCallback`, `disconnectedCallback`,
  `attributeChangedCallback`, `observedAttributes`.
- Built-in classes and members: `HTMLElement`, `ShadowRoot`, `AbortController`,
  `addEventListener`, `querySelector`, `textContent`, …
- `element.dataset.*` — the platform maps `data-element-name` to `dataset.elementName`; read it
  as-is, never invent a snake_case shadow of it.
- Anything from a third-party import (`z.object`, `defineConfig`).

Everything you introduce is snake_case (or StudlyCase for a type), and every
`HTMLElement`-typed one of those is `$`-prefixed.

<a name="explicit-accessibility-always"></a>
## Explicit Accessibility, Always

Every class member — every method, every field, every constructor — states `public`,
`private` or `protected`. TypeScript's implicit default is never relied on, including for
platform-named members (`public connectedCallback()`, `public static get observedAttributes()`):

```ts
// Right — every member explicit, including the constructor
class Example {
  private value: number;
  public constructor() { this.value = 0; }
  public compute(): number { return this.value; }
}
```

<a name="splitting-a-non-trivial-class"></a>
## Splitting a Non-Trivial Class

A very small element (a handful of fields, one or two listeners — the generator's `minimal`
preset) stays one class. Once an element has real state, real DOM construction and real
decisions — roughly, once you'd struggle to describe it in one sentence — it is five
collaborating classes, each with one job, named `<Class><Role>`:

| Class          | Holds                                                                  | Example                  |
|----------------|------------------------------------------------------------------------|--------------------------|
| **Settings**   | Readonly configuration constants                                       | `TceNotepadSettings`     |
| **References** | Every `$`-prefixed DOM element, the shadow root; built once; public     | `TceNotepadReferences`   |
| **Properties** | Mutable instance state, including the `abort_controller`               | `TceNotepadProperties`   |
| **Renderer**   | Pure painting: given data, updates the DOM; decides nothing            | `TceNotepadRenderer`     |
| **Logic**      | Every decision and listener; `start()`/`stop()`; calls the Renderer    | `TceNotepadLogic`        |

The element class is a **shallow delegate**: its constructor wires the five together,
`connectedCallback`/`disconnectedCallback` call exactly `logic.start()`/`logic.stop()`, and any
other public method is a one-line forward into Logic. No `if` lives in the element class.

```ts
export class TceNotepad extends HTMLElement {
  private readonly settings: TceNotepadSettings;
  private readonly references: TceNotepadReferences;
  private readonly properties: TceNotepadProperties;
  private readonly renderer: TceNotepadRenderer;
  private readonly logic: TceNotepadLogic;

  public constructor() {
    super();
    this.settings = new TceNotepadSettings();
    this.references = new TceNotepadReferences(this);
    this.properties = new TceNotepadProperties(this.settings.default_path);
    this.renderer = new TceNotepadRenderer(this.references);
    this.logic = new TceNotepadLogic(this, this.references, this.properties, this.renderer, this.settings);
  }

  public connectedCallback(): void { this.logic.start(); }
  public disconnectedCallback(): void { this.logic.stop(); }
}
```

Renderer and Logic are the easiest to mix up; the test is **does this method decide anything,
or only paint**. A Renderer method that builds taskbar buttons and wires their clicks to a
callback *it was given* is painting; deciding that a click toggles minimize/restore is Logic,
which hands that decision to the Renderer as the callback. When a Renderer method needs an `if`
about *why* it paints something a certain way, that decision has leaked and moves back to Logic.

Collaborators that talk to other packages (a `<Class>FileSystemConnection` that loads a library
and asserts its contracts) are additional single-purpose classes next to the five, not code
inside Logic.

<a name="libraries-stay-one-class"></a>
## Libraries Stay One Class

A library has no DOM, so the split does not apply: it is one class extending `TcePackage`, with
its schemas and exported contract constants in the same file or a sibling `*-schema.ts`
([Interfacing: Publishing a Contract](./interfacing#publishing-a-contract)).

<a name="validate-external-input"></a>
## Validate External Input

Anything a package did not produce itself is untrusted and is parsed through a
[zod](https://zod.dev) schema before use. Use `zod/mini` (`import * as z from 'zod/mini'`):
it tree-shakes far better than classic zod, and every package bundles its own copy. External
input, and how to read it with `@tce-v3/common`:

| Source                                       | Read it with                                                           |
|----------------------------------------------|------------------------------------------------------------------------|
| HTTP responses (the registry, any API)       | `fetch_json(url, schema)` — throws `TceValidationError` on mismatch   |
| JSON in `localStorage`                       | `read_storage_json(key, schema)` — `null` if missing, corrupt or wrong |
| Plain text in `localStorage`                 | `read_storage_text(key)` — no schema, but storage can be unavailable   |
| `CustomEvent` details from other packages    | `event_detail(event, schema)` — `null` plus a warning if malformed     |
| Attributes that aren't plain strings         | `read_attribute(element, name, schema, fallback)` with `z.coerce.number()` etc. |
| Arguments to a library's public functions    | `parse_or_throw(schema, value, source)` at the top of the function     |
| Values from IndexedDB, globals, other scripts | `parse_or_throw` or `parse_or_null`                                    |
| URLs                                          | `http_url_schema` — plain `z.url()` also accepts `javascript:`         |

The schema is the single source of truth: declare it, derive the type
(`export type TceWindowRequestDetail = z.infer<typeof window_request_detail_schema>`), and never
hand-write an interface next to it. A package that publishes a protocol (events, library
functions) exports its schemas with it, so sender and receiver share one definition. Functions
inside a payload use `function_schema<F>()`.

Decide what failure means at each boundary: required input that is wrong is a bug in the sender,
so throw (`bind()`, `register_group()`, `fetch_json`); optional or recoverable input falls back
to a default and logs why (corrupt storage, a bad attribute, a malformed event).

Input from the user via form controls is already typed by the control: use `valueAsNumber` or
`checked` and skip `NaN`; don't string-parse it.

<a name="tooling"></a>
## Tooling

The lint config — `eslint.config.js` in the v3 workspace, and the identical file in every
generated project — mechanically enforces:

- **Explicit accessibility** via `@typescript-eslint/explicit-member-accessibility`
  (`accessibility: 'explicit'`), including constructors.
- **Validated input** by banning `JSON.parse`, `parseInt`/`parseFloat`, argument-less
  `.json()` calls, `as CustomEvent<…>` casts and `localStorage.getItem` everywhere except the
  SDK's `validation.ts`, each with a message naming the helper to use instead.

`npm run check` runs `tsc --noEmit` and `eslint .`; a package is not done while either fails.
The `$`-prefix and the five-way split are not mechanically enforced — a linter cannot know a
variable holds an `HTMLElement` without full type information, and "should this class be
split" is a judgment — so they are enforced in review against this page.

<a name="worked-example"></a>
## Worked Example

```ts
export class TceNotepadLogic {                               // class: StudlyCase, role suffix
  public constructor(                                        // accessibility explicit
    private readonly host: HTMLElement,                      // not $-prefixed? It IS an element:
    private readonly references: TceNotepadReferences,       //   the host is the one conventional
    private readonly properties: TceNotepadProperties,       //   exception, named `host` everywhere
    private readonly renderer: TceNotepadRenderer,
    private readonly settings: TceNotepadSettings,
  ) {}

  public start(): void {                                      // method: snake_case
    this.properties.abort_controller = new AbortController(); // variable: snake_case
    const signal = this.properties.abort_controller.signal;
    this.references.$save_button.addEventListener('click', () => void this.save(), { signal });
    if (!this.properties.is_load_started) {                   // decision: lives in Logic
      this.properties.is_load_started = true;
      void this.load();
    }
  }

  private async load(): Promise<void> {                      // reads the init- attribute once
    this.properties.file_path = this.host.getAttribute('init-file-path') ?? this.settings.default_path;
    this.renderer.set_path(this.properties.file_path);         // painting: delegated
  }
}
```

<a name="dos-and-donts"></a>
## Do's and Don'ts

- ✅ Do name every variable, parameter and function you write in `snake_case`, every type in
  `StudlyCase`, every schema `*_schema`.
- ✅ Do prefix every `HTMLElement`-typed variable with `$`; the element's own `host` is the
  single named exception.
- ✅ Do write `public`/`private`/`protected` on every member, including constructors.
- ✅ Do split an element into Settings/References/Properties/Renderer/Logic once it stops being
  describable in one sentence; keep the element class a shallow delegate.
- ✅ Do parse every external input through a zod schema and derive its type from the schema.
- ✅ Do run `npm run check` before publishing.
- ❌ Don't mix conventions because an imported library uses camelCase — wrap at the boundary.
- ❌ Don't invent a snake_case shadow of a `dataset` property.
- ❌ Don't `$`-prefix a variable that merely contains or relates to elements.
- ❌ Don't cast external data into a type — a cast checks nothing at runtime.
- ❌ Don't let a Renderer method decide anything beyond how to paint the data it was given.
