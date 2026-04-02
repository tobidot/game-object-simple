(async () => {
    // 1. Fetch the elements list to find the registry
    const response = await fetch('/api/public/tobidot-elements');
    if (!response.ok) {
        throw new Error(`Failed to fetch elements: ${response.statusText}`);
    }
    const json = await response.json();
    const package_groups = json.packages;

    // 2. Find and import the registry package
    let registry_item: any = null;
    for (const name in package_groups) {
        registry_item = package_groups[name].find((p: any) => p.name === 'dll-tce-package-registry');
        if (registry_item) break;
    }

    if (!registry_item) {
        throw new Error('Registry package "dll-tce-package-registry" not found');
    }

    const registry_url = new URL(registry_item.content, registry_item.root).href;
    console.log('Importing Registry Package from: ', registry_url);
    await import (/* @vite-ignore */ registry_url);

    const package_loader = (window as any).tobidot.tce_package_loader;
    const registry_id = { namespace: 'tobidot', name: 'dll-tce-package-registry' };

    // Initialize Registry
    const registry_pkg = await package_loader.load(registry_id) as any;
    await registry_pkg.init();
    await registry_pkg.register_provider('tobidot', '/api/public/tobidot-elements');

    // 3. Load the desktop package (now registered via the provider)
    const desktop_id = { namespace: 'tobidot', name: 'tce-desktop' };
    const desktop_pkg = await package_loader.load(desktop_id);
    await desktop_pkg.init();

    // Create and add the desktop element to the document
    const $element = document.createElement(desktop_id.name);
    $element.dataset.autoInstall = 'true';
    $element.innerHTML = `
        <div slot="task-bar-icons">
            <tce-clock style="display: flex; width: auto; height: 100%; aspect-ratio: 1 / 1; font-size: 6px;"></tce-clock>
        </div>
    `;

    const $desktop = document.getElementById('desktop');
    if (!($desktop instanceof HTMLElement)) {
        throw new Error('App element not found');
    }
    $desktop.append($element);

    console.log('Desktop package loaded and initialized: ', desktop_id);

    // 4. Load other necessary components
    const bootloader_id = { namespace: 'tobidot', name: 'tce-bootloader' };
    const bootloader_pkg = await package_loader.load(bootloader_id);
    await bootloader_pkg.init();
})().then(() => {
    console.log('Application ready.');
}).catch((error) => {
    console.error('Error initializing application:', error);
});
