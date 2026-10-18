# Interfacing Between Elements

- [Three Ways Elements Talk](#three-ways-elements-talk)
- [1. Slots (Structure)](#1-slots-structure)
- [2. Bubbling Request Events](#2-bubbling-request-events)
    - [The Desktop Protocol](#the-desktop-protocol)
    - [The Nearest Owner Claims a Request](#the-nearest-owner-claims-a-request)
    - [Receivers Parse, Senders Are Not Trusted](#receivers-parse-senders-are-not-trusted)
    - [Queries Answer Through a Callback](#queries-answer-through-a-callback)
- [3. Library Functions (Services)](#3-library-functions-services)
    - [Per-Function Contracts, Not Whole-Library Versions](#per-function-contracts-not-whole-library-versions)
    - [Publishing a Contract](#publishing-a-contract)
    - [Vendoring and Asserting a Contract](#vendoring-and-asserting-a-contract)
    - [Staying Fully Typed](#staying-fully-typed)
    - [Optional Libraries](#optional-libraries)
    - [Two Consumers, Two Required Versions](#two-consumers-two-required-versions)
- [File Handlers](#file-handlers)
- [Do's and Don'ts](#dos-and-donts)

Packages are independently versioned, independently published, and never share source at
runtime. "Interfacing" therefore always means one of three narrow channels — never a direct
property or method call across a package boundary.

<a name="three-ways-elements-talk"></a>
## Three Ways Elements Talk

| Channel  | Direction         | Used for                                              |
|----------|-------------------|-------------------------------------------------------|
| Slot     | Parent → Child    | Structural composition ([Declaration](./declaration)) |
| Event    | Child → Owner     | Asking the owner to do something ("ask your parent")  |
| Library  | Any → Library     | Shared services (file system, settings, logging)      |

<a name="1-slots-structure"></a>
## 1. Slots (Structure)

Covered in [Declarative Structure](./declaration). A parent decides what to render inside a
child's named slot; neither reaches into the other's shadow tree.

<a name="2-bubbling-request-events"></a>
## 2. Bubbling Request Events

A child never reaches up to its owner; it dispatches a bubbling, `composed` custom event and
whoever owns it decides. `@tce-v3/desktop-protocol` is the single definition of these events:
for each one a name constant, a zod schema for the detail, the type derived from the schema, and
a `request_*` helper that dispatches it correctly. Elements that take part in the desktop
depend on this SDK package; elements that don't, don't load it — that is why it is not part of
`@tce-v3/common` ([Package boundaries](./package-model)).

<a name="the-desktop-protocol"></a>
### The Desktop Protocol

Requests (child → owner, dispatched with the helper):

| Event                            | Helper                  | Detail                                                                 | Handled by |
|----------------------------------|-------------------------|------------------------------------------------------------------------|------------|
| `tce.window.request`             | `request_window_action` | `{ action: fullscreen \| maximize \| restore \| minimize \| close \| resize \| retitle, width?, height?, title? }` | the owning window |
| `tce.context-menu.request`       | `request_context_menu`  | `{ x, y, items: [{ label, action, disabled? }] }`                      | the surface owner (desktop) |
| `tce.desktop.open-request`       | `request_open`          | `{ identifier, attributes?, title?, width?, height? }`                 | the nearest desktop |
| `tce.desktop.create-icon-request`| `request_create_icon`   | `{ identifier, label, icon_src?, attributes?, title?, width?, height? }` | the nearest desktop |
| `tce.desktop.layout-request`     | `request_layout`        | `{ on_layout }` — a query, answered through the callback ([below](#queries-answer-through-a-callback)) | the nearest desktop |
| `tce.desktop.composition-request`| `request_composition`   | `{ on_composition }` — a query: what fills each switchable slot, with attributes | the nearest desktop |
| `tce.desktop.configure-request`  | `request_configure`     | `{ target: icon \| component, enabled?, attributes? }` — change an icon's boot attributes, or switch a component on/off and set its attributes | the nearest desktop |

Notifications (dispatched by an implementation so an owner can react without a build-time
dependency on that implementation):

| Event                        | Dispatched by      | Meaning                                  |
|------------------------------|--------------------|------------------------------------------|
| `tce.window.closed` / `.minimized` / `.restored` / `.focused` / `.retitled` | a window | window lifecycle, for taskbars |
| `tce.icon.select` / `tce.icon.activate` | an icon | selection, activation (double-click/Enter) |
| `tce.desktop.icon-removed`   | the desktop        | an icon was removed for good              |

`request_open` is how one element opens another: the desktop loads the package if needed,
creates the element, sets the given attributes, and wraps it in a window. Attribute names are
validated — lower-case, never `on*` — so a request cannot install inline event handlers.

`request_create_icon` with `attributes` asks for a *shortcut*: an icon that, when activated, opens
the package with exactly those attributes set (and the given `title`, `width`, `height`), the way
`request_open` would. The desktop validates the attributes with the same rule, remembers shortcut
icons across reloads and offers "Remove shortcut" in their context menu; icons without
`attributes` stand for the package itself and are not remembered. This is how a file in the file
system gets an icon: the explorer and the code editor request a shortcut to the file's handler.

<a name="the-nearest-owner-claims-a-request"></a>
### The Nearest Owner Claims a Request

Because the events are `composed` and bubble, a request from inside nested shells (a desktop in a
window of another desktop) passes every owner on its way out. The first owner that handles a
request calls `event.stopPropagation()`, so exactly one owner acts; outer owners never see it.

<a name="receivers-parse-senders-are-not-trusted"></a>
### Receivers Parse, Senders Are Not Trusted

An owner reads a detail with `event_detail(event, schema)` and ignores the event when it does
not match the schema. Nobody casts `event as CustomEvent<Detail>`; the lint rules forbid it
([Coding Style](./coding-style#validate-external-input)).

<a name="queries-answer-through-a-callback"></a>
### Queries Answer Through a Callback

A request is fire-and-forget: the sender gets no acknowledgment. Two requests are different in
kind — **queries**: `tce.desktop.layout-request` (`request_layout`) and
`tce.desktop.composition-request` (`request_composition`). A query's detail carries a function
(`on_layout`, `on_composition`), and the owner that claims the query calls it **synchronously,
exactly once, inside its handler**. The layout query answers with a `TceDesktopLayout`:
the viewport rectangles of the desktop surface, the taskbar, every visible (not minimized)
window with its title, stacking order and an opaque stable `id`, and every icon with its label,
package identifier and `id`. When `request_layout` returns, the sender either has the answer or
knows that no desktop owns it, and behaves as if the desktop were empty:

```ts
const answer: { layout: TceDesktopLayout | null } = { layout: null };
request_layout(this.host, {
  on_layout: (raw) => { answer.layout = parse_or_null(desktop_layout_schema, raw, 'desktop layout'); },
});
```

The answer is external input to the sender, so it is parsed like any other. The rectangles are
public geometry (`getBoundingClientRect()`) and `data-` state of elements the desktop composed
itself; nothing in the answer comes from inside another element's shadow tree, and the answer
never hands out element references. A query describes; whoever wants to *change* something
still sends a request (`request_open` for the icon it sits on, for instance). This is how a
desktop effect — a mascot in the desktop's `effects` slot ([Declaration](./declaration#slots-as-attributes))
— knows where to walk without reaching into anything: it asks, every frame, and the desktop
describes.

The composition query answers with a `TceDesktopComposition` — for each switchable slot
(`decoration`, `effects`, `task-bar-icons`) the package names composed into it and the
attributes their elements are created with — and `request_configure` is the request that
changes it: switch a package on or off in a slot, set the attributes it is created with, or set
the boot attributes an icon opens its package with. The properties dialog (`tce-inspector`) is
built on nothing else: candidates come from the loader's bindings (`meta.slots`,
`meta.attributes` — [Declaration: Declared Attributes](./declaration#declared-attributes)), the
current state from the query, every change through the request.

<a name="3-library-functions-services"></a>
## 3. Library Functions (Services)

A package that needs a shared capability loads a *library* package through the loader and calls
its functions only through a contract assertion, never a bare `load()`:

```ts
const file_system = await TceRoot.package_loader().load(file_system_identifier);
const read = assert_contract<NotepadFileSystem['read']>(file_system, file_system_contracts.read, 'dll-tce-file-system');
const content = await read('/documents/todo.txt');
```

<a name="per-function-contracts-not-whole-library-versions"></a>
### Per-Function Contracts, Not Whole-Library Versions

A whole-library version is too coarse: two consumers use different subsets of a library's
functions, so pinning compatibility per library rejects good combinations and reports failures
as "library X is wrong" instead of "the one function you use changed". Compatibility is checked
per function:

```ts
interface TceFunctionContract {
  name: string;               // as published through `functions`/`load()`
  version: TcePackageVersion; // this function's own version, independent of the package's
  params?: readonly string[]; // documentation only
}
```

<a name="publishing-a-contract"></a>
### Publishing a Contract

A library lists its exposed names in `functions`, publishes one contract per function through
`contracts()`, and exports the contract constants and the function types so a consumer can
vendor them:

```ts
export const dll_tce_settings_contracts = {
  register_group: { name: 'register_group', version: { major: 1, minor: 0, patch: 0 } },
  list_groups:    { name: 'list_groups',    version: { major: 1, minor: 0, patch: 0 } },
} satisfies Record<string, TceFunctionContract>;

export class DllTceSettings extends TcePackage {
  public functions: Array<keyof this> = ['register_group', 'list_groups'];
  public contract_list: TceFunctionContract[] = Object.values(dll_tce_settings_contracts);
  public version(): TcePackageVersion { return { major: 0, minor: 1, patch: 0 }; }

  public register_group(group: TceSettingsGroup): void {
    group = parse_or_throw(settings_group_schema, group, 'settings group passed to register_group()');
    // ...
  }
}
```

Arguments to a library's public functions are external input: each one is parsed at the top of
the function.

<a name="vendoring-and-asserting-a-contract"></a>
### Vendoring and Asserting a Contract

A consumer **vendors** the contracts it was built against — copies the constants it uses into
`src/contracts/<library>.contract.ts` together with the library's identifier — the way a
lockfile pins a dependency, instead of reading them live off whatever is installed:

```ts
// src/contracts/dll-tce-file-system.contract.ts
import { TceFunctionContract } from '@tce-v3/common';
import type { DllTceFileSystem } from 'dll-tce-file-system';   // types only: never bundled

export const file_system_identifier = { namespace: 'tobidot', name: 'dll-tce-file-system' };

export const file_system_contracts = {
  exists: { name: 'exists', version: { major: 1, minor: 0, patch: 0 } },
  read:   { name: 'read',   version: { major: 1, minor: 0, patch: 0 } },
} satisfies Record<string, TceFunctionContract>;

export interface NotepadFileSystem {
  exists: DllTceFileSystem['exists'];
  read: DllTceFileSystem['read'];
}
```

At boot — inside `init()`, never lazily on first call ([Lifecycle](./lifecycle#boot-vs-mount)) —
the consumer asserts every vendored contract against the installed package. `assert_contract`
looks up the name in the installed package's `contracts()`, checks the installed version
against the vendored one with [caret-range matching](./package-model#version-matching-is-caret-range),
and returns the live function typed as requested — or throws a precise error:

```
dll-tce-file-system: 'read' contract mismatch — need >=1.2.0, installed package provides 1.1.3
```

A failure happens at boot with this message, never as `undefined is not a function` deep inside
application logic later.

<a name="staying-fully-typed"></a>
### Staying Fully Typed

The contract file is also the type source: the library's own types are imported as *types only*
(`import type`), so the library is never bundled into the consumer, and the generic parameter of
`assert_contract<Fn>` is supplied from that same file. The developer gets full type-checking on
the asserted function from the exact file that drives the runtime check — one artifact, nothing
to drift.

<a name="optional-libraries"></a>
### Optional Libraries

A library may be optional for a consumer (the notepad works on `localStorage` without the file
system). The consumer then checks `has_binding()` — after `DOMContentLoaded` when the document
is still loading, since a later `<script>` tag may still bind it — and degrades explicitly,
logging why. A library that is *required* and never binds fails `init()` through the loader's
bounded `with_binding` timeout, with the message naming the missing identifier; an element that
cannot work without it renders that failure in its own shadow DOM instead of staying blank.

<a name="two-consumers-two-required-versions"></a>
### Two Consumers, Two Required Versions

The loader memoizes one instance per resolved package, so two elements loading
`dll-tce-file-system` share one running copy. Per-function contracts make the outcomes precise:

- **Different functions**: A asserts `read` at 1.2.0, B asserts `write` at 2.0.0. Both succeed
  independently.
- **Same function, incompatible versions**: A vendored `read` at major 1, the installed package
  provides major 2. A's assertion fails naming exactly `read` and both versions; B is unaffected.

<a name="file-handlers"></a>
## File Handlers

`dll-tce-file-system` keeps a registry of which element opens which file types. An element
registers itself when its package boots, so "Open with" knows it before any window was opened:

```ts
file_system.register_file_handler({
  element: 'tce-notepad',
  namespace: 'tobidot',          // the handler's own publisher — required, never defaulted
  title: 'Notepad',
  extensions: ['.txt', '.md'],
  attribute: 'init-file-path',   // receives the absolute path
  width: 480,
  height: 360,
});
```

The opener (`tce-explorer`) looks up handlers with `get_file_handlers(extension)` and asks its
desktop with `request_open`, passing the path through the registered attribute. The opened
element only reacts to that attribute; it never receives a function call from the opener.

<a name="dos-and-donts"></a>
## Do's and Don'ts

- ✅ Do pick the channel that matches the relationship: slot for structure, request event for
  "do something to me", library for a shared service.
- ✅ Do dispatch protocol events through the `request_*` helpers and parse them with
  `event_detail`.
- ✅ Do call `event.stopPropagation()` once you have handled a request as its owner.
- ✅ Do answer a query synchronously, exactly once, inside the handler that claims it — and parse
  the answer on the receiving side.
- ✅ Do assert every library function you call with `assert_contract` at boot, against a
  vendored contract.
- ✅ Do import a library's types with `import type` only.
- ✅ Do register file handlers in `init()`, not `connectedCallback`.
- ✅ Do bump a function's own contract version only when that function changes.
- ❌ Don't reach into another element's shadow root or call a method on another package's
  instance directly.
- ❌ Don't look up where other elements are by querying their owner's DOM; ask with
  `request_layout`.
- ❌ Don't `load(name)` and cast the result; assert its contract.
- ❌ Don't read a contract live off the installed package; vendor your copy.
- ❌ Don't invent a new `CustomEvent` for window control; extend `window_action_schema`.
- ❌ Don't assume a request was honored — there is no acknowledgment.
