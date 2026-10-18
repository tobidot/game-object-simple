# Naming & Identifiers

- [Identifiers](#identifiers)
- [Namespace](#namespace)
- [Package Names](#package-names)
- [Class Names](#class-names)
- [SDK Package Names](#sdk-package-names)
- [Identifier Validation](#identifier-validation)
- [Do's and Don'ts](#dos-and-donts)

<a name="identifiers"></a>
## Identifiers

Every package is identified by `{ namespace, name }` (`TcePackageIdentifier` in
`@tce-v3/common`). Both parts are required, real data; the loader serializes the pair as
`namespace/name`, and so does every registry endpoint, embed URL and manifest.

<a name="namespace"></a>
## Namespace

The namespace names the publisher, the way an npm scope does: two publishers may use the same
`name` without colliding. It is open — any string matching the
[identifier pattern](#identifier-validation) is a namespace — and it is never a framework-level
constant. No runtime code, bootstrap, data layer or generator may contain the literal namespace
of a particular publisher; each layer carries the namespace it was given:

- A package states its own identifier in its entry script and manifest.
- A consumer names the full identifier of every dependency it declares or loads, in its vendored
  contract file ([Interfacing](./interfacing#vendoring-and-asserting-a-contract)).
- A deployment of this site has exactly one place for its own default namespace,
  `config/tce.php`, used only to expand the short-form routes (`/tce-elements/{name}`,
  `/tce/{name}`) and as the generator's default. `tobidot` is this site's value, not the
  framework's.

<a name="package-names"></a>
## Package Names

A package `name` is used directly as its HTML tag, so it must be a valid custom-element name:
lower-case letters, digits and hyphens, with at least one hyphen. The exact pattern, enforced by
the generator and the upload validation:

```
^[a-z][a-z0-9]*(-[a-z0-9]+)+$
```

Conventions on top of that rule:

- Elements are prefixed `tce-`: `tce-clock`, `tce-notepad`, `tce-explorer`.
- Libraries are prefixed `dll-tce-` (they never reach `customElements.define`, but the shape
  keeps a package list readable): `dll-tce-file-system`, `dll-tce-settings`.

A name is never rewritten silently: an invalid name is rejected, not prefixed or slugged.

<a name="class-names"></a>
## Class Names

The class behind a package is the StudlyCase form of its full name — the prefix included, so the
class says what kind of thing it is:

| Name                   | Class              |
|------------------------|--------------------|
| `tce-shape-me`         | `TceShapeMe`       |
| `tce-desktop`          | `TceDesktop`       |
| `dll-tce-file-system`  | `DllTceFileSystem` |

The collaborators of a split element ([Coding Style](./coding-style#splitting-a-non-trivial-class))
append their role: `TceDesktopSettings`, `TceDesktopReferences`, `TceDesktopProperties`,
`TceDesktopRenderer`, `TceDesktopLogic`. Renaming a package means renaming its class, its files
and its entry script — nothing does that automatically.

<a name="sdk-package-names"></a>
## SDK Package Names

The SDK packages are published to the registry under their plain directory names (`common`,
`desktop-protocol`) and consumed from npm under the `@tce-v3` scope (`@tce-v3/common`,
`@tce-v3/desktop-protocol`). `config/tce.php` maps the one to the other for the generator.

<a name="identifier-validation"></a>
## Identifier Validation

Both `name` and `namespace` must match

```
^[0-9a-zA-Z\-_]+$
```

This is checked by `package_identifier_schema` on every `bind()`, by
`TcePackageLoader.serialize_identifier`, and by the upload validation on the server. It is
deliberately looser than the tag-name rule above: a library name is never passed to
`customElements.define`, and a namespace is not a tag at all. An element whose name violates the
tag-name rule fails at registration time, which is why the stricter rule is enforced where names
are created — in the generator and the upload.

<a name="dos-and-donts"></a>
## Do's and Don'ts

- ✅ Do name every package `tce-*` (element) or `dll-tce-*` (library), lower-case with hyphens.
- ✅ Do derive the class name from the full package name, prefix included.
- ✅ Do carry `namespace` through as data at every layer; this site's own value lives in
  `config/tce.php` only.
- ❌ Don't hardcode a publisher's namespace in a package, the SDK, a bootstrap or a template.
- ❌ Don't register a tag without a hyphen — the failure surfaces at `customElements.define()`,
  far from the mistake.
- ❌ Don't silently rewrite an invalid name; reject it.
