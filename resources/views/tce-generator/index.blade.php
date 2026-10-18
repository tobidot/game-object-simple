<x-layouts.app class="page tce-generator" title="TCE Project Generator">
    <x-slot name="head">
        <style>
            .tce-generator .selected-deps-list { display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; }
            .tce-generator .selected-dep-card { display: flex; align-items: flex-start; gap: 8px; cursor: pointer; }
            .tce-generator .selected-dep-card input { margin-top: 3px; }
            .tce-generator .dep-meta { display: flex; flex-direction: column; gap: 2px; }
            .tce-generator .dep-meta .form-text { margin: 0; }
        </style>
    </x-slot>
    <div class="tce-generator-container">
        <div class="generator-header">
            <p class="lead">
                Generate a complete, building <strong>Tobidot Custom Element v3</strong> project: TypeScript, Vite,
                SCSS, lint rules and the SDK it was generated against, laid out exactly as the
                <a href="{{ url(config('larecipe.docs.route').'/'.config('larecipe.versions.default').'/features/tce-framework/index') }}">specification</a> prescribes.
            </p>
        </div>

        @if (! $sdk_published)
            <div class="alert alert-danger">
                The SDK packages ({{ implode(', ', array_keys(config('tce.sdk_packages'))) }}) are not published to this
                registry yet, so nothing can be generated. Publish them from the v3 workspace first.
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <h4>Please correct the following:</h4>
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
                <div class="form-column">
                    <fieldset class="form-section">
                        <legend>Package</legend>

                        <div class="form-row">
                            <div class="form-group col-half">
                                <label for="namespace">Namespace <span class="required">*</span></label>
                                <input type="text" id="namespace" name="namespace" class="form-control"
                                       value="{{ old('namespace', $default_namespace) }}" pattern="[0-9a-zA-Z\-_]+" required>
                                <small class="form-text">The publisher. Packages are identified as <code>namespace/name</code>.</small>
                            </div>
                            <div class="form-group col-half">
                                <label for="name">Name <span class="required">*</span></label>
                                <input type="text" id="name" name="name" class="form-control"
                                       value="{{ old('name', 'tce-my-element') }}" pattern="[a-z][a-z0-9]*(-[a-z0-9]+)+" required>
                                <small class="form-text">The tag name: lower-case, at least one hyphen. <code>tce-</code> for elements, <code>dll-</code> for libraries.</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" class="form-control" value="{{ old('title') }}" placeholder="My Element">
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3" class="form-control"
                                      placeholder="What this package does, in one or two sentences.">{{ old('description') }}</textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-half">
                                <label for="version">Initial version</label>
                                <input type="text" id="version" name="version" class="form-control" value="{{ old('version', '0.1.0') }}" pattern="\d+\.\d+\.\d+">
                            </div>
                            <div class="form-group col-half">
                                <label for="kind">Kind</label>
                                <select id="kind" name="kind" class="form-control">
                                    <option value="element" @selected(old('kind', 'element') === 'element')>Element (custom element with a window)</option>
                                    <option value="library" @selected(old('kind') === 'library')>Library (functions for other packages, no DOM)</option>
                                </select>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="form-section" data-element-only>
                        <legend>Element</legend>

                        <div class="form-group">
                            <label for="template">Starter preset</label>
                            <select id="template" name="template" class="form-control">
                                <option value="standard" @selected(old('template', 'standard') === 'standard')>Standard — Settings / References / Properties / Renderer / Logic split, small counter demo</option>
                                <option value="canvas" @selected(old('template') === 'canvas')>2D Canvas — same split, low-fps frame loop on a canvas</option>
                                <option value="minimal" @selected(old('template') === 'minimal')>Minimal — one small class, nothing else</option>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-half">
                                <label for="width">Window width (px)</label>
                                <input type="number" id="width" name="width" class="form-control" value="{{ old('width', 400) }}" min="50" max="3840">
                            </div>
                            <div class="form-group col-half">
                                <label for="height">Window height (px)</label>
                                <input type="number" id="height" name="height" class="form-control" value="{{ old('height', 300) }}" min="50" max="2160">
                            </div>
                        </div>

                        <div class="resolution-presets">
                            <span class="presets-label">Presets:</span>
                            <button type="button" class="btn-preset" data-w="240" data-h="240">240×240</button>
                            <button type="button" class="btn-preset" data-w="400" data-h="300">400×300</button>
                            <button type="button" class="btn-preset" data-w="640" data-h="480">640×480</button>
                            <button type="button" class="btn-preset" data-w="800" data-h="600">800×600</button>
                        </div>
                    </fieldset>
                </div>

                <div class="form-column">
                    <fieldset class="form-section">
                        <legend>Icon</legend>
                        <div class="form-group">
                            <label for="icon">Icon file (optional)</label>
                            <input type="file" id="icon" name="icon" accept=".svg,.png" class="form-control-file">
                            <small class="form-text">
                                64×64 SVG or PNG in the house palette. Without one, a placeholder icon is generated.
                            </small>
                        </div>
                    </fieldset>

                    <fieldset class="form-section">
                        <legend>Dependencies</legend>
                        <p class="section-help-text">
                            Registry packages this project will declare and load at runtime. A library dependency gets a
                            vendored contract file to fill in.
                        </p>
                        @php
                            $selected = (array) old('dependencies', []);
                            $groups = collect($available_dependencies)->groupBy('kind');
                        @endphp
                        @forelse (['library' => 'Libraries', 'element' => 'Elements'] as $kind => $label)
                            @if ($groups->has($kind))
                                <label class="selected-deps-label">{{ $label }}</label>
                                <div class="selected-deps-list">
                                    @foreach ($groups[$kind] as $package)
                                        @php $key = "{$package['namespace']}/{$package['name']}"; @endphp
                                        <label class="selected-dep-card">
                                            <input type="checkbox" name="dependencies[]" value="{{ $key }}" @checked(in_array($key, $selected, true))>
                                            <span class="dep-meta">
                                                <code class="dep-name">{{ $key }}</code>
                                                <span class="dep-badge dep-badge-ver">{{ $package['version'] }}</span>
                                                @if ($package['description'] !== '')
                                                    <small class="form-text">{{ $package['description'] }}</small>
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        @empty
                        @endforelse
                        @if ($groups->isEmpty())
                            <div class="empty-deps-notice">No packages are published to this registry yet.</div>
                        @endif
                    </fieldset>

                    <div class="preview-card">
                        <h3>Summary</h3>
                        <div class="preview-item">
                            <span class="preview-label">Identifier:</span>
                            <code id="preview-identifier">{{ $default_namespace }}/tce-my-element</code>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Class:</span>
                            <code id="preview-class">TceMyElement</code>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Embed:</span>
                            <code id="preview-embed">&lt;tce-my-element&gt;</code>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">SDK:</span>
                            <span>{{ implode(', ', array_map(fn (array $sdk) => $sdk['npm_name'], config('tce.sdk_packages'))) }} (vendored)</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg" @disabled(! $sdk_published)>
                    Generate &amp; download project zip
                </button>
            </div>
        </form>

        <div class="generator-guide">
            <h3>Afterwards</h3>
            <div class="guide-steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h4>Install &amp; run</h4>
                    <p>Unzip, <code>npm install</code>, <code>npm start</code> opens the dev harness with live reload.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h4>Check</h4>
                    <p><code>npm run check</code> runs tsc and eslint with the coding-standard rules of the specification.</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h4>Publish</h4>
                    <p><code>npm run pack</code> builds and zips <code>dist/</code>; upload the zip via <code>POST /api/v1/tce-packages</code> (the README has the exact call).</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const namespace_input = document.getElementById('namespace');
            const name_input = document.getElementById('name');
            const kind_input = document.getElementById('kind');
            const width_input = document.getElementById('width');
            const height_input = document.getElementById('height');
            const preview_identifier = document.getElementById('preview-identifier');
            const preview_class = document.getElementById('preview-class');
            const preview_embed = document.getElementById('preview-embed');
            const element_sections = document.querySelectorAll('[data-element-only]');

            function studly(name) {
                return name.split('-').filter(Boolean).map((part) => part.charAt(0).toUpperCase() + part.slice(1)).join('');
            }

            function update() {
                const name = name_input.value.trim().toLowerCase();
                const is_library = kind_input.value === 'library';
                preview_identifier.textContent = `${namespace_input.value.trim()}/${name}`;
                preview_class.textContent = studly(name) || '—';
                preview_embed.textContent = is_library ? `<script src=".../tce-elements/${namespace_input.value.trim()}/${name}">` : `<${name}>`;
                element_sections.forEach((section) => { section.hidden = is_library; });
            }

            [namespace_input, name_input, kind_input].forEach((input) => input.addEventListener('input', update));
            kind_input.addEventListener('change', update);
            document.querySelectorAll('.btn-preset').forEach((button) => {
                button.addEventListener('click', function () {
                    width_input.value = this.dataset.w;
                    height_input.value = this.dataset.h;
                });
            });
            update();
        });
    </script>
</x-layouts.app>
