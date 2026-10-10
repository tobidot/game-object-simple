<?php

use App\DTO\TceGeneratorConfig;

/** @var TceGeneratorConfig $config */
?>
# <?= $config->title ?>

<?= $config->description ?>

- **Element Tag:** `<<?= $config->name ?>>`
- **Class Name:** `<?= $config->className ?>`
- **Version:** `<?= $config->version ?>`
- **Kind:** `<?= $config->kind ?>`
- **Default Resolution:** `<?= $config->width ?>x<?= $config->height ?>`

---

## Getting Started

### 1. Install Dependencies
```bash
npm install
```

### 2. Start Local Development Server
```bash
npm start
# or
npm run dev
```
Open [http://localhost:5173](http://localhost:5173) in your browser to view and develop the element live.

### 3. Build for Production
```bash
npm run build
```
This builds the bundled element into the `dist/` directory.

### 4. Package as TCE Zip
```bash
make tce
```
This creates a ready-to-upload ZIP file in `releases/<?= $config->name ?>-v<?= $config->version ?>.zip`.

---

## Uploading to `game-object-de`

You can upload the resulting package via:
1. **Nova Admin Panel:** Navigate to **Tobidot Elements** &rarr; select **Upload Tobidot Element** action.
2. **API Endpoint:**
   ```bash
   curl -X POST https://your-server.com/api/v1/tobidot-elements \
     -H "Authorization: Bearer <YOUR_TOKEN>" \
     -F "name=<?= $config->name ?>" \
     -F "version=<?= $config->version ?>" \
     -F "kind=<?= $config->kind ?>" \
     -F "description=<?= $config->description ?>" \
     -F "zip=@releases/<?= $config->name ?>-v<?= $config->version ?>.zip" \
     -F "icon=@assets/icon.svg"
   ```

---

## Project Structure
- `src/<?= $config->className ?>.ts` - Main Web Component definition.
- `src/index.ts` - Package registration and entrypoint.
- `src/applet.html` - HTML template for the element.
- `src/applet.scss` - SCSS stylesheet scoped to the component.
- `src/import.ts` - Local dev harness integration.
- `tobidot-project.json` - Tobidot project manifest.
- `assets/` - Icons and media assets.
