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
    let registry_item_versions: Array<{
        major: number,
        minor: number,
        patch: number,
        [key: string]: any,
    }> = package_groups['dll-tce-package-registry'];
    let latest_version_string: string = '0.0.0';
    let registry_item = null;
    for (const item of registry_item_versions) {
        const version = [
            item.major,
            item.minor,
            item.patch,
        ].join('.');
        if (version >= latest_version_string) {
            latest_version_string = version;
        }
        registry_item = item;
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
});
