<?php

use App\DTO\TceGeneratorConfig;

/** @var TceGeneratorConfig $config */
?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128" width="128" height="128">
  <defs>
    <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#0284c7;stop-opacity:1" />
      <stop offset="100%" style="stop-color:#0f172a;stop-opacity:1" />
    </linearGradient>
  </defs>
  <rect width="128" height="128" rx="24" fill="url(#grad)" />
  <rect x="8" y="8" width="112" height="112" rx="18" fill="none" stroke="#38bdf8" stroke-width="2" stroke-opacity="0.4" />
  <text x="64" y="74" font-family="-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif" font-size="44" font-weight="bold" fill="#f8fafc" text-anchor="middle" dominant-baseline="middle"><?= $config->getInitials() ?></text>
  <circle cx="96" cy="32" r="8" fill="#38bdf8" />
</svg>
