#!/usr/bin/env php
<?php

// Print next-step guidance after composer install/update.

$lines = [
    '',
    '✅ Installation complete.',
    '',
    'Next step: scaffold your package details.',
    '',
    '  - Run: php bin/bootstrap',
    '    or: composer bootstrap',
    '',
    'The bootstrap will:',
    '  • Update namespaces and provider class',
    '  • Rename the config file/key',
    '  • Adjust composer constraints (optional)',
    '  • Update test namespaces and CI mappings',
    '',
    'Tip: You can re-run it anytime.',
    '',
];

fwrite(STDOUT, implode(PHP_EOL, $lines).PHP_EOL);
