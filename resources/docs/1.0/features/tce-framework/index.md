# Tobidot Custom Elements (TCE) Framework

- [What Is a TCE?](#what-is-a-tce)
- [The Three Kinds of Package](#the-three-kinds-of-package)
- [Specification Documents](#specification-documents)
- [How the Pieces Fit](#how-the-pieces-fit)
- [Do's and Don'ts](#dos-and-donts)

This is the specification of TCE version 3. It is normative: it says how a package must be
written, built, published and embedded. Where code and this specification disagree, one of them
is wrong and gets fixed — the specification never describes a "current state", only the rule.

<a name="what-is-a-tce"></a>
## What Is a TCE?

A Tobidot Custom Element is a self-contained Web Component (TypeScript, built with Vite into an
ES module and an IIFE) that is published to a registry as a versioned package and can then be

- embedded on any website with one `<script src>` tag and one HTML tag, with no build step and
  no JavaScript on the embedding side ([Integration](./integration)),
- opened as a window on the TCE desktop, which is this site's homepage, or
- played fullscreen on its own page.

Packages never share source at runtime. They talk through three narrow channels — slots,
bubbling request events, and library functions guarded by per-function contracts
([Interfacing](./interfacing)) — and every input that crosses a package boundary is validated
([Coding Style](./coding-style#validate-external-input)).

<a name="the-three-kinds-of-package"></a>
## The Three Kinds of Package

| Kind      | Has a DOM? | Loaded at runtime? | Examples                                      |
|-----------|------------|--------------------|-----------------------------------------------|
| `element` | yes        | yes                | `tce-notepad`, `tce-desktop`, `tce-window`    |
| `library` | no         | yes                | `dll-tce-file-system`, `dll-tce-settings`     |
| `sdk`     | no         | no — bundled at build time | `common` (`@tce-v3/common`), `desktop-protocol` (`@tce-v3/desktop-protocol`) |

Elements and libraries are the same thing at the package level ([Package Model](./package-model)):
both bind themselves into the page's loader, both have a version and `init()`, and a library
additionally exposes functions. An SDK package is the TypeScript every package compiles against;
it is published to the registry only so the [generator](./generator) can vendor it into new
projects, and it never appears in the public registry response.

<a name="specification-documents"></a>
## Specification Documents

- [Naming & Identifiers](./naming) — `{ namespace, name }`, tag names, class names, prefixes.
- [Coding Style](./coding-style) — snake_case/StudlyCase, `$`-prefixed DOM handles, explicit
  accessibility, the five-way class split, validating external input, the lint rules.
- [Styling Guide](./styling) — Shadow DOM, SCSS + BEM, the Windows95-esque palette, `attach_styles`.
- [Declarative Structure](./declaration) — composition by nesting, slots, `init-` and `data-`
  attributes, the parent/child contract.
- [Element Lifecycle & Rendering](./lifecycle) — constructor, connect/disconnect, the entry
  point that binds the package, boot vs. mount.
- [Package Model & Versioning](./package-model) — the package interface, the loader, binding vs.
  loading, dependencies, caret-range matching.
- [Assets & Build](./assets-and-build) — `public/` assets and `resolve_asset_url`, cheap `init()`,
  code splitting, the dual ES/IIFE build.
- [Interfacing Between Elements](./interfacing) — slots, the desktop protocol events, library
  functions and contracts, file handlers.
- [Integration Into Websites](./integration) — the zero-script embed, the play pages, the
  desktop, installing packages at runtime.
- [The `tobidot-project.json` Manifest](./manifest) — the project metadata file.
- [TCE Project Generator](./generator) — Web UI, CLI and REST API producing a complete project.
- [Publishing & Discovery](./upload-and-registry) — the data model, the three ways to publish,
  the registry response, the routes.

<a name="how-the-pieces-fit"></a>
## How the Pieces Fit

1. A project is generated ([Generator](./generator)) or laid out by hand to the same shape, with
   the SDK as a build-time dependency.
2. Its entry script binds the package into the page's single loader and registers its tag
   ([Lifecycle: Bootstrapping](./lifecycle#bootstrapping-the-entry-point-binds-the-package)).
3. `npm run pack` builds `dist/index.js` + `dist/index.global.js` and zips `dist/`
   ([Assets & Build](./assets-and-build#dual-build-output-es-and-iife)).
4. The zip is published to the registry, dependencies first
   ([Publishing](./upload-and-registry)).
5. Any page includes it through `/tce-elements/{namespace}/{name}`; the desktop's package manager
   installs it the same way at runtime ([Integration](./integration)).

<a name="dos-and-donts"></a>
## Do's and Don'ts

- ✅ Do read [Coding Style](./coding-style), [Lifecycle](./lifecycle) and
  [Interfacing](./interfacing) before writing a package.
- ✅ Do treat these pages as the rule: when code disagrees, fix the code or change the rule here —
  in the same change.
- ❌ Don't write "currently", "for now" or version history into these pages; a rule is a rule.
- ❌ Don't assume a publisher namespace anywhere — `namespace` is data
  ([Naming](./naming#namespace)).
