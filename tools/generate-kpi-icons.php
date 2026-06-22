<?php

$icons = [
    'price-of-roti', 'price-of-bakery-bread', 'price-control', 'road-repair', 'zebra-crossings',
    'streetlights', 'manholes', 'water-filtration', 'education', 'health', 'marriage-act',
    'anti-encroachment', 'stray-dogs', 'wall-chalking', 'graveyards', 'illegal-decanting',
    'cleanliness', 'greenbelts', 'drains-sewerage', 'bus-terminals', 'complaint-management',
    'shops-handcarts', 'e-biz',
];

$dir = __DIR__ . '/../public/assets/images/kpi-icons';
if (! is_dir($dir)) {
    mkdir($dir, 0755, true);
}

foreach ($icons as $name) {
    $svg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="48" height="48">
  <rect width="48" height="48" rx="12" fill="#e9f7f0"/>
  <rect x="8" y="8" width="32" height="32" rx="8" fill="#087443" opacity="0.12"/>
  <circle cx="24" cy="20" r="8" fill="none" stroke="#087443" stroke-width="2.5"/>
  <rect x="16" y="30" width="16" height="4" rx="2" fill="#087443" opacity="0.7"/>
</svg>
SVG;
    file_put_contents($dir . '/' . $name . '.svg', $svg);
}

echo count($icons) . " icons created in {$dir}\n";
