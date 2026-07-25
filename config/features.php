<?php

/**
 * Feature flags for incremental Phase 14 UI migration.
 *
 * Disable ENTERPRISE_SHELL to roll back to the legacy sidebar/header chrome
 * while keeping tokenized CSS and ui.* components available.
 */
return [
    'enterprise_shell' => (bool) env('ENTERPRISE_SHELL', true),
    'workspace_nav' => (bool) env('WORKSPACE_NAV', true),
    'command_palette' => (bool) env('COMMAND_PALETTE', true),
    'global_search_modal' => (bool) env('GLOBAL_SEARCH_MODAL', true),
    'notification_drawer' => (bool) env('NOTIFICATION_DRAWER', true),
    'theme_switcher' => (bool) env('THEME_SWITCHER', true),
];
