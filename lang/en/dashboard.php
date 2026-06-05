<?php

return [
    'title' => 'RuFlo Control Deck',
    'eyebrow' => 'Authenticated workspace',
    'heading' => 'RuFlo Control Deck',
    'description' => 'This workspace is already branded for RuFlo and wired into the existing MCP manifests. Use this page when you want the shortest path to the CLI install, the plugin path, or the hosted demo.',

    'cards' => [
        'mcp' => [
            'label' => 'MCP server',
            'description' => 'Register this in Claude Code to expose the full loop.',
        ],
        'plugin' => [
            'label' => 'Plugin path',
            'description' => 'Use this for slash commands and agent definitions only.',
        ],
        'demo' => [
            'label' => 'Hosted demo',
            'description' => 'Open the web UI first if you want to inspect the hosted experience.',
        ],
    ],

    'install' => [
        'label' => 'CLI path',
        'heading' => 'Full install',
        'badge' => 'Hooks + daemon + memory',
        'description' => 'Run the wizard if you want the safest guided install. Use the direct MCP command when you already know the target client.',
    ],

    'next' => [
        'label' => 'Quick use',
        'heading' => 'What to do next',
        'plugin' => 'Pick the plugin path if you only need commands and agents.',
        'cli' => 'Pick the CLI path if you want swarms, hooks, memory, and the daemon.',
        'normal' => 'Keep using Claude Code normally. RuFlo handles coordination in the background.',
    ],

    'workspace' => [
        'label' => 'Workspace note',
        'description' => 'The local agent manifests in this repo already register both Laravel Boost and RuFlo.',
    ],
];
