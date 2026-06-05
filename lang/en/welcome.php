<?php

return [
    'title' => 'RuFlo',
    'image_alt' => 'RuFlo banner',
    'tagline' => 'Agent orchestration platform',
    'subtitle' => 'Multi-agent AI harness for Claude Code and Codex',
    'source' => 'Source',
    'eyebrow' => 'Full loop or plugin-only',
    'heading' => 'RuFlo',
    'description' => 'Install the CLI when you want the full swarm, memory, hooks, and daemon. Use the plugin path when you only need slash commands and agent definitions.',

    'steps' => [
        [
            'label' => 'Step 1',
            'heading' => 'Choose your path',
            'description' => 'Plugins keep the workspace light. CLI installs the complete loop.',
        ],
        [
            'label' => 'Step 2',
            'heading' => 'Register MCP',
            'description' => 'The full loop becomes callable from Claude Code once the server is added.',
        ],
        [
            'label' => 'Step 3',
            'heading' => 'Use normally',
            'description' => 'Claude Code routes work through RuFlo in the background.',
        ],
    ],

    'paths' => [
        'plugin' => [
            'label' => 'Path A',
            'heading' => 'Claude Code plugins',
            'badge' => 'Slash commands only',
            'description' => 'Use this when you want agent definitions and slash commands without registering the full MCP server.',
        ],
        'cli' => [
            'label' => 'Path B',
            'heading' => 'CLI install',
            'badge' => 'Full loop',
            'description' => 'This path installs the MCP server, hooks, daemon, memory, and the full swarm workflow.',
        ],
    ],

    'use' => [
        'label' => 'Use it',
        'install' => 'Install either the plugin path or the CLI path.',
        'register' => 'If you use the CLI path, register the MCP server in Claude Code.',
        'normal' => 'Keep using Claude Code normally. RuFlo routes the background coordination.',
    ],

    'features' => [
        'label' => 'What you get',
        'agents' => '100+ agents for coding, testing, security, docs, and architecture.',
        'memory' => 'Shared memory, swarms, hooks, and background workers.',
        'federation' => 'Federation support for secure cross-machine collaboration.',
        'demo' => 'Hosted demo at :url if you want to see it first.',
    ],

    'footer' => [
        'description' => 'RuFlo is the command layer; this page keeps the install paths in one place.',
        'demo' => 'Hosted demo',
        'source' => 'Source',
    ],
];
