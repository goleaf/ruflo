<?php

return [
    'title' => 'RuFlo Control Deck',
    'eyebrow' => 'Authenticated workspace',
    'heading' => 'RuFlo Control Deck',
    'description' => 'Your private workspace summary is scoped to your account before any counts or labels render.',

    'summary' => [
        'active' => 'Active',
        'overdue' => 'Overdue',
        'completed' => 'Completed',
        'archived' => 'Archived',
        'trash' => 'Trash',
        'projects' => 'Projects',
        'tags' => 'Tags',
    ],

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
        'heading' => 'Install paths',
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
        'label' => 'Private workspace',
        'heading' => 'Todos stay owner-scoped',
        'description' => 'Lists, project filters, tag filters, and dashboard counters use the same authenticated owner boundary.',
        'action' => 'Open todos',
    ],
];
