import {TceRoot} from '@game.object/tce-common';

(async () => {
    const $viewer = document.getElementById('viewer');
    if (!($viewer instanceof HTMLElement)) {
        throw new Error('Viewer element not found');
    }

    const elementName = $viewer.dataset.elementName;
    if (!elementName) {
        throw new Error('No element name provided');
    }
    const version = {
        major: parseInt($viewer.dataset.elementMajor ?? '0'),
        minor: parseInt($viewer.dataset.elementMinor ?? '0'),
        patch: parseInt($viewer.dataset.elementPatch ?? '0'),
    };

    // 1. Fetch the elements list to find the registry
    const response = await fetch('/api/public/tobidot-elements');
    if (!response.ok) {
        throw new Error(`Failed to fetch elements: ${response.statusText}`);
    }
    const json = await response.json();
    const package_groups = json.packages;

    // 2. Find and import the registry package
    const registry_item_versions: Array<{
        major: number,
        minor: number,
        patch: number,
        [key: string]: any,
    }> = package_groups['dll-tce-package-registry'];

    if (!registry_item_versions || !registry_item_versions.length) {
        throw new Error('Registry package "dll-tce-package-registry" not found');
    }

    // compare numerically: as strings "1.0.9" would wrongly be newer than "1.0.19"
    const is_newer = (a: typeof registry_item_versions[number], b: typeof registry_item_versions[number]) =>
        a.major !== b.major ? a.major > b.major : a.minor !== b.minor ? a.minor > b.minor : a.patch > b.patch;
    let registry_item: typeof registry_item_versions[number] | null = null;
    for (const item of registry_item_versions) {
        if (!registry_item || is_newer(item, registry_item)) {
            registry_item = item;
        }
    }

    if (!registry_item) {
        throw new Error('Registry package "dll-tce-package-registry" not found');
    }

    const registry_url = new URL(registry_item.content, registry_item.root).href;
    console.log('Importing Registry Package from: ', registry_url);
    await import (/* @vite-ignore */ registry_url);

    const package_loader = (window as any).tobidot.tce_package_loader;
    const registry_id = {namespace: 'tobidot', name: 'dll-tce-package-registry'};

    // Initialize Registry and register the provider - dependencies of the requested
    // element are resolved automatically by the package loader through this provider.
    const registry_pkg = await package_loader.load(registry_id) as any;
    await registry_pkg.init();
    await registry_pkg.register_provider('tobidot', '/api/public/tobidot-elements');

    // 3. Load only the requested element and its dependencies, no desktop shell required
    await TceRoot.fast_load(elementName, version);

    // 4. Mount the element fullscreen and borderless
    const $element = document.createElement(elementName);
    $element.dataset.autoInstall = 'true';
    $element.style.display = 'block';
    $element.style.width = '100%';
    $element.style.height = '100%';
    $element.style.border = 'none';
    $viewer.append($element);

    console.log('Element loaded and mounted fullscreen: ', elementName, version);
})().then(() => {
    console.log('Element viewer ready.');
}).catch((error) => {
    console.error('Error initializing element viewer:', error);
    const $viewer = document.getElementById('viewer');
    if ($viewer) {
        $viewer.innerHTML = '';
        const $error = document.createElement('div');
        $error.style.cssText = 'margin:auto;padding:16px;max-width:80%;background:#7f1d1d;color:#fee2e2;font-family:sans-serif;border:1px solid #ef4444;';
        $error.textContent = 'This element could not be loaded: ' + (error instanceof Error ? error.message : String(error));
        $viewer.append($error);
    }
});
