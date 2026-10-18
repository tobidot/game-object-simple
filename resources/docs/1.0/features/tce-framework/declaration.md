# Declarative Structure

- [Principle](#principle)
- [Usage](#usage)
    - [Declaration](#declaration)
    - [Slots as Attributes](#slots-as-attributes)
    - [Attributes](#attributes)
    - [Declared Attributes](#declared-attributes)
- [Implementation](#implementation)
    - [Parent/Child Contract](#parentchild-contract)
    - [Imperative Helpers Wrap Slots, They Don't Replace Them](#imperative-helpers-wrap-slots-they-dont-replace-them)
- [Do's and Don'ts](#dos-and-donts)

<a name="principle"></a>
## Principle

A composition of elements reads like a sentence: nesting plain HTML custom elements is enough to
describe *what* a widget does, while *how* it does it stays private to each element.

<a name="usage"></a>
## Usage

<a name="declaration"></a>
### Declaration

Like any HTML element, a TCE is nested to be meaningful. A window showing an application is one
element slotted into another:

```html
<tce-window data-title="Notepad">
    <tce-notepad slot="display" init-file-path="/documents/todo.txt"></tce-notepad>
</tce-window>
```

Each element is agnostic of the others' internals: `tce-window` does not know what
`tce-notepad` does once it is slotted, and `tce-notepad` does not know how its window draws
itself. They agree only on the slot name and on the request events of
[Interfacing](./interfacing#2-bubbling-request-events).

<a name="slots-as-attributes"></a>
### Slots as Attributes

A slotted child is a more complex attribute of its parent, keyed by its `slot` name. The
desktop's system tray is such a slot:

```html
<tce-desktop>
    <tce-clock slot="task-bar-icons"></tce-clock>
</tce-desktop>
```

Effects are slots too. A window has a `decoration` slot — a 48px strip resting on its top
border, full window width, with pointer events passing through — and the desktop has an
`effects` slot, an overlay above every window and the taskbar that also lets pointer events
through (an effect opts its own parts back in):

```html
<tce-window data-title="Notepad">
    <tce-flames slot="decoration"></tce-flames>
    <tce-notepad slot="display"></tce-notepad>
</tce-window>

<tce-desktop data-window-decoration="tce-flames">
    <tce-klabautermann slot="effects"></tce-klabautermann>
</tce-desktop>
```

The desktop's `data-window-decoration` names packages it composes into every window's
`decoration` slot itself, exactly as the first snippet does by hand; `data-effects` names
packages for its own `effects` slot and `data-tray` for `task-bar-icons`. All three are live,
comma-separated lists, and the same three slots are offered as settings and in the properties
dialog (`tce-inspector`, "Desktop Properties…" on the desktop menu), so a user switches each
package on or off at runtime and keeps the choice across reloads — together with the attributes
each composed element is created with. An effect learns where windows and icons are through
the layout query ([Interfacing](./interfacing#queries-answer-through-a-callback)), never by
looking.

Every composition point of an element is a named slot — `display` and `decoration` on
`tce-window`, `task-bar-icons` and `effects` on `tce-desktop` — documented in the element's class
comment.

<a name="attributes"></a>
### Attributes

Elements take attributes as input. Rules:

- An attribute that is only read once, when the element is first connected, is prefixed
  `init-` (`init-file-path`, `init-count`), so other elements know not to rely on live updates
  to it. It is read in `Logic.start()` behind a once-flag, never in the constructor.
- Any other attribute is live: the element lists it in `observedAttributes` and reacts in
  `attributeChangedCallback`.
- Custom data that is not a standard HTML/ARIA attribute uses the `data-` prefix, matching the
  native `dataset` API (`data-title`, `data-mode`, `data-docs-base`).
- Attribute values are external input: a value that is not a plain string is read with
  `read_attribute(element, name, schema, fallback)` ([Coding Style](./coding-style#validate-external-input)).

<a name="declared-attributes"></a>
### Declared Attributes

An element declares the attributes it accepts in its entry script's `bind_self()` meta, together
with the desktop slots it is made for, so a desktop or a properties dialog can offer them
without loading the element ([Package Model](./package-model#declared-slots-and-attributes)):

```ts
TceRoot.bind_self(package_identifier, tce_flames_package, {
    kind: 'element',
    description: '…',
    slots: ['decoration'],
    attributes: [
        { name: 'data-intensity', label: 'Intensity', type: 'number', default: '1', min: 0, max: 1, step: 0.1 },
        { name: 'data-color', label: 'Colour', type: 'select', default: 'fire', options: ['fire', 'gas', 'ember', 'ghost'] },
    ],
});
```

`name` is the attribute as written in HTML; `type` is one of `text`, `number`, `color`,
`boolean` (values `true`/`false`), `select` (with `options`); `default` is what the element
does without the attribute. Values are always strings. The element still reads every attribute
through a schema — the declaration is for the dialog, the schema is the truth. Declare the list
once (a `*_attributes` constant next to the element's `Settings`) and pass it to `bind_self`.

The desktop keeps the attributes chosen for an icon ("Properties…" on the icon) and applies
them when the icon is activated, exactly as a shortcut's `attributes` would be; the attributes
chosen for a composed package are set on its element whenever the desktop creates it.

<a name="implementation"></a>
## Implementation

<a name="parentchild-contract"></a>
### Parent/Child Contract

An element must not mutate its own attributes to change how its parent treats it. A child that
needs its owner to resize, retitle, maximize or close it asks through a bubbling, composed
request event, and the owner decides whether to honor it:

```ts
import { request_window_action } from '@tce-v3/desktop-protocol';

request_window_action(this.host, { action: 'resize', width: 640, height: 480 });
```

The full set of request events is in [Interfacing](./interfacing#2-bubbling-request-events).
The child never gets an acknowledgment and never assumes the request succeeded.

Reflecting an element's *own* state into a `data-` attribute (a window marking itself
`data-minimized` so it can be styled and inspected) is fine; the owner may read it, but it is
never the channel through which the child asks for a change.

<a name="imperative-helpers-wrap-slots-they-dont-replace-them"></a>
### Imperative Helpers Wrap Slots, They Don't Replace Them

Convenience methods (the desktop's `add_icon`, the desktop opening a window for a
`tce.desktop.open-request`, the desktop slotting the decoration and effects its attributes or
settings name) build the same slotted markup a page could have written by hand.
An element exposes every composition point as a named slot, even if only one caller uses it;
no element offers an imperative-only way to compose something expressible as a slot.

<a name="dos-and-donts"></a>
## Do's and Don'ts

- ✅ Do give every composable child a named `slot`.
- ✅ Do prefix read-once attributes with `init-` and read them once, behind a flag.
- ✅ Do observe every non-`init-` attribute and react live.
- ✅ Do use `data-*` for anything that is not a real DOM/ARIA attribute.
- ✅ Do ask the parent through a request event when the child needs the parent to change.
- ❌ Don't have an element write to its own attributes to signal its parent — that is a race the
  parent may or may not observe.
- ❌ Don't invent a new event name for something the desktop protocol already covers.
- ❌ Don't offer an imperative-only way to compose something that could be a slot.
