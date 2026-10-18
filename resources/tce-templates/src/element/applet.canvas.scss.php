// Styles are scoped to this element's shadow root — see "Styling Guide". BEM class names,
// the house palette as custom properties, no reliance on any host-page CSS.
:host {
    --color-turquoise: #6cdad9;
    --color-gray: #6c6c6c;
    --color-blue: #26306a;
    --color-orange: #d48c17;
    --color-black: #111111;
    --color-white: #ffffff;

    display: block;
    width: 100%;
    height: 100%;
    box-sizing: border-box;
    background: var(--color-gray);
    overflow: hidden;
}

.applet__canvas {
    display: block;
    width: 100%;
    height: 100%;
    image-rendering: pixelated;
}
