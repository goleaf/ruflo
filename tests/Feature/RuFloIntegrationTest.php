<?php

test('agent mcp manifests register RuFlo non-interactively', function (string $path, array $serverPath) {
    $manifest = json_decode(file_get_contents(base_path($path)), true, flags: JSON_THROW_ON_ERROR);

    $server = $manifest;

    foreach ($serverPath as $segment) {
        $server = $server[$segment];
    }

    expect($server['command'])->toBe('npx')
        ->and($server['args'])->toBe([
            '--yes',
            'ruflo@latest',
            'mcp',
            'start',
        ]);
})->with([
    '.mcp.json' => ['.mcp.json', ['mcpServers', 'ruflo']],
    '.cursor/mcp.json' => ['.cursor/mcp.json', ['mcpServers', 'ruflo']],
    '.factory/mcp.json' => ['.factory/mcp.json', ['mcpServers', 'ruflo']],
    '.amp/settings.json' => ['.amp/settings.json', ['amp.mcpServers', 'ruflo']],
]);

test('opencode mcp config registers RuFlo non-interactively', function () {
    $manifest = json_decode(file_get_contents(base_path('opencode.json')), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['mcp']['ruflo']['command'])->toBe([
        'npx',
        '--yes',
        'ruflo@latest',
        'mcp',
        'start',
    ]);
});

test('codex mcp config registers RuFlo non-interactively', function () {
    expect(file_get_contents(base_path('.codex/config.toml')))
        ->toContain('[mcp_servers.ruflo]')
        ->toContain('command = "npx"')
        ->toContain('args = ["--yes", "ruflo@latest", "mcp", "start"]');
});
