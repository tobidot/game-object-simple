import {TceRoot} from '@game.object/tce-common';

(async () => {
    // 1. Fetch the elements list to find the registry
    const response = await fetch('/api/public/tobidot-elements');
    if (!response.ok) {
        throw new Error(`Failed to fetch elements: ${response.statusText}`);
    }
    const json = await response.json();
    const package_groups = json.packages;

    // 2. Find and import the registry package
    const raw_registry_versions = package_groups?.['dll-tce-package-registry'];
    let registry_item_versions: Array<{
        major: number,
        minor: number,
        patch: number,
        [key: string]: any,
    }> = Array.isArray(raw_registry_versions)
        ? raw_registry_versions
        : (raw_registry_versions ? Object.values(raw_registry_versions) : []);
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

    // Initialize Registry
    const registry_pkg = await package_loader.load(registry_id) as any;
    await registry_pkg.init();
    await registry_pkg.register_provider('tobidot', '/api/public/tobidot-elements');

    // 3. Load the desktop library to interact with the desktop
    const dll_desktop_id = {namespace: 'tobidot', name: 'dll-tce-desktop'};
    const dll_desktop_pkg = await package_loader.load(dll_desktop_id);
    await dll_desktop_pkg.init();
    const dll_desktop_add_icon = dll_desktop_pkg.load('create_icon') as ($application: HTMLElement, options: any) => any;
    const dll_desktop_add_window = dll_desktop_pkg.load('create_window') as ($application: HTMLElement, options: any) => any;

    // 4. Load the desktop package (now registered via the provider)
    const desktop_id = {namespace: 'tobidot', name: 'tce-desktop'};
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

    // 3.1 Load/Install and add icons/windows from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    let windowX = 100;
    let windowY = 100;

    for (const [key, value] of urlParams.entries()) {
        const packageName = key.replace(/[^a-zA-Z0-9-]/g, '');
        if (!packageName || packageName === 'tce-desktop' || packageName === 'tce-bootloader') {
            continue;
        }

        try {
            console.log(`Loading package ${packageName} from URL parameter...`);
            const pkg_id = {namespace: 'tobidot', name: packageName};
            const pkg = await package_loader.load(pkg_id);
            if (pkg && typeof pkg.init === 'function') {
                await pkg.init();
            }

            const windowMatch = value.match(/^window(,(\d+),(\d+))?$/);
            const isIcon = value === 'icon' || windowMatch;
            const width = windowMatch ? parseInt(windowMatch[2]) || 400 : 400;
            const height = windowMatch ? parseInt(windowMatch[3]) || 300 : 300;

            if (isIcon) {
                const $icon = await TceRoot.create_icon(packageName, undefined, {
                    width,
                    height,
                });
            }
            if (windowMatch) {
                const $window = await TceRoot.create_window(packageName, undefined, {
                    area: {
                        left: 0,
                        top: 0,
                        width,
                        height,
                    },
                });
            }
        } catch (error) {
            console.error(`Error loading package ${packageName}:`, error);
        }
    }


    console.log('Looking for autostart');
    package_loader.load(
        {namespace: 'tobidot', name: 'dll-tce-file-system'},
    ).then(async (file_system: { load: (...args: Array<any>) => any }) => {
        const fs_exists = file_system.load('exists') as (file: string) => Promise<boolean>;
        const fs_read = file_system.load('read') as (file: string) => Promise<string>;
        const auto_start_exists = await fs_exists('/autostart.js');
        if (!auto_start_exists) {
            console.log('No autostart script found');
            return;
        }
        const content = await fs_read('/autostart.js');
        console.log('Executing autostart script...');
        const autostart = new Function(content);
        autostart();
    }).catch((error: any) => {
        console.log('Error during autostart', error);
    });

    console.log('Desktop package loaded and initialized: ', desktop_id);

    // 4. Load other necessary components
    const bootloader_id = {namespace: 'tobidot', name: 'tce-bootloader'};
    const bootloader_pkg = await package_loader.load(bootloader_id);
    await bootloader_pkg.init();
})().then(() => {
    console.log('Application ready.');
}).catch((error) => {
    console.error('Error initializing application:', error);
    const $desktop = document.getElementById('desktop');
    if ($desktop) {
        $desktop.innerHTML = '';
        const $errorContainer = document.createElement('div');
        $errorContainer.style.cssText = 'margin: auto; padding: 2rem; max-width: 560px; background: rgba(30, 41, 59, 0.95); color: #f1f5f9; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; border: 1px solid #475569; border-radius: 8px; text-align: center; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);';

        const $title = document.createElement('h2');
        $title.style.cssText = 'margin: 0 0 0.75rem 0; color: #f87171; font-size: 1.25rem; font-weight: 600;';
        $title.textContent = 'Desktop Initialization Error';

        const $msg = document.createElement('p');
        $msg.style.cssText = 'margin: 0.5rem 0; font-size: 0.95rem; line-height: 1.5; color: #cbd5e1;';
        $msg.textContent = error instanceof Error ? error.message : String(error);

        const $help = document.createElement('p');
        $help.style.cssText = 'margin-top: 1rem; margin-bottom: 0; font-size: 0.85rem; color: #94a3b8; line-height: 1.4;';
        $help.textContent = 'Please make sure that the required TCE packages (such as "dll-tce-package-registry" and "tce-desktop") are uploaded and available in the registry.';

        $errorContainer.appendChild($title);
        $errorContainer.appendChild($msg);
        $errorContainer.appendChild($help);
        $desktop.appendChild($errorContainer);
    }
});
