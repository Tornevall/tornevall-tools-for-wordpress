<?php
/**
 * Deterministic source contract checks for Guestbook connection administration.
 *
 * Run with: php tests/guestbook-connection-contract-test.php
 */

$root = dirname(__DIR__);
$connection = file_get_contents($root . '/includes/class-ttfw-guestbook-connection-admin.php');
$api = file_get_contents($root . '/includes/class-ttfw-guestbook-api.php');

if ($connection === false || $api === false) {
    fwrite(STDERR, "Unable to read Guestbook source files.\n");
    exit(1);
}

$checks = [
    'update admin action is registered' => strpos($connection, 'admin_post_ttfw_guestbook_update_book') !== false,
    'existing guestbooks expose an Edit action' => strpos($connection, "esc_html__( 'Edit', 'tornevall-tools-for-wordpress' )") !== false,
    'edit form posts the existing guestbook id' => strpos($connection, 'name="guestbook_id"') !== false,
    'selected local slug is refreshed after update' => strpos($connection, 'TTFW_Guestbook_Settings::set_selected_guestbook( $guestbook_id, $updated_slug )') !== false,
    'Tools Guestbook is removed from the core Tools menu' => strpos($connection, "remove_submenu_page( 'tools.php', TTFW_Guestbook_Admin::PAGE_SLUG )") !== false,
    'Tools Guestbook is registered below Tornevall Tools' => strpos($connection, 'TTFW_Settings::PAGE_SLUG') !== false && strpos($connection, 'TTFW_Guestbook_Admin::PAGE_SLUG') !== false,
    'API client supports owner guestbook updates' => strpos($api, 'function update_owned_book') !== false,
    'API client uses the unversioned owned-books endpoint' => strpos($api, "'/owned/books/' . \$guestbook_id") !== false,
    'API client uses PATCH for updates' => strpos($api, "'PATCH'") !== false,
    'no versioned guestbook API path was introduced' => strpos($api, '/v1/') === false && strpos($api, '/v2/') === false,
];

$failed = [];
foreach ($checks as $label => $passed) {
    if (!$passed) {
        $failed[] = $label;
    }
}

if ($failed !== []) {
    fwrite(STDERR, "Guestbook connection contract failures:\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}

echo "Guestbook connection contract checks passed.\n";
