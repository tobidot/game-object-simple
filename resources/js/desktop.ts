import {TceRoot} from '@game.object/common';
import {TcePackageRegistryLibInterface} from '@game.object/dll-tce-package-registry';

(async () => {
    const package_loader = TceRoot.package_loader();

    await import ('@game.object/tce-desktop');
    const desktop_id = { namespace: 'tobidot', name: 'tce-desktop' };
    await package_loader.with_binding(desktop_id);
    const desktop_pkg = await package_loader.load(desktop_id);
    await desktop_pkg.init();

    // add to doc
    const $element = document.createElement(desktop_id.name);
    $element.dataset.autoInstall = 'true';

    // Add some children to the desktop as seen in original index.html
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
    console.log('Package loaded and initialized successfully: ', desktop_id);
    await boot($desktop);
    return $element;
})().then(() => {
    console.log('Packages imported, application ready.');
}).catch((error) => {
    console.error('Error initializing application:', error);
});


async function boot(
    $desktop: HTMLElement,
) {
    const package_loader = TceRoot.package_loader();
    const registry_id = { namespace: 'tobidot', name: 'dll-tce-package-registry' };
    const bootloader_id = { namespace: 'tobidot', name: 'tce-bootloader' };

    console.log('Import Packages');
    await import ('@game.object/dll-tce-package-registry');
    await import ('@game.object/tce-bootloader');

    await package_loader.with_binding(registry_id);
    const registry_pkg = await package_loader.load(registry_id) as TcePackageRegistryLibInterface;
    await registry_pkg.init();
    await registry_pkg.register_provider('tobidot', '/api/public/tobidot-elements');

    await package_loader.with_binding(bootloader_id);
    const bootloader_pkg = await package_loader.load(bootloader_id);
    await bootloader_pkg.init();
}
