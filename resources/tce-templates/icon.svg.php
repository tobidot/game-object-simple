<?php
/** @var \App\DTO\TceGeneratorConfig $config */
$initials = htmlspecialchars($config->getInitials(), ENT_QUOTES);
?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64" shape-rendering="crispEdges">
    <!-- Flat house palette: #6cdad9 #6c6c6c #26306a #d48c17 #111111 #ffffff -->
    <rect x="4" y="4" width="56" height="56" fill="#6cdad9"/>
    <rect x="4" y="4" width="56" height="2" fill="#ffffff"/>
    <rect x="4" y="4" width="2" height="56" fill="#ffffff"/>
    <rect x="4" y="58" width="56" height="2" fill="#6c6c6c"/>
    <rect x="58" y="4" width="2" height="56" fill="#6c6c6c"/>
    <rect x="2" y="2" width="60" height="60" fill="none" stroke="#111111" stroke-width="2"/>
    <rect x="12" y="12" width="40" height="8" fill="#26306a"/>
    <rect x="12" y="12" width="8" height="8" fill="#d48c17"/>
    <text x="32" y="47" text-anchor="middle" font-family="monospace" font-size="22" font-weight="bold" fill="#26306a"><?= $initials ?></text>
</svg>
