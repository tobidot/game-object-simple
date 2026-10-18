// Styles are scoped to this element's shadow root — see "Styling Guide".
:host {
    --color-turquoise: #6cdad9;
    --color-gray: #6c6c6c;
    --color-blue: #26306a;
    --color-orange: #d48c17;
    --color-black: #111111;
    --color-white: #ffffff;

    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    box-sizing: border-box;
    font-family: monospace;
    color: var(--color-black);
    background: var(--color-white);
}

.applet__message {
    margin: 0;
    padding: 8px 12px;
    color: var(--color-blue);
    background: var(--color-turquoise);
    border: 2px solid var(--color-black);
    cursor: pointer;
}
