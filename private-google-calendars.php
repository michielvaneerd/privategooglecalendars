<?php
/*
Plugin Name: Private Google Calendars
Description: Display multiple private Google Calendars
Plugin URI: http://blog.michielvaneerd.nl/private-google-calendars/
Version: 20241103
Author: Michiel van Eerd
Author URI: http://michielvaneerd.nl/
License: GPL2
Text Domain: private-google-calendars
Domain Path: /languages
*/

// Always set this to the same version as "Version" in header! Used for query parameters added to style and scripts.
define('PGC_PLUGIN_VERSION', '20241103');

if (!defined('PGC_THEMES_DIR_NAME')) {
  define('PGC_THEMES_DIR_NAME', 'pgc_themes');
}

if (!class_exists('PGC_GoogleClient')) {
  require_once(plugin_dir_path(__FILE__) . 'lib/google-client.php');
}

define('PGC_TRANSIENT_PREFIX', 'pgc_ev_');

if (!defined('PGC_EVENTS_MAX_RESULTS')) {
  define('PGC_EVENTS_MAX_RESULTS', 250);
}

if (!defined('PGC_EVENTS_DEFAULT_TITLE')) {
  define('PGC_EVENTS_DEFAULT_TITLE', '');
}

if (!defined('PGC_CALENDARS_MAX_RESULTS')) {
  define('PGC_CALENDARS_MAX_RESULTS', 250);
}

// Priority for the enqueue css and javascript.
// We need to be sure to load them after the Wordpress theme css files, so we can override some things to make the fullcalendar look good.
// If someone wants to override this or add their own styles, they have to enqueue their style with a higher priority.
define('PGC_ENQUEUE_ACTION_PRIORITY', 11);

function initTranslatedDefines()
{
  define('PGC_PLUGIN_NAME', __('Private Google Calendars'));

  define('PGC_NOTICES_VERIFY_SUCCESS', __('Verify OK!', 'private-google-calendars'));
  define('PGC_NOTICES_REVOKE_SUCCESS', __('Access revoked. This plugin does not have access to your calendars anymore.', 'private-google-calendars'));
  define('PGC_NOTICES_REMOVE_SUCCESS', sprintf(__('Plugin data removed. Make sure to also manually revoke access to your calendars in the Google <a target="__blank" href="%s">Permissions</a> page!', 'private-google-calendars'), 'https://myaccount.google.com/permissions'));
  define('PGC_NOTICES_CALENDARLIST_UPDATE_SUCCESS', __('Calendars updated.', 'private-google-calendars'));
  define('PGC_NOTICES_COLORLIST_UPDATE_SUCCESS', __('Colors updated.', 'private-google-calendars'));
  define('PGC_NOTICES_CACHE_DELETED', __('Cache deleted.', 'private-google-calendars'));

  define('PGC_ERRORS_CLIENT_SECRET_MISSING', __('No client secret.', 'private-google-calendars'));
  define('PGC_ERRORS_CLIENT_SECRET_INVALID', __('Invalid client secret.', 'private-google-calendars'));
  define('PGC_ERRORS_ACCESS_TOKEN_MISSING', __('No access token.', 'private-google-calendars'));
  define('PGC_ERRORS_REFRESH_TOKEN_MISSING', sprintf(__('Your refresh token is missing!<br><br>This can only be solved by manually revoking this plugin&#39;s access in the Google <a target="__blank" href="%s">Permissions</a> page and remove all plugin data.', 'private-google-calendars'), 'https://myaccount.google.com/permissions'));
  define('PGC_ERRORS_ACCESS_REFRESH_TOKEN_MISSING', __('No access and refresh tokens.', 'private-google-calendars'));
  define('PGC_ERRORS_REDIRECT_URI_MISSING', __('URI <code>%s</code> missing in the client secret file. Adjust your Google project and upload the new client secret file.', 'private-google-calendars'));
  define('PGC_ERRORS_INVALID_FORMAT', __('Invalid format', 'private-google-calendars'));
  define('PGC_ERRORS_NO_CALENDARS', __('No calendars', 'private-google-calendars'));
  define('PGC_ERRORS_NO_SELECTED_CALENDARS',  __('No selected calendars', 'private-google-calendars'));
  define('PGC_ERRORS_TOKEN_AND_API_KEY_MISSING',  __('Access token and API key are missing.', 'private-google-calendars'));
}

/**
 * Add shortcode.
 */
add_action('init', 'pgc_init');
function pgc_init()
{

  load_plugin_textdomain('private-google-calendars', FALSE, basename(dirname(__FILE__)) . '/languages/');

  if (!defined('PGC_PLUGIN_NAME')) {
    initTranslatedDefines();
  }

  add_shortcode('pgc', 'pgc_shortcode');
  if (function_exists('register_block_type')) {
    pgc_register_block();
  }
}

if (is_admin()) {
  $plugin = plugin_basename(__FILE__);
  add_filter('plugin_action_links_' . $plugin, 'pgc_add_plugin_settings_links');
  add_filter('network_admin_plugin_action_links_' . $plugin, 'pgc_add_plugin_settings_links');
}

function pgc_wrap_in_theme_class($theme)
{
  return 'pgc-theme-' . $theme;
}

function pgc_get_current_theme($userTheme)
{
  if (!empty($userTheme)) return $userTheme;
  return get_option('pgc_fullcalendar_theme');
}

function pgc_get_custom_themes_dir()
{
  $uploadDir = wp_upload_dir();
  return $uploadDir['basedir'] . '/' . PGC_THEMES_DIR_NAME;
}

function pgc_get_custom_themes_url()
{
  $uploadDir = wp_upload_dir();
  return $uploadDir['baseurl'] . '/' . PGC_THEMES_DIR_NAME;
}

function pgc_get_themes_dir()
{
  return __DIR__ . '/css/themes';
}

function pgc_get_themes_url()
{
  return plugin_dir_url(__FILE__) . 'css/themes';
}

// Rule: inlcuded themes ALWAYS start with 'pgc-' e.g. 'pgc-dark.css'.
// Custom themes NEVER start with 'pgc-'.
function pgc_get_themes()
{
  $themes = [];

  $files = scandir(pgc_get_themes_dir());
  if (is_dir(pgc_get_custom_themes_dir())) {
    $files = array_merge($files, scandir(pgc_get_custom_themes_dir()));
  }
  foreach ($files as $file) {
    if (preg_match("/^(.+)\.css$/", $file, $matches)) {
      $themes[] = $matches[1];
    }
  }
  return $themes;
}

function pgc_add_plugin_settings_links($links)
{
  if (!is_network_admin()) {
    $link = '<a href="options-general.php?page=pgc">' . __('Plugin settings', 'private-google-calendars') . '</a>';
    array_unshift($links, $link);
  } else {
    // switch_to_blog(1);
    $link = '<a href="' . admin_url('options-general.php') . '?page=pgc">' . __('Plugin settings', 'private-google-calendars') . '</a>';
    // restore_current_blog();
    array_unshift($links, $link);
  }
  return $links;
}

function pgc_register_block()
{

  $asset_file = include(plugin_dir_path(__FILE__) . 'build/index.asset.php');

  wp_register_script(
    'pgc-plugin-script',
    plugins_url('build/index.js', __FILE__),
    $asset_file['dependencies'],
    PGC_PLUGIN_VERSION
  );

  wp_register_style(
    'pgc-plugin-style',
    plugins_url('css/block-style.css', __FILE__),
    ['wp-edit-blocks'],
    PGC_PLUGIN_VERSION
  );

  register_block_type('pgc-plugin/calendar', array(
    'editor_script' => 'pgc-plugin-script',
    'editor_style' => 'pgc-plugin-style'
  ));

  // Make the selected calendars available for the block.
  $selectedCalendarIds = get_option('pgc_selected_calendar_ids');
  if (empty($selectedCalendarIds)) {
    $selectedCalendarIds = [];
  }
  $calendarList = getDecoded('pgc_calendarlist', []);
  $selectedCalendars = [];
  foreach ($calendarList as $calendar) {
    if (in_array($calendar['id'], $selectedCalendarIds)) {
      $selectedCalendars[$calendar['id']] = $calendar;
    }
  }

  $blockTrans = [
    'calendar_options' => __('Calendar options', 'private-google-calendars'),
    'selected_calendars' => __('Selected calendars', 'private-google-calendars'),
    'all' => __('All', 'private-google-calendars'),
    'none' => __('None', 'private-google-calendars'),
    'public' => __('Public', 'private-google-calendars'),
    'public_calendars' => __('Public calendar(s)', 'private-google-calendars'),
    'show_calendar_filter' => __('Show calendar filter', 'private-google-calendars'),
    'edit_fullcalendar_config' => __('Edit FullCalendar config', 'private-google-calendars'),
    'hide_passed_events' => __('Hide passed events...', 'private-google-calendars'),
    'hide_future_events' => __('Hide future events...', 'private-google-calendars'),
    'popup_options' => __('Popup options', 'private-google-calendars'),
    'show' => __('Show', 'private-google-calendars'),
    'hide' => __('Hide', 'private-google-calendars'),
    'copy_fullcalendar_config_info' => __('Copy the default FullCalendar config if you want to change it. This is the configuration object that you can set as the second argument in the <code>FullCalendar.Calendar</code> constructor.', 'private-google-calendars'),
    'fullcalendar_docs_link' => __('See the <a target="_blank" href="https://fullcalendar.io/docs#toc">FullCalendar documentation</a> for available configuration options.', 'private-google-calendars'),
    'eventpopup' => __('Show event popup', 'private-google-calendars'),
    'eventlink' => __('Show event link', 'private-google-calendars'),
    'eventdescription' => __('Show event description', 'private-google-calendars'),
    'eventlocation' => __('Show event location', 'private-google-calendars'),
    'eventattendees' => __('Show event attendees', 'private-google-calendars'),
    'eventattachments' => __('Show event attachments', 'private-google-calendars'),
    'eventcreator' => __('Show event creator', 'private-google-calendars'),
    'eventcalendarname' => __('Show calendarname', 'private-google-calendars'),
    'more_than' => __('...more than', 'private-google-calendars'),
    'days_ago' => __('days ago', 'private-google-calendars'),
    'days_from_now' => __('days from now', 'private-google-calendars'),
    'malformed_json' => __('Malformed JSON, this calendar will probably not display correctly', 'private-google-calendars'),
    'enter_one_or_more_public_calendar_ids' => __('Add at least one calendar!', 'private-google-calendars'),
    'malformed_json_short' => __('Malformed JSON', 'private-google-calendars'),
    'fullcalendar_config' => __('FullCalendar config', 'private-google-calendars'),
    'copy_default_fullcalendar_config' => __('Copy default FullCalendar config', 'private-google-calendars'),
    'comma_separated_list_calendar_ids' => __('Comma separated list of public calendar IDs', 'private-google-calendars'),
    'show_filter_bottom' => __('Show filter at bottom', 'private-google-calendars'),
    'show_filter_top' => __('Show filter at top', 'private-google-calendars'),
    'hide_filter' => __('Hide filter', 'private-google-calendars'),
    'filter_options' => __('Filter options', 'private-google-calendars'),
    'filter_uncheckedcalendarids' => __('Unchecked calendar IDs', 'private-google-calendars'),
    'plugin_version' => PGC_PLUGIN_VERSION,
    'theme' => __('Theme', 'private-google-calendars'),
    'default' => __('Default', 'private-google-calendars'),
    'themes' => pgc_get_themes(),
    'fullcalendar_version' => get_option('pgc_fullcalendar_version', 4)
  ];

  wp_add_inline_script('pgc-plugin-script', 'window.pgc_selected_calendars=' . json_encode($selectedCalendars) . ';', 'before');
  wp_add_inline_script('pgc-plugin-script', 'window.pgc_trans = ' . json_encode($blockTrans) . ';', 'before');
}

function pgc_shortcode($atts = [])
{

  // When we have no attributes, $atts is an empty string
  if (!is_array($atts)) {
    $atts = [];
  }

  //var_dump($atts);
  //exit;

  $fcVersion = get_option('pgc_fullcalendar_version', 4);

  // Very wierd: you can enter uppercase in attribute name
  // but after parsing they will have all lowercase...
  // So  we have to match lowercase with known camelCase fullCalendar properties...
  // "It should be noted that even though attributes can be used with mixed case in the editor, they will always be lowercase after parsing."
  // https://codex.wordpress.org/Shortcode_API#Attributes

  // Add some default fullcalendar options.
  // See for available options: https://fullcalendar.io/docs/
  // We accept nested attributes like this:
  // [pgc header-left="today" header-center="title"] which becomes:
  // ['header' => ['left' => 'today', 'center' => 'title']]
  // 'header' is called in FC5 'headerToolbar' (and 'footer' to 'footerToolbar')
  $defaultConfig = $fcVersion >= 5 ? [
    'headerToolbar' => [
      'start' => 'prev,next today',
      'center' => 'title',
      'end' => 'dayGridMonth,timeGridWeek,listWeek'
    ]
  ] : [
    'header' => [
      'left' => 'prev,next today',
      'center' => 'title',
      'right' => 'dayGridMonth,timeGridWeek,listWeek'
    ]
  ];
  $userConfig = $defaultConfig; // copy
  $userFilter = 'top';
  $userTheme = '';
  $userEventPopup = 'true';
  $userEventLink = 'false';
  $userHidePassed = 'false';
  $userHideFuture = 'false';
  $userEventDescription = 'false';
  $userEventLocation = 'false';
  $userEventAttendees = 'false';
  $userEventAttachments = 'false';
  $userEventCreator = 'false';
  $userEventCalendarname = 'false';
  $calendarIds = '';
  $uncheckedCalendarIds = ''; // in filter
  // Get all non-fullcalendar known properties
  foreach ($atts as $key => $value) {

    if ($key === 'public') {
      // This existsed in old versions, but we don't want it in our shortcode output, so skip it.
      continue;
    }
    if ($key === 'filter') {
      $userFilter = $value === 'true' ? 'top' : $value;
      continue;
    }
    if ($key === 'theme') {
      $userTheme = $value;
      continue;
    }
    if ($key === 'eventpopup') {
      $userEventPopup = $value;
      continue;
    }
    if ($key === 'eventlink') {
      $userEventLink = $value;
      continue;
    }
    if ($key === 'hidepassed') {
      $userHidePassed = $value;
      continue;
    }
    if ($key === 'hidefuture') {
      $userHideFuture = $value;
      continue;
    }
    if ($key === 'eventdescription') {
      $userEventDescription = $value;
      continue;
    }
    if ($key === 'eventattachments') {
      $userEventAttachments = $value;
      continue;
    }
    if ($key === 'eventattendees') {
      $userEventAttendees = $value;
      continue;
    }
    if ($key === 'eventlocation') {
      $userEventLocation = $value;
      continue;
    }
    if ($key === 'eventcreator') {
      $userEventCreator = $value;
      continue;
    }
    if ($key === 'eventcalendarname') {
      $userEventCalendarname = $value;
      continue;
    }
    if ($key === 'uncheckedcalendarids' && !empty($value)) {
      $uncheckedCalendarIds = $value; // comma separated string
      continue;
    }

    // START FIX calids
    // Note: for the shortcode we DON'T make a difference between old and new way
    // Here no calendarids STILL means ALL private calendars.
    // Reason is that if we regard no calendarids as really no calendars, all users will suddenly see no events.
    // Another reason pro this approach: users can add public and private calendar IDs to this property.
    if ($key === 'calendarids') {
      if (!empty($value)) {
        $calendarIds = $value; // comma separated string
      }
      continue;
    }
    // END FIX calids

    if ($key === 'fullcalendarconfig') {
      // A JSON string that we can directly send to FullCalendar
      $userConfig = json_decode($value, true);
    } else {
      // Fullcalendar properties that get passed to fullCalendar instance.
      $parts = explode('-', $key);
      $partsCount = count($parts);
      if ($partsCount > 1) {
        $currentUserConfigLayer = &$userConfig;
        for ($i = 0; $i < $partsCount; $i++) {
          $part = $parts[$i];
          if ($i + 1 === $partsCount) {
            if ($value === 'true') {
              $value = true;
            } elseif ($value === 'false') {
              $value = $value;
            }
            $currentUserConfigLayer[$part] = $value;
          } else {
            if (!array_key_exists($part, $currentUserConfigLayer)) {
              $currentUserConfigLayer[$part] = [];
            }
            $currentUserConfigLayer = &$currentUserConfigLayer[$part];
          }
        }
      } else {
        $userConfig[$key] = $value;
      }
    }
  }

  $calendarIdsAsArray = [];
  if (!empty($calendarIds)) {
    // Note: calendarIds is a comma separated string so make it into an array.
    $calendarIdsAsArray = array_map('trim', explode(',', $calendarIds));
  } else {
    $privateSettingsSelectedCalendarListIds = get_option('pgc_selected_calendar_ids', []);
    if (!empty($privateSettingsSelectedCalendarListIds)) {
      // Note: privateSettingsSelectedCalendarListIds is already an array.
      $calendarIdsAsArray = $privateSettingsSelectedCalendarListIds;
    }
  }

  // Note that this is used below 2 times, so we escape the attribute here and output this below.
  $filterHTML = '<div class="pgc-calendar-filter" ' . (!empty($uncheckedCalendarIds) ? (' data-uncheckedcalendarids=\'' . esc_attr(json_encode(array_map('trim', explode(',', $uncheckedCalendarIds)))) . '\' ') : '') . '></div>';

  $activeTheme = pgc_get_current_theme($userTheme);

  // Note that filterHTML is already escaped above and consist of HTML so we cannot escape it below.
  return '<div class="pgc-calendar-wrapper pgc-calendar-page ' . esc_attr(pgc_wrap_in_theme_class($activeTheme)) . '">' . ($userFilter === 'top' ? $filterHTML : '') . '<div '
    . (!empty($calendarIdsAsArray) ? (' data-calendarids=\'' . esc_attr(json_encode($calendarIdsAsArray)) . '\' ') : '') . ' data-filter=\'' . esc_attr($userFilter) . '\' data-eventpopup=\'' . esc_attr($userEventPopup) . '\' data-eventlink=\''
    . esc_attr($userEventLink) . '\' data-eventdescription=\'' . esc_attr($userEventDescription) . '\' data-eventlocation=\''
    . esc_attr($userEventLocation) . '\' data-eventattachments=\'' . esc_attr($userEventAttachments) . '\' data-eventattendees=\''
    . esc_attr($userEventAttendees) . '\' data-eventcreator=\'' . esc_attr($userEventCreator) . '\' data-eventcalendarname=\''
    . esc_attr($userEventCalendarname) . '\' data-hidefuture=\'' . esc_attr($userHideFuture) . '\' data-hidepassed=\''
    . esc_attr($userHidePassed) . '\' data-config=\'' . esc_attr(json_encode($userConfig)) . '\' data-locale="'
    . get_locale() . '" data-theme="' . esc_attr($activeTheme) . '" class="pgc-calendar"></div>' . ($userFilter === 'bottom' ? $filterHTML : '') . '</div>';

}

/**
 * Add CSS and Javascript for admin.
 */
add_action('admin_enqueue_scripts', 'pgc_admin_enqueue_scripts');
function pgc_admin_enqueue_scripts($hook)
{
  if ($hook === 'settings_page_pgc' || $hook === 'widgets.php') {
    wp_enqueue_script('pgc-admin', plugin_dir_url(__FILE__) . 'js/pgc-admin.js', null, PGC_PLUGIN_VERSION);
    wp_enqueue_style('pgc-admin', plugin_dir_url(__FILE__) . 'css/pgc-admin.css', null, PGC_PLUGIN_VERSION);
  }
}

/**
 * Add CSS and Javascript for frontend.
 */
//add_action('wp_enqueue_scripts', 'pgc_enqueue_scripts', PHP_INT_MAX);
add_action('wp_enqueue_scripts', 'pgc_enqueue_scripts', PGC_ENQUEUE_ACTION_PRIORITY);
// make sure we load last after theme files so we can override.
function pgc_enqueue_scripts()
{

  wp_enqueue_style('dashicons');

  $fullcalendarVersion = get_option('pgc_fullcalendar_version');

  if ($fullcalendarVersion >= 5) {
    wp_enqueue_script(
      'pgc_main',
      plugin_dir_url(__FILE__) . 'lib/dist/main.js',
      null,
      PGC_PLUGIN_VERSION,
      true
    );
    $nonce = wp_create_nonce('pgc_nonce');
    wp_localize_script('pgc_main', 'pgc_object', [
      'ajax_url' => admin_url('admin-ajax.php'),
      'custom_themes_url' => pgc_get_custom_themes_url(),
      'themes_url' => pgc_get_themes_url(),
      'nonce' => $nonce,
      'trans' => [
        'all_day' => __('All day', 'private-google-calendars'),
        'created_by' => __('Created by', 'private-google-calendars'),
        'go_to_event' => __('Go to event', 'private-google-calendars'),
        'unknown_error' => __('Unknown error', 'private-google-calendars'),
        'request_error' => __('Request error', 'private-google-calendars'),
        'loading' => __('Loading', 'private-google-calendars'),
      ]
    ]);
    return;
  }

  // Old FullCalendar (4)

  wp_enqueue_style(
    'tippy_light',
    plugin_dir_url(__FILE__) . 'lib/tippy/light-border.css',
    null,
    PGC_PLUGIN_VERSION
  );
  wp_enqueue_script(
    'popper',
    plugin_dir_url(__FILE__) . 'lib/popper.min.js',
    null,
    PGC_PLUGIN_VERSION,
    true
  );
  wp_enqueue_script(
    'tippy',
    plugin_dir_url(__FILE__) . 'lib/tippy/tippy-bundle.umd.min.js',
    ['popper'],
    PGC_PLUGIN_VERSION,
    true
  );

  wp_enqueue_style(
    'pgc_fullcalendar',
    plugin_dir_url(__FILE__) . 'lib/fullcalendar4/core/main.min.css',
    null,
    PGC_PLUGIN_VERSION
  );
  wp_enqueue_style(
    'pgc_fullcalendar_daygrid',
    plugin_dir_url(__FILE__) . 'lib/fullcalendar4/daygrid/main.min.css',
    ['pgc_fullcalendar'],
    PGC_PLUGIN_VERSION
  );
  wp_enqueue_style(
    'pgc_fullcalendar_timegrid',
    plugin_dir_url(__FILE__) . 'lib/fullcalendar4/timegrid/main.min.css',
    ['pgc_fullcalendar_daygrid'],
    PGC_PLUGIN_VERSION
  );
  wp_enqueue_style(
    'pgc_fullcalendar_list',
    plugin_dir_url(__FILE__) . 'lib/fullcalendar4/list/main.min.css',
    ['pgc_fullcalendar'],
    PGC_PLUGIN_VERSION
  );
  wp_enqueue_style(
    'pgc',
    plugin_dir_url(__FILE__) . 'css/pgc.css',
    ['pgc_fullcalendar_timegrid'],
    PGC_PLUGIN_VERSION
  );
  wp_enqueue_script(
    'my_moment',
    plugin_dir_url(__FILE__) . 'lib/moment/moment-with-locales.min.js',
    null,
    PGC_PLUGIN_VERSION,
    true
  );
  wp_enqueue_script(
    'my_moment_timezone',
    plugin_dir_url(__FILE__) . 'lib/moment/moment-timezone-with-data.min.js',
    ['my_moment'],
    PGC_PLUGIN_VERSION,
    true
  );
  wp_enqueue_script(
    'pgc_fullcalendar',
    plugin_dir_url(__FILE__) . 'lib/fullcalendar4/core/main.min.js',
    ['my_moment_timezone'],
    PGC_PLUGIN_VERSION,
    true
  );
  wp_enqueue_script(
    'pgc_fullcalendar_moment',
    plugin_dir_url(__FILE__) . 'lib/fullcalendar4/moment/main.min.js',
    ['pgc_fullcalendar'],
    PGC_PLUGIN_VERSION,
    true
  );
  wp_enqueue_script(
    'pgc_fullcalendar_moment_timezone',
    plugin_dir_url(__FILE__) . 'lib/fullcalendar4/moment-timezone/main.min.js',
    ['pgc_fullcalendar_moment'],
    PGC_PLUGIN_VERSION,
    true
  );
  wp_enqueue_script(
    'pgc_fullcalendar_daygrid',
    plugin_dir_url(__FILE__) . 'lib/fullcalendar4/daygrid/main.min.js',
    ['pgc_fullcalendar'],
    PGC_PLUGIN_VERSION,
    true
  );
  wp_enqueue_script(
    'pgc_fullcalendar_timegrid',
    plugin_dir_url(__FILE__) . 'lib/fullcalendar4/timegrid/main.min.js',
    ['pgc_fullcalendar_daygrid'],
    PGC_PLUGIN_VERSION,
    true
  );
  wp_enqueue_script(
    'pgc_fullcalendar_list',
    plugin_dir_url(__FILE__) . 'lib/fullcalendar4/list/main.min.js',
    ['pgc_fullcalendar'],
    PGC_PLUGIN_VERSION,
    true
  );
  wp_enqueue_script(
    'pgc_fullcalendar_locales',
    plugin_dir_url(__FILE__) . 'lib/fullcalendar4/core/locales-all.min.js',
    ['pgc_fullcalendar'],
    PGC_PLUGIN_VERSION,
    true
  );
  wp_enqueue_script(
    'pgc',
    plugin_dir_url(__FILE__) . 'js/pgc.js',
    ['pgc_fullcalendar'],
    PGC_PLUGIN_VERSION,
    true
  );
  $nonce = wp_create_nonce('pgc_nonce');
  wp_localize_script('pgc', 'pgc_object', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => $nonce,
    'trans' => [
      'all_day' => __('All day', 'private-google-calendars'),
      'created_by' => __('Created by', 'private-google-calendars'),
      'go_to_event' => __('Go to event', 'private-google-calendars'),
      'unknown_error' => __('Unknown error', 'private-google-calendars'),
      'request_error' => __('Request error', 'private-google-calendars'),
      'loading' => __('Loading', 'private-google-calendars')
    ]
  ]);
}

/**
 * Handle AJAX request from frontend.
 */
add_action('wp_ajax_pgc_ajax_get_calendar', 'pgc_ajax_get_calendar');
add_action('wp_ajax_nopriv_pgc_ajax_get_calendar', 'pgc_ajax_get_calendar');
function pgc_ajax_get_calendar()
{

  check_ajax_referer('pgc_nonce');

  try {

    if (empty($_POST['start']) || empty($_POST['end'])) {
      throw new Exception(PGC_ERRORS_INVALID_FORMAT);
    }

    // Start and end are in ISO8601 string format with timezone offset (e.g. 2018-09-01T12:30:00-05:00)
    $start = $_POST['start'];
    $end = $_POST['end'];

    $thisCalendarids = [];
    $postedCalendarIds = [];
    if (array_key_exists('thisCalendarids', $_POST) && !empty($_POST['thisCalendarids'])) {
      $postedCalendarIds = array_map('trim', explode(',', $_POST['thisCalendarids']));
    }
    $privateSettingsCalendarListIds = array_map(function ($item) {
      return $item['id'];
    }, getDecoded('pgc_calendarlist', []));
    if (!empty($privateSettingsCalendarListIds)) {
      $privateSettingsSelectedCalendarListIds = get_option('pgc_selected_calendar_ids');
      // if (empty($postedCalendarIds)) {
      //   // If we have private selected calendars in settings and we get NO selected calendars from widget, shortcode, Gutenberg block, this means
      //   // ALL private calendars will be used.
      //   $postedCalendarIds = $privateSettingsSelectedCalendarListIds;
      // }
      foreach ($postedCalendarIds as $calId) {
        if (!in_array($calId, $privateSettingsCalendarListIds) || in_array($calId, $privateSettingsSelectedCalendarListIds)) {
          $thisCalendarids[] = $calId;
        }
      }
    } else {
      $thisCalendarids = $postedCalendarIds;
    }

    $cacheTime = get_option('pgc_cache_time'); // empty == no cache!

    // We can have mutiple calendars with different calendar selections,
    // so key should be including calendar selection.
    $transientKey = PGC_TRANSIENT_PREFIX . $start . $end . md5(implode('-', $thisCalendarids));

    $transientItems = !empty($cacheTime) ? get_transient($transientKey) : false;

    $calendarListByKey = pgc_get_calendars_by_key($thisCalendarids);

    if ($transientItems !== false) {
      wp_send_json(['items' => $transientItems, 'calendars' => $calendarListByKey]);
      wp_die();
    }

    $colorList = false; // false means not queried yet / otherwise [] or filled []

    $results = [];

    $optParams = array(
      'maxResults' => PGC_EVENTS_MAX_RESULTS,
      'orderBy' => 'startTime',
      'singleEvents' => 'true',
      'timeMin' => $start,
      'timeMax' => $end,
    );
    if (!empty($_POST['timeZone'])) {
      $optParams['timeZone'] = $_POST['timeZone'];
    }

    $hasAccessToken = get_option('pgc_access_token');

    if (!empty($hasAccessToken)) {

      $client = getGoogleClient(true);
      if ($client->isAccessTokenExpired()) {
        if (!$client->getRefreshTOken()) {
          throw new Exception(PGC_ERRORS_REFRESH_TOKEN_MISSING);
        }
        $client->refreshAccessToken();
      }
      $service = new PGC_GoogleCalendarClient($client);

      foreach ($thisCalendarids as $calendarId) {
        $results[$calendarId] = $service->getEvents($calendarId, $optParams);
      }
    } elseif (!empty(get_option('pgc_api_key'))) {

      $referer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
      $apiKey = get_option('pgc_api_key');
      $service = new PGC_GoogleCalendarClient(null);
      foreach ($thisCalendarids as $calendarId) {
        $results[$calendarId] = $service->getEventsPublic($calendarId, $optParams, $apiKey, $referer);
      }
    } else {
      // No API key and no OAuth2 token
      throw new Exception(PGC_ERRORS_TOKEN_AND_API_KEY_MISSING);
    }

    $showPrivateEvents = get_option('pgc_show_private_events');
    $privateEventsTitle = get_option('pgc_private_events_title');

    $items = [];
    foreach ($results as $calendarId => $events) {
      foreach ($events as $item) {
        $isPrivate = array_key_exists('visibility', $item) && $item['visibility'] === 'private';
        if (empty($showPrivateEvents) && $isPrivate) {
          continue;
        }
        $newItem = [
          'title' => $isPrivate && !empty($privateEventsTitle) ? $privateEventsTitle : (empty($item['summary']) ? PGC_EVENTS_DEFAULT_TITLE : $item['summary']),
          'htmlLink' => $item['htmlLink'],
          'description' => !empty($item['description']) ? $item['description'] : '',
          'calId' => $calendarId,
          'creator' => !empty($item['creator']) ? $item['creator'] : [],
          'attendees' => !empty($item['attendees']) ? $item['attendees'] : [],
          'attachments' => !empty($item['attachments']) ? $item['attachments'] : [],
          'location' => !empty($item['location']) ? $item['location'] : '',
          'visibility' => !empty($item['visibility']) ? $item['visibility'] : '',
        ];
        if (!empty($item['start']['date'])) {
          $newItem['allDay'] = true;
          $newItem['start'] = $item['start']['date'];
          $newItem['end'] = $item['end']['date'];
          // $newItem['timeZone'] = $item['start']['timeZone']; // TODO? end timezone also exists...
        } else {
          $newItem['start'] = $item['start']['dateTime'];
          $newItem['end'] = $item['end']['dateTime'];
          // $newItem['timeZone'] = $item['start']['timeZone']; // TODO? end timezone also exists...
        }
        if (!empty($item['colorId'])) {
          if ($colorList === false) {
            $colorList = getDecoded('pgc_colorlist', []);
          }
          if (array_key_exists('event', $colorList) && array_key_exists($item['colorId'], $colorList['event'])) {
            $newItem['bColor'] = $colorList['event'][$item['colorId']]['background'];
            $newItem['fColor'] = $colorList['event'][$item['colorId']]['foreground'];
          }
        }

        $items[] = $newItem;
      }
    }

    if (!empty($cacheTime)) {
      set_transient($transientKey, $items, $cacheTime * MINUTE_IN_SECONDS);
    }

    wp_send_json(['items' => $items, 'calendars' => $calendarListByKey]);
    wp_die();
  } catch (PGC_GoogleClient_RequestException $ex) {
    wp_send_json([
      'stack' => $ex->getTraceAsString(),
      'error' => $ex->getMessage(),
      'errorCode' => $ex->getCode(),
      'errorDescription' => $ex->getDescription()
    ]);
    wp_die();
  } catch (Exception $ex) {
    wp_send_json([
      'stack' => $ex->getTraceAsString(),
      'error' => $ex->getMessage(),
      'errorCode' => $ex->getCode()
    ]);
    wp_die();
  }
}

function pgc_get_calendars_by_key($calendarIds)
{

  $publicCalendarList = get_option('pgc_public_calendarlist');
  if (empty($publicCalendarList)) {
    $publicCalendarList = [];
  }
  $privateCalendarList = getDecoded('pgc_calendarlist', []);
  if (empty($privateCalendarList)) {
    $privateCalendarList = [];
  }
  $calendarList = $publicCalendarList + $privateCalendarList;
  $keyedCalendarList = [];
  foreach ($calendarList as $cal) {
    $keyedCalendarList[$cal['id']] = $cal;
  }

  $calendarListByKey = [];
  foreach ($calendarIds as $calId) {
    $cal = array_key_exists($calId, $keyedCalendarList) ? $keyedCalendarList[$calId] : [
      'summary' => $calId,
      'backgroundColor' => 'rgb(121, 134, 203)'
    ];
    $calendarListByKey[$calId] = [
      'summary' => $cal['summary'],
      'backgroundColor' => $cal['backgroundColor']
    ];
  }

  return $calendarListByKey;
}

/**
 * Add new settings page to menu.
 */
add_action('admin_menu', 'pgc_settings_page');
function pgc_settings_page()
{
  $page = add_options_page(
    PGC_PLUGIN_NAME,
    PGC_PLUGIN_NAME,
    'manage_options',
    'pgc',
    'pgc_settings_page_html'
  );
}

/**
 * Callback function that outputs settings page.
 */
function pgc_settings_page_html()
{
  if (!current_user_can('manage_options')) {
    return;
  }

?>
  <div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <p><?php _e('See the <a class="pgc-link" href="https://blog.michielvaneerd.nl/private-google-calendars/" target="_blank">website</a> for information.', 'private-google-calendars'); ?></p>
    <?php

    $clientSecretError = '';
    $clientSecret = pgc_get_valid_client_secret($clientSecretError);

    if (empty($clientSecret) || !empty($clientSecretError)) {
      //echo '<h2>' . __('Step 1: Upload client secret') . '</h2>';
      pgc_show_settings();
    } else {
      // Valid Client Secret, check access and refresh tokens
      $accessToken = getDecoded('pgc_access_token');
      $refreshToken = get_option('pgc_refresh_token');

      if (empty($accessToken)) {
        echo '<h2>' . __('Setting up private calendar access', 'private-google-calendars') . '</h2>';
        echo '<h2 style="opacity:1; color:green;">' . __('Step 1: Upload client secret', 'private-google-calendars') . ' &#10003;</h2>';
        echo '<h2>' . __('Step 2: Authorize', 'private-google-calendars') . '</h2>';
    ?>
        <form style="display:inline" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
          <input type="hidden" name="action" value="pgc_authorize">
          <?php submit_button(__('Authorize', 'private-google-calendars'), 'primary', 'pgc_authorize', false); ?>
        </form>
        <form style="display:inline" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
          <input type="hidden" name="action" value="pgc_remove_private">
          <?php submit_button(__('Stop', 'private-google-calendars'), '', 'pgc_remove_private', false); ?>
        </form>
    <?php
      } else {
        $okay = get_option('pgc_selected_calendar_ids') ? '&#10003;' : '';
        pgc_show_settings();
      }
    }

    ?>

    <?php pgc_show_tools(); ?>

  </div><!-- .wrap -->
<?php

}

/**
 * Main function for showing settings page.
 */
function pgc_show_settings()
{
?>
  <form enctype="multipart/form-data" action="options.php" method="post" id="pgc-settings-form" onsubmit="return pgc_on_submit();">
    <?php settings_fields('pgc'); ?>
    <?php do_settings_sections('pgc'); ?>
    <?php submit_button(__('Save settings', 'private-google-calendars'), 'primary', 'pgc-settings-submit'); ?>
  </form>
<?php
}

/**
 * Outputs tools section.
 */
function pgc_show_tools()
{
  global $wpdb;

  $clientSecretError = '';
  $clientSecret = pgc_get_valid_client_secret($clientSecretError);
  if (empty($clientSecret)) {
    return;
  }
  $accessToken = getDecoded('pgc_access_token');
  $refreshToken = get_option('pgc_refresh_token');

?>
  <hr>
  <h1><?php _e('Tools'); ?></h1><?php

                                if (empty($clientSecretError) && !empty($accessToken) && !empty($refreshToken)) {



                                ?>

    <h2><?php _e('Update calendars', 'private-google-calendars'); ?></h2>
    <p><?php _e('Use this when you add or remove calendars in your Google account.', 'private-google-calendars'); ?></p>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
      <input type="hidden" name="action" value="pgc_calendarlist">
      <?php submit_button(__('Update calendars'), 'small', 'submit-calendarlist', false); ?>
    </form>

    <h2><?php _e('Get colorlist', 'private-google-calendars'); ?></h2>
    <p><?php _e('Download the colorlist. You only have to use this if you use custom colors for events.', 'private-google-calendars'); ?></p>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
      <input type="hidden" name="action" value="pgc_colorlist">
      <?php submit_button(__('Update colorlist'), 'small', 'submit-colorlist', false); ?>
    </form>

    <h2><?php _e('Verify', 'private-google-calendars'); ?></h2>
    <p><?php _e('Verify if have setup everything correctly.', 'private-google-calendars'); ?></p>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
      <input type="hidden" name="action" value="pgc_verify">
      <?php submit_button(__('Verify', 'private-google-calendars'), 'small', 'submit-verify', false); ?>
    </form>

    <h2><?php _e('Cache', 'private-google-calendars'); ?></h2>
    <?php
                                  $cachedEvents = $wpdb->get_var("SELECT option_name FROM " . $wpdb->options
                                    . " WHERE option_name LIKE '_transient_timeout_" . PGC_TRANSIENT_PREFIX . "%' OR option_name LIKE '_transient_" . PGC_TRANSIENT_PREFIX . "%' LIMIT 1");
                                  $cacheArgs = [];
                                  if (empty($cachedEvents)) {
                                    $cacheArgs['disabled'] = true;
                                  }
    ?>
    <p><?php _e('Remove cached calendar events.', 'private-google-calendars'); ?></p>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
      <input type="hidden" name="action" value="pgc_deletecache">
      <?php
                                  submit_button(__('Remove cache', 'private-google-calendars'), 'small', 'submit-deletecache', false, $cacheArgs);
                                  if (empty($cachedEvents)) { ?>
        <em><?php _e('Cache is empty.', 'private-google-calendars'); ?></em>
      <?php } ?>
    </form>


    <h2><?php _e('Revoke access', 'private-google-calendars'); ?></h2>
    <p><?php _e('Revoke this plugins access to your private calendars.', 'private-google-calendars'); ?></p>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
      <input type="hidden" name="action" value="pgc_revoke">
      <?php submit_button(__('Revoke access', 'private-google-calendars'), 'small', 'submit-revoke', false); ?>
    </form>

  <?php } ?>

  <h2><?php _e('Remove plugin data', 'private-google-calendars'); ?></h2>
  <p><?php printf(__('Removes all saved plugin data.<br>If you have authorized this plugin access to your calendars, manually revoke access on the Google <a href="%s" target="__blank">Permissions</a> page.', 'private-google-calendars'), 'https://myaccount.google.com/permissions'); ?></p>
  <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
    <input type="hidden" name="action" value="pgc_remove">
    <?php submit_button(__('Remove plugin data', 'private-google-calendars'), 'small', 'submit-remove', false); ?>
  </form>



  <?php
}

function pgc_sort_calendars(&$items)
{
  // Set locale to UTF-8 variant if this is not the case.
  if (strpos(setlocale(LC_COLLATE, 0), '.UTF-8') === false) {
    // If we set this to a non existing locale it will be the default locale after this call.
    setlocale(LC_COLLATE, get_locale() . '.UTF-8');
  }
  usort($items, function ($a, $b) {
    return strcoll($a['summary'], $b['summary']);
  });
}

/**
 * Admin post action to update calendar list.
 */
add_action('admin_post_pgc_calendarlist', 'pgc_admin_post_calendarlist');
function pgc_admin_post_calendarlist()
{
  try {
    $client = getGoogleClient(true);
    if ($client->isAccessTokenExpired()) {
      if (!$client->getRefreshToken()) {
        throw new Exception(PGC_ERRORS_REFRESH_TOKEN_MISSING);
      }
      $client->refreshAccessToken();
    }
    $service = new PGC_GoogleCalendarClient($client);
    $items = $service->getCalendarList(PGC_CALENDARS_MAX_RESULTS);

    pgc_sort_calendars($items);

    update_option('pgc_calendarlist', getPrettyJSONString($items), false);
    pgc_add_notice(PGC_NOTICES_CALENDARLIST_UPDATE_SUCCESS, 'success', true);
    exit;
  } catch (Exception $ex) {
    pgc_die($ex);
  }
}

/**
 * Admin post action to update color list.
 */
add_action('admin_post_pgc_colorlist', 'pgc_admin_post_colorlist');
function pgc_admin_post_colorlist()
{
  try {
    $client = getGoogleClient(true);
    if ($client->isAccessTokenExpired()) {
      if (!$client->getRefreshToken()) {
        throw new Exception(PGC_ERRORS_REFRESH_TOKEN_MISSING);
      }
      $client->refreshAccessToken();
    }
    $service = new PGC_GoogleCalendarClient($client);
    $items = $service->getColorList();
    update_option('pgc_colorlist', getPrettyJSONString($items), false);
    pgc_add_notice(PGC_NOTICES_COLORLIST_UPDATE_SUCCESS, 'success', true);
    exit;
  } catch (Exception $ex) {
    pgc_die($ex);
  }
}

/**
 * Admin post action to delete calendar cache.
 */
add_action('admin_post_pgc_deletecache', 'pgc_admin_post_deletecache');
function pgc_admin_post_deletecache()
{
  pgc_delete_calendar_cache();
  pgc_add_notice(PGC_NOTICES_CACHE_DELETED, 'success', true);
  exit;
}

/**
 * Admin post action to verify if we have valid access and refresh token.
 */
add_action('admin_post_pgc_verify', 'pgc_admin_post_verify');
function pgc_admin_post_verify()
{
  try {
    $client = getGoogleClient(true);
    $client->refreshAccessToken();
    pgc_add_notice(PGC_NOTICES_VERIFY_SUCCESS, 'success', true);
    exit;
  } catch (Exception $ex) {
    pgc_die($ex);
  }
}

add_action('admin_post_pgc_remove_private', function () {
  pgc_delete_plugin_data('private');
  pgc_add_notice(PGC_NOTICES_REMOVE_SUCCESS, 'success', true);
  exit;
});

/**
 * Admin post action to delete all plugin data.
 */
add_action('admin_post_pgc_remove', 'pgc_admin_post_remove');
function pgc_admin_post_remove()
{
  pgc_delete_plugin_data('all');
  pgc_add_notice(PGC_NOTICES_REMOVE_SUCCESS, 'success', true);
  exit;
}

/**
 * Admin post action to revoke access and if that succeeds remove all plugin data.
 */
add_action('admin_post_pgc_revoke', 'pgc_admin_post_revoke');
function pgc_admin_post_revoke()
{
  try {
    $client = getGoogleClient();
    $accessToken = getDecoded('pgc_access_token');
    if (!empty($accessToken)) {
      $client->setAccessTokenInfo($accessToken);
    }
    $refreshToken = get_option("pgc_refresh_token");
    if (!empty($refreshToken)) {
      $client->setRefreshToken($refreshToken);
    }
    if (empty($accessToken) && empty($refreshToken)) {
      throw new Exception(PGC_ERRORS_ACCESS_REFRESH_TOKEN_MISSING);
    }
    $client->revoke();
    // Clear access and refresh tokens
    pgc_delete_plugin_data('private');
    pgc_add_notice(PGC_NOTICES_REVOKE_SUCCESS, 'success', true);
    exit;
  } catch (Exception $ex) {
    pgc_die($ex);
  }
}

/**
 * Admin post action to authorize access.
 */
add_action('admin_post_pgc_authorize', 'pgc_admin_post_authorize');
function pgc_admin_post_authorize()
{

  try {
    $client = getGoogleClient();
    $client->authorize(pgc_get_state_from_user());
    exit;
  } catch (Exception $ex) {
    pgc_die($ex);
  }
}


/**
 * Uninstall hook: try to revoke access and always delete all plugin data.
 */
register_uninstall_hook(__FILE__, 'pgc_uninstall');
function pgc_uninstall()
{
  try {
    $client = getGoogleClient();
    $accessToken = getDecoded('pgc_access_token');
    if (!empty($accessToken)) {
      $client->setAccessTokenInfo($accessToken);
    }
    $refreshToken = get_option("pgc_refresh_token");
    if (!empty($refreshToken)) {
      $client->setRefreshToken($refreshToken);
    }
    if (empty($accessToken) && empty($refreshToken)) {
      throw new Exception(PGC_ERRORS_ACCESS_REFRESH_TOKEN_MISSING);
    }
    $client->revoke();
  } catch (Exception $ex) {
    // Too bad...
  } finally {
    // Clear all plugin data
    pgc_delete_plugin_data('all');
  }
}

/**
 * Helper function to delete cache.
 */
function pgc_delete_calendar_cache()
{
  global $wpdb;
  $wpdb->query("DELETE FROM " . $wpdb->options
    . " WHERE option_name LIKE '_transient_timeout_" . PGC_TRANSIENT_PREFIX . "%' OR option_name LIKE '_transient_" . PGC_TRANSIENT_PREFIX . "%'");
}

/**
 * Helper function to delete all plugin options.
 */
function pgc_delete_options($which)
{ // which = all, public, private
  if ($which === 'all' || $which === 'private') {
    delete_option('pgc_access_token');
    delete_option('pgc_refresh_token');
    delete_option('pgc_selected_calendar_ids');
    delete_option('pgc_calendarlist');
    delete_option('pgc_client_secret');
  }
  if ($which === 'all' || $which === 'public') {
    delete_option('pgc_api_key');
  }
  if ($which === 'all') {
    delete_option('pgc_cache_time');
  }
}

/**
 * Helper function to delete all plugin data.
 */
function pgc_delete_plugin_data($which = 'all')
{
  pgc_delete_calendar_cache();
  pgc_delete_options($which);
}

/**
 * Helper function die die with different kind of errors.
 */
function pgc_die($error = null)
{
  $backLink = '<br><br><a href="' . admin_url('options-general.php?page=pgc') . '">' . __('Back', 'private-google-calendars') . '</a>';
  if (empty($error)) {
    wp_die(__('Unknown error', 'private-google-calendars') . $backLink);
  }
  if ($error instanceof Exception) {
    $s = [];
    if ($error->getCode()) {
      $x[] = $error->getCode();
    }
    $s[] = $error->getMessage();
    if ($error instanceof PGC_GoogleClient_RequestException) {
      if ($error->getDescription()) {
        $s[] = $error->getDescription();
      }
    }
    wp_die(implode("<br>", $s) . $backLink);
  } elseif (is_array($error)) {
    wp_die(implode("<br>", $error) . $backLink);
  } elseif (is_string($error)) {
    wp_die($error . $backLink);
  } else {
    wp_die(__('Unknown error format', 'private-google-calendars') . $backLink);
  }
}

/**
 * Validate secret client JSON file.
 */
function pgc_validate_client_secret_input($input)
{
  if (
    !empty($_FILES) && !empty($_FILES['pgc_client_secret'])
    && is_uploaded_file($_FILES['pgc_client_secret']['tmp_name'])
  ) {
    $content = trim(file_get_contents($_FILES['pgc_client_secret']['tmp_name']));
    $decoded = json_decode($content, true);
    if (!empty($decoded)) {
      return getPrettyJSONString($decoded);
    }
    add_settings_error('pgc', 'client_secret_input_error', PGC_ERRORS_CLIENT_SECRET_INVALID, 'error');
  }
  return null;
}

function pgc_get_state_from_user()
{
  $user = wp_get_current_user();
  return md5(serialize($user));
}

/**
 * Decide which settings to register.
 */
add_action('admin_init', 'pgc_settings_init');
function pgc_settings_init()
{

  // Important to first check state! Otherwise this can interfere with other plugins that do a redirect!
  // https://wordpress.org/support/topic/state-mismatch-error-with-contact-form-7/
  // And possibly also:
  // https://wordpress.org/support/topic/conflict-with-other-plugins-that-connect-to-google/
  if (!empty($_GET['code']) && !empty($_GET['state']) && $_GET['state'] === pgc_get_state_from_user()) {
    // Redirect from Google authorize with code that we can use to get access and refreh tokens.
    try {
      $client = getGoogleClient();
      // This will also set the access and refresh tokens on the client
      // and call the tokencallback we have set to save them in the options table.
      $client->handleCodeRedirect();
      $service = new PGC_GoogleCalendarClient($client);
      $items = $service->getCalendarList(PGC_CALENDARS_MAX_RESULTS);
      pgc_sort_calendars($items);
      update_option('pgc_calendarlist', getPrettyJSONString($items), false);
      wp_redirect(admin_url('options-general.php?page=pgc'));
      exit;
    } catch (Exception $ex) {
      pgc_die($ex);
    }
  }

  $clientSecretError = '';
  $clientSecret = pgc_get_valid_client_secret($clientSecretError);

  $accessToken = getDecoded('pgc_access_token');

  add_settings_section(
    'pgc_settings_section_always',
    __('General settings', 'private-google-calendars'),
    function () {
      _e('Settings for both private and public calendars.', 'private-google-calendars');
    },
    'pgc'
  ); // page, slug

  add_settings_section(
    'pgc_settings_section',
    __('Private calendar settings', 'private-google-calendars'),
    function () use ($clientSecret) {
      if (empty($clientSecret)) {
  ?>
      <p class="pgc-notice"><?php printf(__('Note: Create a new project in de Google developer console <strong>and make sure you set <code>%s</code> as the authorized redirect URI!</strong>', 'private-google-calendars'), admin_url('options-general.php?page=pgc')); ?></p>
    <?php
      } else {
        // empty
      }
    },
    'pgc'
  ); // page

  add_settings_section(
    'pgc_settings_section_public',
    __('Public calendar settings', 'private-google-calendars'),
    function () {
    }, // leeg
    'pgc'
  ); // page, slug

  register_setting('pgc', 'pgc_api_key', [
    'show_in_rest' => false
  ]);
  register_setting('pgc', 'pgc_cache_time', [
    'show_in_rest' => false
  ]);
  register_setting('pgc', 'pgc_show_private_events', [
    'show_in_rest' => false
  ]);
  register_setting('pgc', 'pgc_private_events_title', [
    'show_in_rest' => false
  ]);
  register_setting('pgc', 'pgc_fullcalendar_version', [
    'show_in_rest' => false
  ]);
  register_setting('pgc', 'pgc_fullcalendar_theme', [
    'show_in_rest' => true
  ]);
  // Added in settings: id / name / backgroundcolor / color
  register_setting('pgc', 'pgc_public_calendarlist', [
    'show_in_rest' => false
  ]);


  add_settings_field(
    'pgc_api_key',
    '<label for="pgc_api_key">' . __('API key', 'private-google-calendars') . '</label>',
    function () {
      $setting = get_option('pgc_api_key');
    ?>
    <input id="pgc_api_key" type="text" name="pgc_api_key" class="regular-text" value="<?php echo isset($setting) ? esc_attr($setting) : ''; ?>">
    <p><em><strong style="color:red;"><?php _e('Support for the API key will be removed in the next release, because this is not officially supported by Google.', 'private-google-calendars'); ?></strong></em></p>
  <?php
    },
    'pgc',
    'pgc_settings_section_public'
  );

  add_settings_field(
    'pgc_public_calendarlist',
    '<label for="pgc_public_calendarlist">' . __('Public calendars', 'private-google-calendars') . '</label>',
    function () {
  ?><table class="pgc-public-calendar-table">
      <tr>
        <th><?php _e('Calendar ID', 'private-google-calendars'); ?></th>
        <th><?php _e('Title', 'private-google-calendars'); ?></th>
        <th><?php _e('Color', 'private-google-calendars'); ?></th>
        <th><?php _e('Delete', 'private-google-calendars'); ?></th>
      </tr><?php
            $publicCalendars = get_option('pgc_public_calendarlist');
            // $publicCalendars can be empty string
            if (empty($publicCalendars)) {
              $publicCalendars = [];
            }
            $counter = 0;
            foreach ($publicCalendars as $publicCalendar) { ?>
        <tr class="pgc-public-calendar-row" data-source-id="<?php echo esc_attr($publicCalendar['id']); ?>">
          <td><input type="text" class="regular-text pgc-public-calendar-id" name="pgc_public_calendarlist[<?php echo $counter; ?>][id]" value="<?php echo esc_attr($publicCalendar['id']); ?>" /></td>
          <td><input type="text" class="regular-text pgc-public-calendar-title" name="pgc_public_calendarlist[<?php echo $counter; ?>][summary]" value="<?php echo esc_attr($publicCalendar['summary']); ?>" /></td>
          <td><input type="text" class="pgc-public-calendar-backgroundcolor" name="pgc_public_calendarlist[<?php echo $counter; ?>][backgroundColor]" value="<?php echo esc_attr($publicCalendar['backgroundColor']); ?>" /></td>
          <td><input type="checkbox" data-delete-target-id="<?php echo esc_attr($publicCalendar['id']); ?>" /></td>
        </tr>
      <?php $counter += 1;
            } ?>
      <tr class="pgc-public-calendar-row">
        <td><input type="text" class="regular-text" class="pgc-public-calendar-id" name="pgc_public_calendarlist[<?php echo $counter; ?>][id]" value="" /></td>
        <td><input type="text" class="regular-text" class="pgc-public-calendar-title" name="pgc_public_calendarlist[<?php echo $counter; ?>][summary]" value="" /></td>
        <td><input type="text" class="pgc-public-calendar-backgroundcolor" name="pgc_public_calendarlist[<?php echo $counter; ?>][backgroundColor]" value="" /></td>
        <td></td>
      </tr>
    </table>
    <p class="description"><?php _e('Setup your public calendars here to display a title and background color. This is optional.', 'private-google-calendars'); ?></p>
  <?php
    },
    'pgc',
    'pgc_settings_section_public'
  );

  add_settings_field(
    'pgc_settings_cache_time',
    __('Cache time in minutes', 'private-google-calendars'),
    function () {
      $cacheTime = get_option('pgc_cache_time');
  ?>
    <input type="number" name="pgc_cache_time" id="pgc_cache_time" value="<?php echo esc_attr($cacheTime); ?>" />
    <p><em><?php _e('Set to 0 to disable cache.', 'private-google-calendars'); ?></em></p>
  <?php
    },
    'pgc',
    'pgc_settings_section_always'
  );

  add_settings_field(
    'pgc_settings_show_private_events',
    __('Show private events', 'private-google-calendars'),
    function () {
      $value = get_option('pgc_show_private_events');
  ?>
    <input value="1" type="checkbox" name="pgc_show_private_events" <?php checked($value, '1', true); ?>>
  <?php
    },
    'pgc',
    'pgc_settings_section_always'
  );

  add_settings_field(
    'pgc_settings_private_events_title',
    __('Private events title', 'private-google-calendars'),
    function () {
      $privateEventsTitle = get_option('pgc_private_events_title');
  ?>
    <input type="text" name="pgc_private_events_title" id="pgc_private_events_title" value="<?php echo esc_attr($privateEventsTitle); ?>" />
    <p><em><?php _e('Show this title for private events instead of the real title.', 'private-google-calendars'); ?></em></p>
  <?php
    },
    'pgc',
    'pgc_settings_section_always'
  );

  add_settings_field(
    'pgc_settings_fullcalendar_version',
    __('FullCalendar version', 'private-google-calendars'),
    function () {
      $version = get_option('pgc_fullcalendar_version');
  ?>
    <select name="pgc_fullcalendar_version" id="pgc_fullcalendar_version">
      <option value="4" <?php selected($version, '4', true); ?>>4</option>
      <option value="5" <?php selected($version, '5', true); ?>>6</option><!-- So we display 6, but use 5, this is because 5 is replaced by 6 -->
    </select>
    <p><em><strong style="color:red;"><?php _e('Version 4 will be removed in the next release, so make sure to upgrade before.', 'private-google-calendars'); ?></strong></em></p>
  <?php
    },
    'pgc',
    'pgc_settings_section_always'
  );

  add_settings_field(
    'pgc_settings_fullcalendar_theme',
    __('FullCalendar theme', 'private-google-calendars'),
    function () {
      $version = get_option('pgc_fullcalendar_theme');
      $themes = array_map(function ($theme) use ($version) {
        return '<option value="' . $theme . '" ' . selected($version, $theme, false) . '>' . $theme . '</option>';
      }, pgc_get_themes());
  ?>
    <select name="pgc_fullcalendar_theme" id="pgc_fullcalendar_theme">
      <option value="" <?php selected($version, '', true); ?>>Default...</option>
      <?php echo implode("\n", $themes); ?>
    </select>
    <p>
      <em><?php printf(__('Place CSS files into uploads/%s directory to use custom themes.', 'private-google-calendars'), PGC_THEMES_DIR_NAME); ?></em><br>
      <a target="_blank" href="<?php echo (plugin_dir_url(__FILE__) . 'css/redbrown.css'); ?>"><?php _e('Example CSS file'); ?></a>
    </p>
  <?php
    },
    'pgc',
    'pgc_settings_section_always'
  );

  if (empty($clientSecret) || !empty($clientSecretError)) {
    // Make the options we use with register_settings not autoloaded.
    update_option('pgc_client_secret', get_option('pgc_client_secret', ''), false);
    update_option('pgc_selected_calendar_ids', get_option('pgc_selected_calendar_ids', []), false);
    register_setting('pgc', 'pgc_client_secret', [
      'show_in_rest' => false,
      'sanitize_callback' => 'pgc_validate_client_secret_input'
    ]);
  } else {
    if (!empty($accessToken)) {
      register_setting('pgc', 'pgc_selected_calendar_ids', [
        'show_in_rest' => false,
        'sanitize_callback' => 'pgc_validate_selected_calendar_ids'
      ]);
    }
  }

  if (empty($clientSecret) || !empty($clientSecretError)) {
    add_settings_field(
      'pgc_settings_client_secret_json',
      __('Upload client secret', 'private-google-calendars'),
      'pgc_settings_client_secret_json_cb',
      'pgc',
      'pgc_settings_section'
    );
  } elseif (getDecoded('pgc_calendarlist')) {

    add_settings_field(
      'pgc_settings_selected_calendar_ids_json',
      __('Select calendars', 'private-google-calendars'),
      'pgc_settings_selected_calendar_ids_json_cb',
      'pgc',
      'pgc_settings_section'
    );
  }
}

/**
 * Sanitize callback specified in register_setting.
 * Is used here to know when we save this setting, so we can remove the cache
 */
function pgc_validate_selected_calendar_ids($input)
{
  pgc_delete_calendar_cache();
  return $input;
}

/**
 * Empty callback function
 */
function pgc_settings_empty_cb()
{
}

/**
 * Callback function to show calendar list checkboxes in admin.
 */
function pgc_settings_selected_calendar_ids_json_cb()
{
  $calendarList = getDecoded('pgc_calendarlist');
  if (!empty($calendarList)) {
    $selectedCalendarIds = get_option('pgc_selected_calendar_ids'); // array
    if (empty($selectedCalendarIds)) {
      $selectedCalendarIds = [];
    }
  ?>
    <?php foreach ($calendarList as $calendar) { ?>
      <?php
      $calendarId = $calendar['id'];
      $htmlId = md5($calendarId);
      ?>
      <p class="pgc-calendar-filter">
        <input id="<?php echo $htmlId; ?>" type="checkbox" name="pgc_selected_calendar_ids[]" <?php if (in_array($calendarId, $selectedCalendarIds)) echo ' checked '; ?> value="<?php echo esc_attr($calendarId); ?>" />
        <label for="<?php echo $htmlId; ?>">
          <span class="pgc-calendar-color" style="background-color:<?php echo esc_attr($calendar['backgroundColor']); ?>"></span>
          <?php echo esc_html($calendar['summary']); ?><?php if (!empty($calendar['primary'])) echo ' (primary)'; ?>
        </label>
        <br>ID: <a tabindex="0" class="pgc-copy-text" title="<?php _e('Copy to clipboard', 'private-google-calendars'); ?>"><?php echo esc_html($calendarId); ?></a>
      </p>
    <?php } ?>
    </ul>
    <?php
    $refreshToken = get_option("pgc_refresh_token");
    if (empty($refreshToken)) {
      pgc_show_notice(PGC_ERRORS_REFRESH_TOKEN_MISSING, 'error', false);
    }
  } else {
    ?>
    <p><?php _e('No calendars yet.', 'private-google-calendars'); ?></p>
  <?php
  }
}


/**
 * Callback function to show client secret file input.
 */
function pgc_settings_client_secret_json_cb()
{

  $clientSecretError = '';
  $clientSecret = pgc_get_valid_client_secret($clientSecretError);
  $clientSecretString = '';

  if (!empty($clientSecret)) {
    $clientSecretString = getPrettyJSONString($clientSecret);
  }
  if (!empty($clientSecretError)) {
    pgc_show_notice($clientSecretError, 'error', false);
  }
  ?>
  <input type="file" name="pgc_client_secret" id="pgc_client_secret" />
  <?php
}

/**
 * Helper function to check if we have a valid redirect uri in the client secret.
 * @return bool
 */
function pgc_check_redirect_uri($decodedClientSecret)
{
  return !empty($decodedClientSecret)
    && !empty($decodedClientSecret['web'])
    && !empty($decodedClientSecret['web']['redirect_uris'])
    && in_array(admin_url('options-general.php?page=pgc'), $decodedClientSecret['web']['redirect_uris']);
}


/**
 * Helper function to return pretty printed JSON string.
 * @return string
 */
function getPrettyJSONString($jsonObject)
{
  return str_replace("    ", "  ", json_encode($jsonObject, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/**
 * Helper function to return array from option (that should be a JSON string).
 * @return array or $default = null
 */
function getDecoded($optionName, $default = null)
{
  $item = get_option($optionName);
  // $item should be a JSON string.
  if (!empty($item)) {
    return json_decode($item, true);
  }
  return $default;
}

/**
 * Helper function that returns a valid Google Client.
 * @return PGC_GoogleClient instance
 * @param bool $withTokens If true, also get tokens.
 * @throws Exception.
 */
function getGoogleClient($withTokens = false)
{

  // Apparently the init callback is not done when deleting a plugin and therefore the constants are not defined which causes a fatal error.
  // So at least make sure the constants are defined.
  if (!defined('PGC_PLUGIN_NAME')) {
    load_plugin_textdomain('private-google-calendars', FALSE, basename(dirname(__FILE__)) . '/languages/');
    initTranslatedDefines();
  }

  $authConfig = get_option('pgc_client_secret');
  if (empty($authConfig)) {
    throw new Exception(PGC_ERRORS_CLIENT_SECRET_MISSING);
  }
  $authConfig = getDecoded('pgc_client_secret');
  if (empty($authConfig)) {
    throw new Exception(PGC_ERRORS_CLIENT_SECRET_INVALID);
  }

  $c = new PGC_GoogleClient($authConfig);
  $c->setScope('https://www.googleapis.com/auth/calendar.readonly');
  if (!pgc_check_redirect_uri($authConfig)) {
    throw new Exception(sprintf(PGC_ERRORS_REDIRECT_URI_MISSING, admin_url('options-general.php?page=pgc')));
  }
  $c->setRedirectUri(admin_url('options-general.php?page=pgc'));
  $c->setTokenCallback(function ($accessTokenInfo, $refreshToken) {
    update_option('pgc_access_token', json_encode($accessTokenInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), false);
    if (!empty($refreshToken)) {
      update_option('pgc_refresh_token', $refreshToken, false);
    }
  });

  if ($withTokens) {
    $accessToken = getDecoded('pgc_access_token');
    if (empty($accessToken)) {
      throw new Exception(PGC_ERRORS_ACCESS_TOKEN_MISSING);
    }
    $c->setAccessTokenInfo($accessToken);
    $refreshToken = get_option("pgc_refresh_token");
    if (empty($refreshToken)) {
      throw new Exception(PGC_ERRORS_REFRESH_TOKEN_MISSING);
    }
    $c->setRefreshToken($refreshToken);
  }

  return $c;
}

/**
 * Get a valid formatted client secret.
 * @return Client Secret Array, false if no exists, Exception for invalid one
 **/
function pgc_get_valid_client_secret(&$error = '')
{
  $clientSecret = get_option('pgc_client_secret');
  if (empty($clientSecret)) {
    return false;
  }
  $clientSecret = getDecoded('pgc_client_secret');
  if (
    empty($clientSecret)
    || empty($clientSecret['web'])
    || empty($clientSecret['web']['client_secret'])
    || empty($clientSecret['web']['client_id'])
  ) {
    $error = PGC_ERRORS_CLIENT_SECRET_INVALID;
  } elseif (!pgc_check_redirect_uri($clientSecret)) {
    $error = sprintf(PGC_ERRORS_REDIRECT_URI_MISSING, admin_url('options-general.php?page=pgc'));
  }
  return $clientSecret;
}

/**
 * Add 'pgcnotice' to the removable_query_args filter, so we can set this and
 * WP will remove it for us. We use this for our custom admin notices. This way
 * you can add parameters to the URL and check for them, but we won't see them
 * in the URL. See for examples:
 * wp-admin/options-head.php and edit-form-advanced.php
 */
add_filter('removable_query_args', 'pgc_removable_query_args');
function pgc_removable_query_args($removable_query_args)
{
  $removable_query_args[] = 'pgcnotice';
  return $removable_query_args;
}

/**
 * Check for 'pgcnotice' parameter and show admin notice if we have a option.
 */
add_action('admin_init', 'pgc_notices_init');
function pgc_notices_init()
{
  if (!empty($_GET['pgcnotice'])) {
    $pgcnotices = get_option('pgc_notices_' . get_current_user_id());
    if (empty($pgcnotices)) {
      return;
    }
    delete_option('pgc_notices_' . get_current_user_id());
    add_action('admin_notices', function () use ($pgcnotices) {
      foreach ($pgcnotices as $notice) {
  ?>
        <div class="notice notice-<?php echo esc_attr($notice['type']); ?> is-dismissible">
          <p><?php echo $notice['content']; ?></p>
        </div>
  <?php
      }
    });
  }
}

/**
 * Helper function to add notice messages.
 * @param bool $redirect Redirect if true.
 */
function pgc_add_notice($content, $type = 'success', $redirect = false)
{
  $pgcnotices = get_option('pgc_notices_' . get_current_user_id());
  if (empty($pgcnotices)) {
    $pgcnotices = [];
  }
  $pgcnotices[] = [
    'content' => $content,
    'type' => $type
  ];
  update_option('pgc_notices_' . get_current_user_id(), $pgcnotices, false);
  if ($redirect) {
    wp_redirect(admin_url("options-general.php?page=pgc&pgcnotice=true"));
  }
}

/**
 * Helper function to show a notice. WP will move this message to the correct place.
 */
function pgc_show_notice($notice, $type, $dismissable)
{
  ?>
  <div class="notice notice-<?php echo esc_attr($type); ?> <?php echo $dismissable ? 'is-dismissible' : ''; ?>">
    <p><?php echo $notice; ?></p>
  </div>
  <?php
}

function pgc_get_default_fc_config()
{

  $fcVersion = get_option('pgc_fullcalendar_version', 4);
  return $fcVersion >= 5 ? [
    'headerToolbar' => [
      'start' => 'prev,next today',
      'center' => 'title',
      'end' => 'dayGridMonth,timeGridWeek,listWeek'
    ]
  ] : [
    'header' => [
      'left' => 'title',
      'center' => '',
      'right' => 'today prev,next',
    ],
  ];
}

class Pgc_Calendar_Widget extends WP_Widget
{

  private static $defaultConfig = null;

  private static function getDefaultConfig()
  {

    if (!empty($defaultConfig)) return $defaultConfig;

    $fcVersion = get_option('pgc_fullcalendar_version', 4);
    $defaultConfig = pgc_get_default_fc_config();

    return $defaultConfig;
  }

  public function __construct()
  {
    parent::__construct(
      'pgc_calender_widget', // Base ID
      __('Private Google Calendars Widget', 'private-google-calendars'),
      ['description' => __('Private Google Calendars Widget', 'private-google-calendars')]
    );
  }

  private function toBooleanString($value)
  {
    if ($value === 'true') return 'true';
    return 'false';
  }

  private function toBoolean($value)
  {
    if ($value === 'true') return true;
    return false;
  }

  private function instanceOptionToBooleanString($instance, $key, $defaultValue)
  {
    return isset($instance[$key]) ? $this->toBooleanString($instance[$key]) : $defaultValue;
  }

  public function widget($args, $instance)
  {

    $publicCalendarids = isset($instance['publiccalendarids']) ? $instance['publiccalendarids'] : "";
    $uncheckedCalendarids = isset($instance['uncheckedcalendarids']) ? $instance['uncheckedcalendarids'] : "";
    $filter = isset($instance['filter']) ? ($instance['filter'] === 'true' ? 'top' : $instance['filter']) : '';
    $userTheme = isset($instance['config']) ? $instance['theme'] : '';
    $eventpopup = $this->instanceOptionToBooleanString($instance, 'eventpopup', 'true');
    $eventlink = $this->instanceOptionToBooleanString($instance, 'eventlink', 'false');
    $eventdescription = $this->instanceOptionToBooleanString($instance, 'eventdescription', 'false');
    $eventlocation = $this->instanceOptionToBooleanString($instance, 'eventlocation', 'false');
    $eventattachments = $this->instanceOptionToBooleanString($instance, 'eventattachments', 'false');
    $eventattendees = $this->instanceOptionToBooleanString($instance, 'eventattendees', 'false');
    $eventcreator = $this->instanceOptionToBooleanString($instance, 'eventcreator', 'false');
    $eventcalendarname = $this->instanceOptionToBooleanString($instance, 'eventcalendarname', 'false');
    $hidepassed = $this->instanceOptionToBooleanString($instance, 'hidepassed', 'false');
    $hidepasseddays = empty($instance['hidepasseddays']) ? 0 : $instance['hidepasseddays'];
    $hidefuture = $this->instanceOptionToBooleanString($instance, 'hidefuture', 'false');
    $hidefuturedays = empty($instance['hidefuturedays']) ? 0 : $instance['hidefuturedays'];
    $config = isset($instance['config']) ? $instance['config'] : self::getDefaultConfig();

    // START FIX calids
    // Fix for old users who used the thiscalendarids property (no selection meant ALL private calendars)
    // Now we do the opposite: select calendars you want to display. No calendarids selected now means NO calendars.
    $privateCalendaridsNew = null;
    if (empty($publicCalendarids) && isset($instance['thiscalendarids'])) {
      $privateCalendaridsOld = $instance['thiscalendarids'];
      if (is_string($privateCalendaridsOld) && !empty($privateCalendaridsOld)) {
        $privateCalendaridsOld = json_decode($privateCalendaridsOld, true);
      }
      if (empty($privateCalendaridsOld)) {
        $privateCalendaridsOld = get_option('pgc_selected_calendar_ids', []);
      }
      $privateCalendaridsNew = $privateCalendaridsOld;
    } else {
      $privateCalendaridsNew = isset($instance['privatecalendarids']) ? $instance['privatecalendarids'] : [];
    }
    // END FIX calids

    if (is_string($config)) {
      $config = json_decode($config, true);
    }

    $activeTheme = pgc_get_current_theme($userTheme);

    $thisCalendarids = array_merge((!empty($publicCalendarids) ? array_map('trim', explode(',', $publicCalendarids)) : []), $privateCalendaridsNew);

    echo $args['before_widget'];

    $dataUnchekedCalendarIds = '';
    if (!empty($uncheckedCalendarids)) {
      $dataUnchekedCalendarIds = " data-uncheckedcalendarids='" . json_encode(array_map('trim', explode(',', $uncheckedCalendarids))) . "'";
    }
    $filterHTML = '<div class="pgc-calendar-filter"' . $dataUnchekedCalendarIds . '></div>';

  ?>
    <div class="pgc-calendar-wrapper pgc-calendar-widget <?php echo pgc_wrap_in_theme_class($activeTheme); ?>">
      <?php if ($filter === 'top') echo $filterHTML; ?>
      <div data-config='<?php echo json_encode($config); ?>' data-calendarids='<?php echo json_encode($thisCalendarids); ?>' data-filter='<?php echo $filter; ?>' data-theme='<?php echo $activeTheme; ?>' data-eventpopup='<?php echo $eventpopup; ?>' data-eventlink='<?php echo $eventlink; ?>' data-eventdescription='<?php echo $eventdescription; ?>' data-eventlocation='<?php echo $eventlocation; ?>' data-eventattendees='<?php echo $eventattendees; ?>' data-eventattachments='<?php echo $eventattachments; ?>' data-eventcreator='<?php echo $eventcreator; ?>' data-eventcalendarname='<?php echo $eventcalendarname; ?>' data-hidepassed='<?php echo $hidepassed === 'true' ? $hidepasseddays : 'false'; ?>' data-hidefuture='<?php echo $hidefuture === 'true' ? $hidefuturedays : 'false'; ?>' data-locale='<?php echo get_locale(); ?>' class="pgc-calendar"></div>
      <?php if ($filter === 'bottom') echo $filterHTML; ?>
    </div>
  <?php

    echo $args['after_widget'];
  }

  public function form($instance)
  {

    $publicCalendarids = isset($instance['publiccalendarids']) ? $instance['publiccalendarids'] : '';
    $uncheckedCalendarids = isset($instance['uncheckedcalendarids']) ? $instance['uncheckedcalendarids'] : '';

    $filterValue = isset($instance['filter']) ? ($instance['filter'] === 'true' ? 'top' : $instance['filter']) : '';
    $themeValue = isset($instance['theme']) ? $instance['theme'] : '';
    $eventpopupValue = isset($instance['eventpopup']) ? $instance['eventpopup'] === 'true' : true;
    $eventlinkValue = isset($instance['eventlink']) ? $instance['eventlink'] === 'true' : false;
    $eventdescriptionValue = isset($instance['eventdescription']) ? $instance['eventdescription'] === 'true' : false;

    $eventlocationValue = isset($instance['eventlocation']) ? $instance['eventlocation'] === 'true' : false;
    $eventattachmentsValue = isset($instance['eventattachments']) ? $instance['eventattachments'] === 'true' : false;
    $eventattendeesValue = isset($instance['eventattendees']) ? $instance['eventattendees'] === 'true' : false;
    $eventcreatorValue = isset($instance['eventcreator']) ? $instance['eventcreator'] === 'true' : false;
    $eventcalendarnameValue = isset($instance['eventcalendarname']) ? $instance['eventcalendarname'] === 'true' : false;
    $hidepassedValue = isset($instance['hidepassed']) ? $instance['hidepassed'] === 'true' : false;
    $hidepasseddaysValue = empty($instance['hidepasseddays']) ? 0 : $instance['hidepasseddays'];
    $hidefutureValue = isset($instance['hidefuture']) ? $instance['hidefuture'] === 'true' : false;
    $hidefuturedaysValue = empty($instance['hidefuturedays']) ? 0 : $instance['hidefuturedays'];

    $jsonValue = !empty($instance['config']) ? $instance['config'] : self::getDefaultConfig();

    $allCalendarIds = get_option('pgc_selected_calendar_ids', []); // selected calendar ids
    // Can also be an empty string.
    if (empty($allCalendarIds)) {
      $allCalendarIds = [];
    }
    $calendarListByKey = pgc_get_calendars_by_key($allCalendarIds);

    $privateCalendaridsValue = isset($instance['privatecalendarids']) ? $instance['privatecalendarids'] : [];

    $popupCheckboxId = $this->get_field_id('eventpopup');
    $hidepassedCheckboxId = $this->get_field_id('hidepassed');
    $hidefutureCheckboxId = $this->get_field_id('hidefuture');
    $publicCalendarIdsAreaId = $this->get_field_id('publiccalendarids');
    $privateCalendarIdsName = $this->get_field_name('privatecalendarids');

    $themes = [];
    if (get_option('pgc_fullcalendar_version') >= 5) {
      $themes = pgc_get_themes();
      $themes = array_map(function ($theme) use ($themeValue) {
        return '<option value="' . $theme . '" ' . selected($themeValue, $theme, false) . '>' . ucfirst($theme) . '</option>';
      }, $themes);
    }

  ?>

    <script>
      window.onPgcPopupCheckboxClick = function(el) {
        el = el || this;
        var checked = el.checked;
        Array.prototype.forEach.call(document.querySelectorAll("input[data-linked-id='" + el.id + "']"), function(input) {
          if (checked) {
            input.removeAttribute("disabled");
          } else {
            input.setAttribute("disabled", "disabled");
          }
        });
      };

      window.onHidefuturePassedCheckboxClick = function(input) {
        input = input || this;
        var el = document.querySelector("label[data-linked-id='" + input.id + "']");
        if (input.checked) {
          el.style.display = 'block';
        } else {
          el.style.display = 'none';
        }
      };
    </script>

    <p>
      <strong class="pgc-calendar-widget-row"><?php _e('Private calendars', 'private-google-calendars'); ?></strong>
      <?php if (empty($calendarListByKey)) { ?>
        <em><?php _e('No private calendars', 'private-google-calendars'); ?></em>
    </p>
  <?php } else { ?>
    <?php foreach ($calendarListByKey as $calId => $calInfo) { ?>
      <label class="pgc-calendar-widget-row">
        <input type="checkbox" class="pgc_widget_private_calendar" <?php checked(in_array($calId, $privateCalendaridsValue), true, true); ?> name="<?php echo $privateCalendarIdsName; ?>[]" value="<?php echo $calId; ?>" />
        <?php echo $calInfo['summary']; ?></label>
    <?php } ?>
    <em><?php _e('Note: only selected calendars will be displayed', 'private-google-calendars'); ?></em></p>
  <?php } ?>


  <p>
    <label class="pgc-calendar-widget-row"><?php _e('Enter comma separated list of public calendar ID\'s:', 'private-google-calendars'); ?><br>
      <textarea class="widefat pgc-calendar-codearea pgc-calendar-widget-row" name="<?php echo $this->get_field_name('publiccalendarids'); ?>" id="<?php echo $publicCalendarIdsAreaId; ?>"><?php echo esc_html($publicCalendarids); ?></textarea></label>
  </p>

  <p>
    <strong class="pgc-calendar-widget-row"><?php _e('Calendar options', 'private-google-calendars'); ?></strong>

    <label class="pgc-calendar-widget-row" for="<?php echo $hidepassedCheckboxId; ?>">
      <input type="checkbox" <?php checked($hidepassedValue, true, true); ?> id="<?php echo $hidepassedCheckboxId; ?>" name="<?php echo $this->get_field_name('hidepassed'); ?>" onclick="window.onHidefuturePassedCheckboxClick(this);" value="true" />
      <?php _e('Hide passed events...'); ?></label>
    <label class="pgc-calendar-widget-row" data-linked-id="<?php echo $hidepassedCheckboxId; ?>"><?php _e('...more than', 'private-google-calendars'); ?> <input min="0" class="pgc_small_numeric_input" type="number" name="<?php echo $this->get_field_name('hidepasseddays'); ?>" id="<?php echo $this->get_field_id('hidepasseddays'); ?>" value="<?php echo $hidepasseddaysValue; ?>" /> <?php _e('days ago', 'private-google-calendars'); ?></label>
    <label class="pgc-calendar-widget-row" for="<?php echo $hidefutureCheckboxId; ?>"><input type="checkbox" <?php checked($hidefutureValue, true, true); ?> id="<?php echo $hidefutureCheckboxId; ?>" name="<?php echo $this->get_field_name('hidefuture'); ?>" onclick="window.onHidefuturePassedCheckboxClick(this);" value="true" />
      <?php _e('Hide future events...'); ?></label>
    <label class="pgc-calendar-widget-row" data-linked-id="<?php echo $hidefutureCheckboxId; ?>"><?php _e('...more than', 'private-google-calendars'); ?> <input min="0" class="pgc_small_numeric_input" type="number" name="<?php echo $this->get_field_name('hidefuturedays'); ?>" id="<?php echo $this->get_field_id('hidefuturedays'); ?>" value="<?php echo $hidefuturedaysValue; ?>" /> <?php _e('from now', 'private-google-calendars'); ?></label>
  </p>

  <?php if (!empty($themes)) { ?>
    <p>
      <strong class="pgc-calendar-widget-row"><?php _e('Theme', 'private-google-calendars'); ?></strong>
      <label><select id="<?php echo $this->get_field_id('theme'); ?>" name="<?php echo $this->get_field_name('theme'); ?>">
          <option value=''><?php _e('Default', 'private-google-calendars'); ?></option>
          <?php echo implode("\n", $themes); ?>
        </select></label>
    </p>
  <?php } ?>

  <p>
    <strong class="pgc-calendar-widget-row"><?php _e('Filter options', 'private-google-calendars'); ?></strong>
    <label><select id="<?php echo $this->get_field_id('filter'); ?>" name="<?php echo $this->get_field_name('filter'); ?>">
        <option value=''><?php _e('Hide filter', 'private-google-calendars'); ?></option>
        <option <?php selected($filterValue, 'top', true); ?> value='top'><?php _e('Show filter at top', 'private-google-calendars'); ?></option>
        <option <?php selected($filterValue, 'bottom', true); ?> value='bottom'><?php _e('Show filter at bottom', 'private-google-calendars'); ?></option>
      </select></label>
  </p>
  <p><label class="pgc-calendar-widget-row" for="<?php echo $this->get_field_id('uncheckedcalendarids'); ?>"><?php _e('Unchecked calendar IDs:', 'private-google-calendars'); ?>
      <textarea class="widefat pgc-calendar-codearea pgc-calendar-widget-row" name="<?php echo $this->get_field_name('uncheckedcalendarids'); ?>" id="<?php echo $this->get_field_id('uncheckedcalendarids'); ?>"><?php echo esc_html($uncheckedCalendarids); ?></textarea>
    </label>
  </p>

  <p>
    <strong class="pgc-calendar-widget-row"><?php _e('Event popup options', 'private-google-calendars'); ?></strong>
    <label class="pgc-calendar-widget-row" for="<?php echo $popupCheckboxId; ?>"><input type="checkbox" <?php checked($eventpopupValue, true, true); ?> id="<?php echo $popupCheckboxId; ?>" name="<?php echo $this->get_field_name('eventpopup'); ?>" value="true" onclick="window.onPgcPopupCheckboxClick(this);" />
      <?php _e('Show event popup', 'private-google-calendars'); ?></label>

    <label class="pgc-calendar-widget-row" for="<?php echo $this->get_field_id('eventlink'); ?>"><input data-linked-id="<?php echo $popupCheckboxId; ?>" type="checkbox" <?php checked($eventlinkValue, true, true); ?> id="<?php echo $this->get_field_id('eventlink'); ?>" name="<?php echo $this->get_field_name('eventlink'); ?>" value="true" />
      <?php _e('Show link to event in popup', 'private-google-calendars'); ?></label>

    <label class="pgc-calendar-widget-row" for="<?php echo $this->get_field_id('eventdescription'); ?>"><input data-linked-id="<?php echo $popupCheckboxId; ?>" type="checkbox" <?php checked($eventdescriptionValue, true, true); ?> id="<?php echo $this->get_field_id('eventdescription'); ?>" name="<?php echo $this->get_field_name('eventdescription'); ?>" value="true" />
      <?php _e('Show description in popup', 'private-google-calendars'); ?></label>

    <label class="pgc-calendar-widget-row" for="<?php echo $this->get_field_id('eventlocation'); ?>"><input data-linked-id="<?php echo $popupCheckboxId; ?>" type="checkbox" <?php checked($eventlocationValue, true, true); ?> id="<?php echo $this->get_field_id('eventlocation'); ?>" name="<?php echo $this->get_field_name('eventlocation'); ?>" value="true" />
      <?php _e('Show location in popup', 'private-google-calendars'); ?></label>

    <label class="pgc-calendar-widget-row" for="<?php echo $this->get_field_id('eventattachments'); ?>"><input data-linked-id="<?php echo $popupCheckboxId; ?>" type="checkbox" <?php checked($eventattachmentsValue, true, true); ?> id="<?php echo $this->get_field_id('eventattachments'); ?>" name="<?php echo $this->get_field_name('eventattachments'); ?>" value="true" />
      <?php _e('Show attachments in popup', 'private-google-calendars'); ?></label>

    <label class="pgc-calendar-widget-row" for="<?php echo $this->get_field_id('eventattendees'); ?>"><input data-linked-id="<?php echo $popupCheckboxId; ?>" type="checkbox" <?php checked($eventattendeesValue, true, true); ?> id="<?php echo $this->get_field_id('eventattendees'); ?>" name="<?php echo $this->get_field_name('eventattendees'); ?>" value="true" />
      <?php _e('Show attendees in popup', 'private-google-calendars'); ?></label>

    <label class="pgc-calendar-widget-row" for="<?php echo $this->get_field_id('eventcalendarname'); ?>"><input data-linked-id="<?php echo $popupCheckboxId; ?>" type="checkbox" <?php checked($eventcalendarnameValue, true, true); ?> id="<?php echo $this->get_field_id('eventcalendarname'); ?>" name="<?php echo $this->get_field_name('eventcalendarname'); ?>" value="true" />
      <?php _e('Show calendar name in popup', 'private-google-calendars'); ?></label>

    <label class="pgc-calendar-widget-row" for="<?php echo $this->get_field_id('eventcreator'); ?>"><input data-linked-id="<?php echo $popupCheckboxId; ?>" type="checkbox" <?php checked($eventcreatorValue, true, true); ?> id="<?php echo $this->get_field_id('eventcreator'); ?>" name="<?php echo $this->get_field_name('eventcreator'); ?>" value="true" />
      <?php _e('Show creator in popup', 'private-google-calendars'); ?></label>
  </p>

  <?php

    $jsonExample = self::getDefaultConfig();

    $jsonValueTextarea = '';
    if (is_array($jsonValue)) {
      $jsonValueTextarea  = getPrettyJSONString($jsonValue);
    } else {
      $jsonValueTextarea = $jsonValue;
    }

  ?>
  <p>
    <label class="pgc-calendar-widget-row" for="<?php echo $this->get_field_id('config'); ?>"><?php _e('FullCalendar JSON config:'); ?></label>
    <textarea name="<?php echo $this->get_field_name('config'); ?>" id="<?php echo $this->get_field_id('config'); ?>" class="widefat pgc-calendar-codearea pgc-calendar-widget-row" rows="10" placeholder='<?php echo esc_attr(getPrettyJSONString($jsonExample)); ?>'><?php echo esc_html($jsonValueTextarea); ?></textarea>

    <?php printf(__('See for config options the <a target="__blank" href="%s">FullCalendar docs</a>.', 'private-google-calendars'), 'https://fullcalendar.io/docs/'); ?>
  </p>
  <script>
    (function($) {

      window.onPgcPopupCheckboxClick.call(document.getElementById("<?php echo $popupCheckboxId; ?>"));
      window.onHidefuturePassedCheckboxClick.call(document.getElementById("<?php echo $hidepassedCheckboxId; ?>"));
      window.onHidefuturePassedCheckboxClick.call(document.getElementById("<?php echo $hidefutureCheckboxId; ?>"));

      var publicCalendarIdsArea = document.getElementById("<?php echo $publicCalendarIdsAreaId; ?>");

      // Note that form() is called 2 times in the widget area: ont time closed
      // and one time opened if you have it in your sidebar.
      var $area = $("#<?php echo $this->get_field_id('config'); ?>");
      var area = $area[0];
      var $form = $area.closest("form");
      // Does not work, so no real submit maybe?
      //$form.submit(function(e) {
      //  e.preventDefault();
      //  return false;
      //});

      $form[0].onclick = function(e) {

        var target = e.target;
        if (target.nodeName.toLowerCase() !== "input" || target.type !== "submit") {
          return;
        }
        if (!checkAreaJSON()) {
          e.stopPropagation();
          e.preventDefault();
          alert("<?php _e("Invalid JSON. Solve it before saving.", 'private-google-calendars'); ?>");
          return false;
        }

        if (document.querySelectorAll("input[name='<?php echo $privateCalendarIdsName; ?>[]']:checked").length === 0 && !document.getElementById("<?php echo $publicCalendarIdsAreaId; ?>").value) {
          e.stopPropagation();
          e.preventDefault();
          alert("<?php _e("No calendars checked or entered", 'private-google-calendars'); ?>");
          return false;
        }

      };

      var checkAreaJSON = function() {
        if (area.value === '') {
          area.style.outline = "2px solid green";
          return true;
        }
        try {
          JSON.parse(area.value);
          area.style.outline = "2px solid green";
          return true;
        } catch (ex) {
          area.style.outline = "3px solid red";
          return false;
        }
      };

      $area.on("input propertychange change", function() {
        checkAreaJSON(this);
      });

      $area.on("keydown", function(e) {
        if (e.keyCode == 9 || e.which == 9) {
          var start = this.selectionStart;
          var value = this.value;
          this.value = value.substring(0, start) +
            "    " +
            value.substring(this.selectionEnd);
          this.selectionStart = this.selectionEnd = start + 2;
          e.preventDefault();
        }
      });

      checkAreaJSON();

    }(jQuery));
  </script>
<?php
  }

  public function update($new_instance, $old_instance)
  {
    $instance = [];
    $instance['config'] = (!empty($new_instance['config']))
      ? $new_instance['config']
      : getPrettyJSONString(self::getDefaultConfig());
    $instance['filter'] = (!empty($new_instance['filter']))
      ? strip_tags($new_instance['filter'])
      : '';
    $instance['publiccalendarids'] = (!empty($new_instance['publiccalendarids']))
      ? strip_tags($new_instance['publiccalendarids'])
      : '';
    $instance['theme'] = (!empty($new_instance['theme']))
      ? strip_tags($new_instance['theme'])
      : '';
    $instance['uncheckedcalendarids'] = (!empty($new_instance['uncheckedcalendarids']))
      ? strip_tags($new_instance['uncheckedcalendarids'])
      : '';
    $instance['eventpopup'] = (!empty($new_instance['eventpopup']))
      ? strip_tags($new_instance['eventpopup'])
      : '';
    $instance['eventlink'] = (!empty($new_instance['eventlink']))
      ? strip_tags($new_instance['eventlink'])
      : '';
    $instance['eventdescription'] = (!empty($new_instance['eventdescription']))
      ? strip_tags($new_instance['eventdescription'])
      : '';
    $instance['eventlocation'] = (!empty($new_instance['eventlocation']))
      ? strip_tags($new_instance['eventlocation'])
      : '';
    $instance['eventattachments'] = (!empty($new_instance['eventattachments']))
      ? strip_tags($new_instance['eventattachments'])
      : '';
    $instance['eventattendees'] = (!empty($new_instance['eventattendees']))
      ? strip_tags($new_instance['eventattendees'])
      : '';
    $instance['eventcreator'] = (!empty($new_instance['eventcreator']))
      ? strip_tags($new_instance['eventcreator'])
      : '';
    $instance['eventcalendarname'] = (!empty($new_instance['eventcalendarname']))
      ? strip_tags($new_instance['eventcalendarname'])
      : '';
    // START FIX calids
    // Note: before we used thiscalendarids, after saving this widget, thiscalendarids is removed from the widget
    // Handled in the widget() function.
    // Basically this means that old user won't be affected by this, but after saving the widget, they have to select the private calendards to be used.
    $instance['privatecalendarids'] = (!empty($new_instance['privatecalendarids']))
      ? $new_instance['privatecalendarids']
      : [];
    // END FIX calids
    $instance['hidepassed'] = (!empty($new_instance['hidepassed']))
      ? strip_tags($new_instance['hidepassed'])
      : '';
    $instance['hidepasseddays'] = (!empty($new_instance['hidepasseddays']))
      ? strip_tags($new_instance['hidepasseddays'])
      : '0';
    $instance['hidefuture'] = (!empty($new_instance['hidefuture']))
      ? strip_tags($new_instance['hidefuture'])
      : '';
    $instance['hidefuturedays'] = (!empty($new_instance['hidefuturedays']))
      ? strip_tags($new_instance['hidefuturedays'])
      : '0';
    return $instance;
  }
}
add_action('widgets_init', function () {
  register_widget('Pgc_Calendar_Widget');
});
