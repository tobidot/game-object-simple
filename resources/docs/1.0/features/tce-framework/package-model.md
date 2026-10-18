# Package Model & Versioning

- [The Package Interface](#the-package-interface)
- [Element Packages](#element-packages)
- [Declared Slots and Attributes](#declared-slots-and-attributes)
- [Identifiers](#identifiers)
- [Versioning](#versioning)
- [One Loader Per Page](#one-loader-per-page)
- [Binding vs. Loading](#binding-vs-loading)
- [Dependencies](#dependencies)
- [Version Matching Is Caret-Range](#version-matching-is-caret-range)
- [Namespace Is Data, Not a Constant](#namespace-is-data-not-a-constant)
- [Do's and Don'ts](#dos-and-donts)

<a name="the-package-interface"></a>
## The Package Interface

Every runtime package — element or library — implements `TcePackageInterface` from
`@tce-v3/common`:

```ts
interface TcePackageInterface {
  functions: Array<string | number | symbol>;   // names reachable through load()
  version(): TcePackageVersion;
  init(): Promise<TcePackageInterface>;         // one-time boot, must stay cheap
  load(name: string): ((...args: unknown[]) => unknown) | null;
  contracts(): TceFunctionContract[];           // per-function semantic versions
}
```

`TcePackage` is the base class: `load(name)` returns the bound method only if `name` is listed
in `functions`, and `null` otherwise — it never throws. `contracts()` returns `contract_list`,
one entry per exposed function ([Interfacing: Per-Function Contracts](./interfacing#per-function-contracts-not-whole-library-versions)).
A library is one class extending `TcePackage`; it has no DOM.

<a name="element-packages"></a>
## Element Packages

An element's package object is a `TceElementPackage(tag_name, element_class, version)`: its
`init()` registers the custom element tag, guarded with `customElements.get()`, and nothing
else. An element whose boot has real work (asserting library contracts, registering a file
handler) subclasses it and overrides `init()`, memoizing the boot promise so repeated calls are
harmless:

```ts
class TceNotepadPackage extends TceElementPackage {
  private boot: Promise<TcePackageInterface> | null = null;

  public async init(): Promise<TcePackageInterface> {
    this.boot ??= NotepadFileSystemConnection.connect().then((file_system) => {
      file_system?.register_file_handler(new TceNotepadSettings().file_handler);
      return super.init();
    });
    return this.boot;
  }
}
```

<a name="declared-slots-and-attributes"></a>
## Declared Slots and Attributes

`TcePackageMeta` carries two optional declarations an element package makes about itself in
`bind_self()`: `slots`, the desktop slots it is made for (`decoration`, `effects`,
`task-bar-icons`), and `attributes`, the attributes it accepts
([Declaration: Declared Attributes](./declaration#declared-attributes)). They are metadata like
`width` and `icon`: read with `with_binding`/`resolve_binding`, never by loading the package. A
desktop lists every bound element whose `slots` name one of its switchable slots as a component
the user can switch on; a properties dialog builds its form from `attributes`. An ordinary
application declares neither.

<a name="identifiers"></a>
## Identifiers

`{ namespace, name }` — see [Naming & Identifiers](./naming). The loader keys its bindings by
`namespace/name`; both parts must pass the identifier pattern or `bind()` throws.

<a name="versioning"></a>
## Versioning

A `TcePackageVersion` is `{ major, minor, patch }` — non-negative integers, never a string.
`parse_version('1.2.3')` and `serialize_version(version)` convert at the boundary (manifests,
registry responses). A package's version is stated once, in its entry script, and published to
the registry with every upload ([Publishing: Versioning on Upload](./upload-and-registry#versioning-on-upload)).

<a name="one-loader-per-page"></a>
## One Loader Per Page

`TceRoot.package_loader()` returns the page's single `TcePackageLoader`, stored on
`window.tobidot.tce_package_loader` and created on first use. Every package script, however it
got onto the page, binds into this one loader — which is what lets several embedded elements
share one running copy of a library.

<a name="binding-vs-loading"></a>
## Binding vs. Loading

- **`bind(identifier, version, fetch, meta)`** announces that a version of a package *can* be
  resolved, with a `fetch()` thunk producing its instance. It validates every argument (they come
  from another package's script), fires `TcePackageLoaderPackageBoundEvent`, and returns the
  existing descriptor — with `meta` merged in — if that exact version is already bound.
  `TceRoot.bind_self(identifier, instance, meta)` is the only way a package binds itself
  ([Lifecycle: Bootstrapping](./lifecycle#bootstrapping-the-entry-point-binds-the-package)).
- **`has_binding(identifier, constraints?)`** answers synchronously whether a matching version
  is bound.
- **`with_binding(identifier, constraints?, timeout_ms?)`** resolves the best matching
  descriptor, waiting for a `bind()` from a script that is still loading. It rejects after the
  timeout (15 s by default) with a message naming the identifier and asking for its `<script>`
  tag — it never hangs forever.
- **`load(identifier, constraints?)`** resolves the best bound version, loads its declared
  dependencies first, runs `fetch()` + `init()` exactly once (memoized on the descriptor), fires
  `TcePackageLoaderPackageLoadedEvent`, and returns the instance.
- **`TceRoot.fast_load(identifier, version?)`** is `with_binding` + `load` + `init`: what an
  activation (opening a window) calls.

Use `with_binding`/`has_binding` when you only need metadata (`meta.icon`, `meta.width`, …) —
they never fetch or initialize anything ([Lifecycle: Icons](./lifecycle#icons-must-not-eagerly-load-their-element)).

<a name="dependencies"></a>
## Dependencies

A package's `meta.dependencies` is a list of `{ identifier, version? }` requirements, declared in
its entry script and in its manifest, and published with it. `load()` loads every dependency,
recursively, before fetching the package. A package that *calls* a library additionally asserts
each function it uses at boot ([Interfacing](./interfacing#vendoring-and-asserting-a-contract));
declaring the dependency is what makes the registry and the embed pages include the library
before the package.

<a name="version-matching-is-caret-range"></a>
## Version Matching Is Caret-Range

A constraint is a partial version `{ major?, minor?, patch? }`. `major`, when given, must match
exactly; `minor` and `patch`, when given, are floors; an omitted field is unconstrained. One
implementation, `is_version_matching_constraints`, serves both whole-package resolution and
per-function contract checks:

```ts
is_version_matching_constraints({ major: 1, minor: 3, patch: 0 }, { major: 1, minor: 2 }) // true
is_version_matching_constraints({ major: 2, minor: 0, patch: 0 }, { major: 1 })           // false
is_version_matching_constraints({ major: 1, minor: 2, patch: 1 }, { major: 1, minor: 2, patch: 5 }) // false
```

Among matching versions the loader picks the newest. This is the only version-matching behavior
the framework implements.

<a name="namespace-is-data-not-a-constant"></a>
## Namespace Is Data, Not a Constant

Per [Naming: Namespace](./naming#namespace), every layer carries a real namespace value:

- **The SDK** (`TceRoot`, `TcePackageLoader`, `resolve_asset_url`) takes the namespace as part of
  the identifier it is given and never constructs one.
- **A package** states its own namespace in its entry script and manifest, and the full
  identifier of each dependency in its contract files.
- **The registry** stores `namespace` as a column, groups by `namespace/name`, and keys version
  resolution on the pair.
- **The site** keeps its own default in `config/tce.php`, read only by the short-form routes, the
  uploader's default and the generator's default.

<a name="dos-and-donts"></a>
## Do's and Don'ts

- ✅ Do declare every runtime dependency in `meta.dependencies` *and* assert the functions you
  call at boot.
- ✅ Do use `with_binding`/`has_binding` for metadata and `fast_load` for activation.
- ✅ Do pass only the version fields you want to constrain; `{}` matches anything.
- ✅ Do memoize a custom `init()` so it can be called more than once.
- ❌ Don't expect an exact-match constraint from `{ major, minor, patch }` — `minor`/`patch`
  are floors.
- ❌ Don't `bind()` the same identifier+version twice expecting a fresh `fetch()` — the first
  binding wins and only `meta` is merged.
- ❌ Don't create a second loader or import the SDK's loader class to instantiate one — there is
  one per page, from `TceRoot.package_loader()`.
