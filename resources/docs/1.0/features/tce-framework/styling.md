# Styling Guide

- [Encapsulation](#encapsulation)
- [SCSS + BEM](#scss-bem)
- [Aesthetic](#aesthetic)
- [Color Palette](#color-palette)
- [Icons](#icons)
- [Typography & Motion](#typography-motion)
- [Loading Your Styles](#loading-your-styles)
- [Do's and Don'ts](#dos-and-donts)

How a TCE looks and how its CSS is structured. It merges the site-wide
[style guidelines](../../guidelines/style) with the rules specific to a Shadow-DOM element.

<a name="encapsulation"></a>
## Encapsulation

Every element attaches an open Shadow Root in its constructor
([Lifecycle](./lifecycle#shadow-dom-attach-and-build-once)). Styles written for the element
cannot leak out, and the host page's styles cannot leak in — other than inherited properties
(`font-family`, `color`) and CSS custom properties, which do pierce the boundary. No ad-hoc
class-name prefixing is needed; the boundary does that job.

An element fills the box it is given: `:host { display: block | flex; width: 100%; height: 100%; box-sizing: border-box; }`.
The window, the play page and the dev harness size that box; the element never sizes itself to
a fixed pixel size.

<a name="scss-bem"></a>
## SCSS + BEM

One `src/applet.scss` per element, imported as `applet.scss?inline`, with
[BEM](https://en.bem.info/) class names (`.block__element--modifier`), e.g. `.notepad__toolbar`,
`.applet__button`. Packages ship and version independently, so no SCSS partial is shared across
packages — the palette is restated as custom properties in each element.

<a name="aesthetic"></a>
## Aesthetic

Simple, low-stimulus, Windows95-esque:

- Dithered gradients in backgrounds.
- Clear, direct borders.
- Pseudo-3D bevels on borders and buttons: light top/left edge, dark bottom/right edge, swapped
  while pressed.
- Small, cheap animation (6–15 fps) only where motion carries meaning.

<a name="color-palette"></a>
## Color Palette

| Name      | Hex       | Use                                   |
|-----------|-----------|----------------------------------------|
| Turquoise | `#6cdad9` | Primary/interesting backgrounds        |
| Gray      | `#6c6c6c` | Borders, functional backgrounds        |
| Blue      | `#26306a` | Alternative text color / highlight     |
| Orange    | `#d48c17` | Alternative background / highlight     |
| Black     | `#111111` | Text color, clear-cut borders          |
| White     | `#ffffff` | Surfaces, light bevel edges            |

Declared once per element as custom properties on `:host` (`--color-turquoise`, …) and used
from there — never hardcoded repeatedly, and never expected from the host page, since an element
may be mounted on a bare play page with no page styling at all.

<a name="icons"></a>
## Icons

Every package has a 64×64 `icon.svg` in the flat palette above, with `shape-rendering: crispEdges`
and no gradients, drop shadows or embedded fonts. It is uploaded with the package and shown on
the desktop, in the package manager and in the registry.

<a name="typography-motion"></a>
## Typography & Motion

Monospaced fonts. Animation frame rates stay low (6–15 fps) by design — it is the aesthetic,
not a fallback.

<a name="loading-your-styles"></a>
## Loading Your Styles

Styles are attached through the one shared helper, `attach_styles` from `@tce-v3/common`,
in the `References` constructor:

```ts
export async function attach_styles(
  shadow: ShadowRoot,
  source: { css: string } | { url: string },
): Promise<void>;

attach_styles(this.shadow, { css: applet_scss });   // the ?inline SCSS every element uses
```

It uses `CSSStyleSheet` + `shadow.adoptedStyleSheets` (`replaceSync` for `{ css }`, `replace`
for `{ url }`), and it is the only place in the ecosystem that touches `adoptedStyleSheets` or
carries a `<style>`-tag fallback for engines without constructable stylesheets. The caller
states the mode explicitly; nothing sniffs content.

<a name="dos-and-donts"></a>
## Do's and Don'ts

- ✅ Do keep one SCSS file per element, scoped to its shadow root, with BEM names.
- ✅ Do fill the given box from `:host`; let the window size the element.
- ✅ Do restate the palette as custom properties in each element and reuse it.
- ✅ Do ship a 64×64 flat `icon.svg`.
- ✅ Do use `attach_styles` for every element.
- ❌ Don't hand-roll a `<style>` or `<link>` tag in an element.
- ❌ Don't fight the shadow boundary with `!important` aimed at a host page.
- ❌ Don't assume any host-page CSS (resets, frameworks, utility classes) is present.
- ❌ Don't point `attach_styles({ url })` at a URL without a version or hash in its path.
