<?php

namespace Database\Seeders;

use App\Enums\AttachmentType;
use App\Enums\PublishState;
use App\Models\Attachment;
use App\Models\TobidotElement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class TobidotElementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $disk = Storage::disk('public');

        $packages = [
            [
                'name' => 'dll-tce-package-registry',
                'kind' => 'library',
                'major' => 1,
                'minor' => 0,
                'patch' => 0,
                'description' => 'Package discovery and dependency resolver provider',
                'file_name' => 'dll-tce-package-registry.js',
                'content' => <<<'JS'
(function() {
    window.tobidot = window.tobidot || {};
    if (!window.tobidot.tce_package_loader) {
        const loaded = new Map();
        const providers = [];
        window.tobidot.tce_package_loader = {
            async load(id) {
                const key = (id && typeof id === 'object') ? (id.namespace + '/' + id.name) : String(id);
                if (loaded.has(key)) {
                    return loaded.get(key);
                }
                const mock = {
                    async init() {},
                    load(fnName) {
                        if (fnName === 'exists') return async () => false;
                        if (fnName === 'read') return async () => '';
                        if (fnName === 'create_icon' || fnName === 'create_window') {
                            return ($app, opts) => document.createElement('div');
                        }
                        return () => {};
                    }
                };
                loaded.set(key, mock);
                return mock;
            },
            async register_provider(namespace, url) {
                providers.push({ namespace, url });
            }
        };
    }
})();
JS
                ,
            ],
            [
                'name' => 'dll-tce-desktop',
                'kind' => 'library',
                'major' => 1,
                'minor' => 0,
                'patch' => 0,
                'description' => 'Desktop shell and window manager services',
                'file_name' => 'dll-tce-desktop.js',
                'content' => <<<'JS'
export default {
    async init() {},
    load(name) {
        if (name === 'create_icon' || name === 'create_window') {
            return ($application, options) => document.createElement('div');
        }
        return () => {};
    }
};
JS
                ,
            ],
            [
                'name' => 'dll-tce-file-system',
                'kind' => 'library',
                'major' => 1,
                'minor' => 0,
                'patch' => 0,
                'description' => 'Virtual file system and asset loader',
                'file_name' => 'dll-tce-file-system.js',
                'content' => <<<'JS'
export default {
    async init() {},
    load(name) {
        if (name === 'exists') return async (file) => false;
        if (name === 'read') return async (file) => '';
        return () => {};
    }
};
JS
                ,
            ],
            [
                'name' => 'tce-desktop',
                'kind' => 'element',
                'major' => 1,
                'minor' => 0,
                'patch' => 0,
                'description' => 'TCE Desktop Environment container',
                'file_name' => 'tce-desktop.js',
                'content' => <<<'JS'
if (!customElements.get('tce-desktop')) {
    customElements.define('tce-desktop', class extends HTMLElement {
        connectedCallback() {
            this.style.display = 'flex';
            this.style.flexDirection = 'column';
            this.style.width = '100%';
            this.style.height = '100%';
            this.style.position = 'relative';
            this.style.background = '#0f172a';
            this.innerHTML = `
                <div style="flex: 1; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-family: ui-sans-serif, system-ui, sans-serif;">
                    <div style="text-align: center;">
                        <h1 style="font-size: 1.75rem; color: #f1f5f9; margin-bottom: 0.5rem;">TCE Desktop</h1>
                        <p style="font-size: 0.95rem; color: #64748b;">Tobidot Custom Elements Desktop Environment</p>
                    </div>
                </div>
                <div style="height: 40px; background: #1e293b; border-top: 1px solid #334155; display: flex; align-items: center; padding: 0 1rem; justify-content: flex-end;">
                    <slot name="task-bar-icons"></slot>
                </div>
            `;
        }
    });
}
JS
                ,
            ],
            [
                'name' => 'tce-bootloader',
                'kind' => 'element',
                'major' => 1,
                'minor' => 0,
                'patch' => 0,
                'description' => 'Bootloader initialization component',
                'file_name' => 'tce-bootloader.js',
                'content' => <<<'JS'
if (!customElements.get('tce-bootloader')) {
    customElements.define('tce-bootloader', class extends HTMLElement {
        connectedCallback() {
            this.style.display = 'none';
        }
    });
}
JS
                ,
            ],
            [
                'name' => 'tce-clock',
                'kind' => 'element',
                'major' => 1,
                'minor' => 0,
                'patch' => 0,
                'description' => 'Taskbar Clock Element',
                'file_name' => 'tce-clock.js',
                'content' => <<<'JS'
if (!customElements.get('tce-clock')) {
    customElements.define('tce-clock', class extends HTMLElement {
        connectedCallback() {
            this.style.display = 'flex';
            this.style.alignItems = 'center';
            this.style.justifyContent = 'center';
            this.style.color = '#e2e8f0';
            this.style.fontSize = '12px';
            const updateTime = () => {
                const now = new Date();
                this.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            };
            updateTime();
            this._timer = setInterval(updateTime, 1000);
        }
        disconnectedCallback() {
            if (this._timer) clearInterval(this._timer);
        }
    });
}
JS
                ,
            ],
        ];

        foreach ($packages as $pkgData) {
            $existing = TobidotElement::query()
                ->where('name', $pkgData['name'])
                ->where('major', $pkgData['major'])
                ->where('minor', $pkgData['minor'])
                ->where('patch', $pkgData['patch'])
                ->first();

            if ($existing) {
                continue;
            }

            $element = TobidotElement::create([
                'name' => $pkgData['name'],
                'kind' => $pkgData['kind'],
                'major' => $pkgData['major'],
                'minor' => $pkgData['minor'],
                'patch' => $pkgData['patch'],
                'description' => $pkgData['description'],
                'width' => 400,
                'height' => 300,
            ]);

            $relDir = 'tobidot-elements/'.$pkgData['name'].'/'.$pkgData['major'].'.'.$pkgData['minor'].'.'.$pkgData['patch'];
            $relFilePath = $relDir.'/'.$pkgData['file_name'];
            $disk->put($relFilePath, $pkgData['content']);

            $attachment = Attachment::create([
                'path' => $relDir,
                'file_name' => $pkgData['file_name'],
                'url' => '/storage/'.$relFilePath,
                'hash' => hash('sha256', $pkgData['content']),
                'content_type' => 'application/javascript',
                'publish_state' => PublishState::PUBLISHED,
                'type' => AttachmentType::TEXT,
            ]);

            $element->attachments()->attach($attachment->id, ['relation' => 'code']);
        }
    }
}
