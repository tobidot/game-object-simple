// Styles are scoped to this element's shadow root — see "Styling Guide". BEM class names,
// the house palette as custom properties, no reliance on any host-page CSS.
:host {
    --color-turquoise: #6cdad9;
    --color-gray: #6c6c6c;
    --color-blue: #26306a;
    --color-orange: #d48c17;
    --color-black: #111111;
    --color-white: #ffffff;

    display: flex;
    flex-direction: column;
    width: 100%;
    height: 100%;
    box-sizing: border-box;
    font-family: monospace;
    color: var(--color-black);
    background: var(--color-white);
}

.applet {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 12px;
}

.applet__title {
    margin: 0;
    font-size: 14px;
    color: var(--color-blue);
}

.applet__count {
    font-size: 32px;
    font-weight: bold;
}

.applet__button {
    padding: 4px 12px;
    font-family: inherit;
    font-size: 12px;
    color: var(--color-black);
    background: var(--color-turquoise);
    border-top: 2px solid var(--color-white);
    border-left: 2px solid var(--color-white);
    border-right: 2px solid var(--color-gray);
    border-bottom: 2px solid var(--color-gray);
    cursor: pointer;

    &:active {
        border-top-color: var(--color-gray);
        border-left-color: var(--color-gray);
        border-right-color: var(--color-white);
        border-bottom-color: var(--color-white);
    }
}
