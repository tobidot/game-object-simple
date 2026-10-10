<x-layouts.app class="page tce-generator" title="TCE Project Generator">
    <div class="tce-generator-container">
        <div class="generator-header">
            <p class="lead">
                Generate a ready-to-use <strong>Tobidot Custom Element (TCE)</strong> TypeScript project template.
                Configure your element details below, download the generated ZIP archive, and start coding right away.
            </p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <h4>Please correct the following errors:</h4>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('tce-generator.download') }}" method="POST" enctype="multipart/form-data" class="generator-form">
            @csrf

            <div class="form-grid">
                <!-- Left Column: Core Configuration -->
                <div class="form-column">
                    <fieldset class="form-section">
                        <legend>Element Details</legend>

                        <div class="form-group">
                            <label for="element-name">
                                Element Name / Tag Name <span class="required">*</span>
                            </label>
                            <input
                                type="text"
                                id="element-name"
                                name="element-name"
                                value="{{ old('element-name', 'tce-my-element') }}"
                                placeholder="tce-my-element"
                                required
                                class="form-control"
                            >
                            <small class="form-text">
                                Used as the HTML tag name (e.g. <code>&lt;tce-my-element&gt;</code>). Must contain a hyphen.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="title">Title / Display Name</label>
                            <input
                                type="text"
                                id="title"
                                name="title"
                                value="{{ old('title', 'My Custom Element') }}"
                                placeholder="My Custom Element"
                                class="form-control"
                            >
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea
                                id="description"
                                name="description"
                                rows="3"
                                placeholder="Describe what this custom element does..."
                                class="form-control"
                            >{{ old('description', 'A custom element for the Tobidot platform.') }}</textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-half">
                                <label for="version">Initial Version</label>
                                <input
                                    type="text"
                                    id="version"
                                    name="version"
                                    value="{{ old('version', '1.0.0') }}"
                                    placeholder="1.0.0"
                                    class="form-control"
                                >
                            </div>

                            <div class="form-group col-half">
                                <label for="kind">Element Kind</label>
                                <select id="kind" name="kind" class="form-control">
                                    <option value="element" {{ old('kind') === 'element' ? 'selected' : '' }}>Element (Interactive Web Component)</option>
                                    <option value="library" {{ old('kind') === 'library' ? 'selected' : '' }}>Library / DLL (Shared Module)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="template">Template Starter Preset</label>
                            <select id="template" name="template" class="form-control">
                                <option value="standard" {{ old('template') === 'standard' ? 'selected' : '' }}>Standard UI Component (Shadow DOM, SCSS, Counter Demo)</option>
                                <option value="canvas" {{ old('template') === 'canvas' ? 'selected' : '' }}>2D Canvas / Game Starter (Game Loop & Animation)</option>
                                <option value="minimal" {{ old('template') === 'minimal' ? 'selected' : '' }}>Minimal Boilerplate (Barebones Web Component)</option>
                            </select>
                        </div>
                    </fieldset>

                    <fieldset class="form-section">
                        <legend>Window Resolution & Sizing</legend>

                        <div class="form-row">
                            <div class="form-group col-half">
                                <label for="width">Width (px)</label>
                                <input
                                    type="number"
                                    id="width"
                                    name="width"
                                    value="{{ old('width', 400) }}"
                                    min="50"
                                    max="3840"
                                    class="form-control"
                                >
                            </div>

                            <div class="form-group col-half">
                                <label for="height">Height (px)</label>
                                <input
                                    type="number"
                                    id="height"
                                    name="height"
                                    value="{{ old('height', 300) }}"
                                    min="50"
                                    max="2160"
                                    class="form-control"
                                >
                            </div>
                        </div>

                        <div class="resolution-presets">
                            <span class="presets-label">Presets:</span>
                            <button type="button" class="btn-preset" data-w="200" data-h="200">Compact (200x200)</button>
                            <button type="button" class="btn-preset" data-w="400" data-h="300">Standard (400x300)</button>
                            <button type="button" class="btn-preset" data-w="640" data-h="480">Retro 4:3 (640x480)</button>
                            <button type="button" class="btn-preset" data-w="800" data-h="600">Game (800x600)</button>
                            <button type="button" class="btn-preset" data-w="1280" data-h="720">HD 16:9 (1280x720)</button>
                        </div>
                    </fieldset>
                </div>

                <!-- Right Column: Icon, Dependencies & Preview -->
                <div class="form-column">
                    <fieldset class="form-section">
                        <legend>Icon & Media</legend>

                        <div class="form-group">
                            <label for="icon">Custom Icon File (Optional)</label>
                            <input
                                type="file"
                                id="icon"
                                name="icon"
                                accept=".png,.jpg,.jpeg,.svg"
                                class="form-control-file"
                            >
                            <small class="form-text">
                                PNG, JPG or SVG (max 2MB). If omitted, an icon will be generated automatically.
                            </small>
                        </div>
                    </fieldset>

                    <fieldset class="form-section">
                        <legend>TCE Dependencies</legend>

                        <p class="section-help-text">
                            Select other TCE elements or libraries available on this server that your project depends on.
                        </p>

                        <!-- Dropdown Selector and Add Row -->
                        <div class="tce-dep-select-row">
                            <div class="tce-dep-select-wrapper">
                                <select id="tce-element-dropdown" class="form-control">
                                    <option value="">-- Choose a TCE element / library --</option>
                                    @php
                                        $libraries = array_filter($availableTceElements ?? [], fn($el) => ($el['kind'] ?? '') === 'library');
                                        $elements = array_filter($availableTceElements ?? [], fn($el) => ($el['kind'] ?? '') !== 'library');
                                    @endphp

                                    @if (!empty($libraries))
                                        <optgroup label="Libraries & Core Modules">
                                            @foreach ($libraries as $el)
                                                <option
                                                    value="{{ $el['name'] }}"
                                                    data-name="{{ $el['name'] }}"
                                                    data-version="^{{ $el['version'] }}"
                                                    data-kind="{{ $el['kind'] }}"
                                                    data-desc="{{ $el['description'] }}"
                                                >
                                                    {{ $el['name'] }} (v{{ $el['version'] }}) [library]
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif

                                    @if (!empty($elements))
                                        <optgroup label="TCE Elements & Games">
                                            @foreach ($elements as $el)
                                                <option
                                                    value="{{ $el['name'] }}"
                                                    data-name="{{ $el['name'] }}"
                                                    data-version="^{{ $el['version'] }}"
                                                    data-kind="{{ $el['kind'] }}"
                                                    data-desc="{{ $el['description'] }}"
                                                >
                                                    {{ $el['name'] }} (v{{ $el['version'] }}) [element]
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </select>
                            </div>

                            <div class="tce-dep-version-wrapper">
                                <input
                                    type="text"
                                    id="tce-dep-version-input"
                                    class="form-control"
                                    placeholder="^1.0.0"
                                    title="Version constraint"
                                >
                            </div>

                            <button type="button" id="btn-add-tce-dep" class="btn btn-add-dep">
                                + Add
                            </button>
                        </div>

                        <!-- Quick Add Recommended Chips -->
                        <div class="quick-add-chips">
                            <span class="chips-label">Quick Add:</span>
                            @foreach ($availableTceElements ?? [] as $el)
                                @if (!empty($el['recommended']))
                                    <button
                                        type="button"
                                        class="btn-chip"
                                        data-name="{{ $el['name'] }}"
                                        data-version="^{{ $el['version'] }}"
                                        data-kind="{{ $el['kind'] }}"
                                    >
                                        + {{ $el['name'] }}
                                    </button>
                                @endif
                            @endforeach
                        </div>

                        <!-- Active Dependencies List -->
                        <div class="selected-deps-section">
                            <label class="selected-deps-label">Configured TCE Dependencies (<span id="dep-count">0</span>):</label>
                            <div id="selected-tce-dependencies-list" class="selected-deps-list">
                                <!-- Populated dynamically by JS -->
                            </div>
                        </div>

                        <!-- Collapsible Advanced / NPM Dependencies -->
                        <details class="advanced-npm-deps">
                            <summary>Additional NPM Dependencies (Optional)</summary>
                            <div class="form-group" style="margin-top: 10px;">
                                <textarea
                                    id="custom_dependencies"
                                    name="custom_dependencies"
                                    rows="2"
                                    placeholder="package-name:^1.0.0 (one per line or comma separated)"
                                    class="form-control"
                                >{{ old('custom_dependencies') }}</textarea>
                                <small class="form-text">e.g. <code>three:^0.160.0</code> or <code>lodash-es:^4.17.21</code></small>
                            </div>
                        </details>
                    </fieldset>

                    <!-- Live Summary Card -->
                    <div class="preview-card">
                        <h3>Template Summary</h3>
                        <div class="preview-item">
                            <span class="preview-label">HTML Tag:</span>
                            <code id="preview-tag">&lt;tce-my-element&gt;</code>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Class:</span>
                            <code id="preview-class">MyElement</code>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Resolution:</span>
                            <span id="preview-res">400 &times; 300 px</span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Dependencies:</span>
                            <span id="preview-deps">0 elements</span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Stack:</span>
                            <span>TypeScript + Vite + SCSS</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg">
                    Generate & Download TCE Project ZIP
                </button>
            </div>
        </form>

        <!-- Quick Workflow Guide -->
        <div class="generator-guide">
            <h3>How to use your generated project</h3>
            <div class="guide-steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h4>Extract & Install</h4>
                    <p>Unzip the downloaded archive and run <code>npm install</code> in the project directory.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h4>Develop Live</h4>
                    <p>Run <code>npm start</code> to launch Vite with hot-reloading in the isolated harness.</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h4>Package & Upload</h4>
                    <p>Run <code>make tce</code> to build and package. Upload via Nova or <code>/api/v1/tobidot-elements</code>.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const nameInput = document.getElementById('element-name') || document.getElementById('name');
            const widthInput = document.getElementById('width');
            const heightInput = document.getElementById('height');
            const previewTag = document.getElementById('preview-tag');
            const previewClass = document.getElementById('preview-class');
            const previewRes = document.getElementById('preview-res');
            const previewDeps = document.getElementById('preview-deps');
            const depCountSpan = document.getElementById('dep-count');
            const selectedDepsContainer = document.getElementById('selected-tce-dependencies-list');
            const tceDropdown = document.getElementById('tce-element-dropdown');
            const tceVersionInput = document.getElementById('tce-dep-version-input');
            const btnAddTceDep = document.getElementById('btn-add-tce-dep');

            // Dependency store: Map of name -> { name, version, kind }
            const selectedDeps = new Map();

            // Initial dependencies from server / defaults
            const initialDeps = {!! json_encode(old('tce_dependencies', old('dependencies', ['@game.object/tce-common:^1.0.6', 'dll-tce-desktop:^1.0.0']))) !!};

            if (Array.isArray(initialDeps)) {
                initialDeps.forEach(function (item) {
                    if (!item || typeof item !== 'string') return;
                    let name = item;
                    let ver = '^1.0.0';
                    let kind = 'library';

                    if (item.includes(':')) {
                        const parts = item.split(':');
                        name = parts[0].trim();
                        ver = parts[1].trim();
                    } else if (item.includes('@') && !item.startsWith('@')) {
                        const parts = item.split('@');
                        name = parts[0].trim();
                        ver = parts[1].trim();
                    }

                    if (name) {
                        // Attempt to deduce kind from dropdown options if available
                        const opt = tceDropdown.querySelector(`option[value="${name}"]`);
                        if (opt && opt.dataset.kind) {
                            kind = opt.dataset.kind;
                        } else if (name.startsWith('dll-') || name.startsWith('@game.object/')) {
                            kind = 'library';
                        } else {
                            kind = 'element';
                        }
                        selectedDeps.set(name, { name: name, version: ver, kind: kind });
                    }
                });
            }

            function toStudly(str) {
                return str
                    .replace(/^[tce-]+/, '')
                    .replace(/[-_]+(.)?/g, (_, c) => c ? c.toUpperCase() : '')
                    .replace(/^(.)/, c => c.toUpperCase()) || 'CustomElement';
            }

            function updatePreview() {
                let name = (nameInput.value || 'my-element').trim().toLowerCase().replace(/[^a-z0-9-_]/g, '-');
                if (!name.includes('-')) {
                    name = 'tce-' + name;
                }
                previewTag.textContent = '<' + name + '>';
                previewClass.textContent = toStudly(name);

                const w = widthInput.value || 400;
                const h = heightInput.value || 300;
                previewRes.innerHTML = w + ' &times; ' + h + ' px';

                const count = selectedDeps.size;
                previewDeps.textContent = count + (count === 1 ? ' element' : ' elements');
            }

            function renderDependencies() {
                selectedDepsContainer.innerHTML = '';
                const count = selectedDeps.size;
                depCountSpan.textContent = count;

                if (count === 0) {
                    const emptyDiv = document.createElement('div');
                    emptyDiv.className = 'empty-deps-notice';
                    emptyDiv.textContent = 'No additional TCE dependencies selected. (Core @game.object/tce-common is always included).';
                    selectedDepsContainer.appendChild(emptyDiv);
                    updatePreview();
                    return;
                }

                selectedDeps.forEach(function (dep) {
                    const itemDiv = document.createElement('div');
                    itemDiv.className = 'selected-dep-card';

                    const infoDiv = document.createElement('div');
                    infoDiv.className = 'dep-meta';

                    const nameCode = document.createElement('code');
                    nameCode.className = 'dep-name';
                    nameCode.textContent = dep.name;
                    infoDiv.appendChild(nameCode);

                    const kindSpan = document.createElement('span');
                    kindSpan.className = 'dep-badge dep-badge-' + (dep.kind || 'element');
                    kindSpan.textContent = dep.kind || 'element';
                    infoDiv.appendChild(kindSpan);

                    const verSpan = document.createElement('span');
                    verSpan.className = 'dep-badge dep-badge-ver';
                    verSpan.textContent = dep.version || '^1.0.0';
                    infoDiv.appendChild(verSpan);

                    itemDiv.appendChild(infoDiv);

                    // Hidden input for form submission
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'tce_dependencies[]';
                    hiddenInput.value = `${dep.name}:${dep.version}`;
                    itemDiv.appendChild(hiddenInput);

                    // Remove button
                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'btn-remove-dep';
                    removeBtn.innerHTML = '&times;';
                    removeBtn.title = 'Remove ' + dep.name;
                    removeBtn.addEventListener('click', function () {
                        selectedDeps.delete(dep.name);
                        renderDependencies();
                    });
                    itemDiv.appendChild(removeBtn);

                    selectedDepsContainer.appendChild(itemDiv);
                });

                updatePreview();
            }

            // Dropdown change handler: autofill version input
            tceDropdown.addEventListener('change', function () {
                const selectedOpt = tceDropdown.options[tceDropdown.selectedIndex];
                if (selectedOpt && selectedOpt.value) {
                    tceVersionInput.value = selectedOpt.dataset.version || '^1.0.0';
                } else {
                    tceVersionInput.value = '';
                }
            });

            // Add dependency handler
            function addCurrentDependency() {
                const selectedOpt = tceDropdown.options[tceDropdown.selectedIndex];
                const depName = (selectedOpt && selectedOpt.value) ? selectedOpt.value : tceDropdown.value.trim();

                if (!depName) {
                    return;
                }

                let depVer = tceVersionInput.value.trim();
                if (!depVer) {
                    depVer = (selectedOpt && selectedOpt.dataset.version) ? selectedOpt.dataset.version : '^1.0.0';
                }

                const depKind = (selectedOpt && selectedOpt.dataset.kind) ? selectedOpt.dataset.kind : (depName.startsWith('dll-') ? 'library' : 'element');

                selectedDeps.set(depName, {
                    name: depName,
                    version: depVer,
                    kind: depKind
                });

                // Reset inputs
                tceDropdown.selectedIndex = 0;
                tceVersionInput.value = '';

                renderDependencies();
            }

            btnAddTceDep.addEventListener('click', addCurrentDependency);

            tceVersionInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addCurrentDependency();
                }
            });

            // Quick add chip click handler
            document.querySelectorAll('.btn-chip').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const name = this.dataset.name;
                    const ver = this.dataset.version || '^1.0.0';
                    const kind = this.dataset.kind || 'library';

                    if (name) {
                        selectedDeps.set(name, {
                            name: name,
                            version: ver,
                            kind: kind
                        });
                        renderDependencies();
                    }
                });
            });

            nameInput.addEventListener('input', updatePreview);
            widthInput.addEventListener('input', updatePreview);
            heightInput.addEventListener('input', updatePreview);

            document.querySelectorAll('.btn-preset').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    widthInput.value = this.dataset.w;
                    heightInput.value = this.dataset.h;
                    updatePreview();
                });
            });

            renderDependencies();
            updatePreview();
        });
    </script>
</x-layouts.app>
