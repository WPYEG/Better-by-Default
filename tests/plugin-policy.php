<?php
/**
 * Lightweight regression tests for Better by Default policies.
 *
 * @package BetterByDefault
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );

$GLOBALS['wpyeg_test_hooks']         = array();
$GLOBALS['wpyeg_test_filter_values'] = array();
$GLOBALS['wpyeg_test_users']         = array();
$GLOBALS['wpyeg_test_current_user']  = 0;
$GLOBALS['wpyeg_test_transients']    = array();
$GLOBALS['wpyeg_test_did_actions']   = array();

/**
 * Translation test double.
 *
 * @param string $text Source text.
 * @return string
 */
function __( $text ) { // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
	return $text;
}

/**
 * Option test double.
 *
 * Returns the schema defaults unless a test has staged a stored option, which
 * is how the policies that ship disabled get exercised.
 *
 * @param string $name          Option name.
 * @param mixed  $default_value Default value.
 * @return mixed
 */
function get_option( $name, $default_value = false ) {
	unset( $name );
	return isset( $GLOBALS['wpyeg_test_option'] ) ? $GLOBALS['wpyeg_test_option'] : $default_value;
}

/**
 * Current-user test double.
 *
 * @return bool
 */
function is_user_logged_in() {
	return (bool) $GLOBALS['wpyeg_test_current_user'];
}

/**
 * Current-user-id test double.
 *
 * @return int
 */
function get_current_user_id() {
	return (int) $GLOBALS['wpyeg_test_current_user'];
}

/**
 * User-lookup test double.
 *
 * @param int $user_id User ID.
 * @return object|false
 */
function get_userdata( $user_id ) {
	return isset( $GLOBALS['wpyeg_test_users'][ $user_id ] ) ? $GLOBALS['wpyeg_test_users'][ $user_id ] : false;
}

/**
 * Action-registration test double.
 *
 * @param string   $hook          Hook name.
 * @param callable $callback      Callback.
 * @param int      $priority      Hook priority.
 * @param int      $accepted_args Accepted argument count.
 */
function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['wpyeg_test_hooks'][] = array(
		'type'          => 'action',
		'hook'          => $hook,
		'callback'      => $callback,
		'priority'      => $priority,
		'accepted_args' => $accepted_args,
	);
}

/**
 * Filter-registration test double.
 *
 * @param string   $hook          Hook name.
 * @param callable $callback      Callback.
 * @param int      $priority      Hook priority.
 * @param int      $accepted_args Accepted argument count.
 */
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['wpyeg_test_hooks'][] = array(
		'type'          => 'filter',
		'hook'          => $hook,
		'callback'      => $callback,
		'priority'      => $priority,
		'accepted_args' => $accepted_args,
	);
}

/**
 * Hook-removal test double.
 *
 * @param string   $hook     Hook name.
 * @param callable $callback Callback.
 * @param int      $priority Hook priority.
 */
function remove_action( $hook, $callback, $priority = 10 ) {
	unset( $hook, $callback, $priority );
}

/**
 * Action-dispatch test double.
 *
 * @param string $hook Hook name.
 */
function do_action( $hook ) {
	$GLOBALS['wpyeg_test_did_actions'][] = $hook;
}

/**
 * Activation-hook test double.
 *
 * @param string   $file     Plugin file.
 * @param callable $callback Activation callback.
 */
function register_activation_hook( $file, $callback ) {
	unset( $file, $callback );
}

/**
 * Filter test double.
 *
 * Returns the unfiltered value unless a test has staged an override in
 * $GLOBALS['wpyeg_test_filter_values'], which stands in for a site adding its
 * own filter callback.
 *
 * @param string $hook  Hook name.
 * @param mixed  $value Filtered value.
 * @return mixed
 */
function apply_filters( $hook, $value ) {
	if ( isset( $GLOBALS['wpyeg_test_filter_values'][ $hook ] ) ) {
		return $GLOBALS['wpyeg_test_filter_values'][ $hook ];
	}
	return $value;
}

/*
 * Admin-rendering doubles.
 *
 * Just enough of the escaping and form helpers to render the settings screen
 * into a string. They are faithful to what matters here — escaping actually
 * escapes, and checked()/selected()/disabled() emit real attributes — so the
 * markup assertions further down mean something rather than checking that a
 * template file contains a particular sequence of characters.
 */

/**
 * Escaping test double.
 *
 * @param string $text Text to escape.
 * @return string
 */
function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}

/**
 * Attribute-escaping test double.
 *
 * @param string $text Text to escape.
 * @return string
 */
function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}

/**
 * Translate-and-escape test double.
 *
 * @param string $text   Source text.
 * @param string $domain Text domain.
 * @return string
 */
function esc_html__( $text, $domain = null ) {
	unset( $domain );
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}

/**
 * Translate-escape-and-echo test double.
 *
 * @param string $text   Source text.
 * @param string $domain Text domain.
 * @return void
 */
function esc_html_e( $text, $domain = null ) {
	unset( $domain );
	echo esc_html( $text );
}

/**
 * Markup-filtering test double. The allowlist itself is asserted separately.
 *
 * @param string $markup    Markup to filter.
 * @param array  $allowed   Allowed tags.
 * @return string
 */
function wp_kses( $markup, $allowed ) {
	unset( $allowed );
	return (string) $markup;
}

/**
 * Checkbox-state test double.
 *
 * @param mixed $helper  Value to compare.
 * @param mixed $current Current value.
 * @param bool  $display Whether to echo.
 * @return string
 */
function checked( $helper, $current = true, $display = true ) {
	$markup = ( (string) $helper === (string) $current ) ? ' checked="checked"' : '';
	if ( $display ) {
		echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed literal.
	}
	return $markup;
}

/**
 * Option-state test double.
 *
 * @param mixed $helper  Value to compare.
 * @param mixed $current Current value.
 * @param bool  $display Whether to echo.
 * @return string
 */
function selected( $helper, $current = true, $display = true ) {
	$markup = ( (string) $helper === (string) $current ) ? ' selected="selected"' : '';
	if ( $display ) {
		echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed literal.
	}
	return $markup;
}

/**
 * Disabled-state test double.
 *
 * @param mixed $helper  Value to compare.
 * @param mixed $current Current value.
 * @param bool  $display Whether to echo.
 * @return string
 */
function disabled( $helper, $current = true, $display = true ) {
	$markup = ( $helper === $current ) ? ' disabled="disabled"' : '';
	if ( $display ) {
		echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed literal.
	}
	return $markup;
}

/**
 * Settings-form-fields test double.
 *
 * @param string $group Option group.
 * @return void
 */
function settings_fields( $group ) {
	echo '<!--settings-fields:' . esc_html( $group ) . '-->';
}

/**
 * Submit-button test double.
 *
 * @return void
 */
function submit_button() {
	echo '<!--submit-button-->';
}

/**
 * Multisite test double.
 *
 * @return bool
 */
function is_multisite() {
	return ! empty( $GLOBALS['wpyeg_test_multisite'] );
}

/**
 * Super Admin test double.
 *
 * Only consulted on multisite by the code under test: on single site
 * is_super_admin() calls has_cap(), which would re-enter the user_has_cap filter
 * this runs inside and recurse until the stack blows.
 *
 * @param int $user_id User ID.
 * @return bool
 */
function is_super_admin( $user_id = 0 ) {
	$super = isset( $GLOBALS['wpyeg_test_super_admins'] ) ? (array) $GLOBALS['wpyeg_test_super_admins'] : array();
	return in_array( (int) $user_id, array_map( 'intval', $super ), true );
}

/**
 * Capability test double.
 *
 * @param string $capability Capability being checked.
 * @return bool
 */
function current_user_can( $capability ) {
	unset( $capability );
	return isset( $GLOBALS['wpyeg_test_can'] ) ? (bool) $GLOBALS['wpyeg_test_can'] : true;
}

/**
 * Environment-type test double.
 *
 * The reason the decision this feeds is a separate function: a real local site
 * reports 'local' and no in-process override changes that, so the production
 * branch is only reachable with a stub.
 *
 * @return string
 */
function wp_get_environment_type() {
	return isset( $GLOBALS['wpyeg_test_environment'] ) ? $GLOBALS['wpyeg_test_environment'] : 'production';
}

/**
 * URL-parsing test double.
 *
 * @param string $url       URL to parse.
 * @param int    $component Component constant.
 * @return mixed
 */
function wp_parse_url( $url, $component = -1 ) {
	return wp_parse_url_shim( $url, $component );
}

/**
 * Thin wrapper so the double above stays a one-liner.
 *
 * @param string $url       URL to parse.
 * @param int    $component Component constant.
 * @return mixed
 */
function wp_parse_url_shim( $url, $component ) {
	$parsed = parse_url( $url ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Test double standing in for wp_parse_url().
	if ( -1 === $component ) {
		return $parsed;
	}
	if ( PHP_URL_HOST === $component ) {
		return isset( $parsed['host'] ) ? $parsed['host'] : null;
	}
	return null;
}

/**
 * Home-URL test double. The host is what the deliverability check reads.
 *
 * @return string
 */
function network_home_url() {
	return isset( $GLOBALS['wpyeg_test_home_url'] ) ? $GLOBALS['wpyeg_test_home_url'] : 'https://example.org/';
}

/**
 * Email-validation test double.
 *
 * @param string $email Candidate address.
 * @return bool
 */
function is_email( $email ) {
	return (bool) filter_var( (string) $email, FILTER_VALIDATE_EMAIL );
}

/**
 * Slash-stripping test double.
 *
 * @param mixed $value Input value.
 * @return mixed
 */
function wp_unslash( $value ) {
	return is_string( $value ) ? stripslashes( $value ) : $value;
}

/**
 * Integer sanitizer test double.
 *
 * @param mixed $value Input value.
 * @return int
 */
function absint( $value ) {
	return abs( (int) $value );
}

/**
 * Test whether a value is an error.
 *
 * @param mixed $value Input value.
 * @return bool
 */
function is_wp_error( $value ) {
	return $value instanceof WP_Error;
}

/**
 * Transient test double. The breach check caches each HIBP range response.
 *
 * @param string $key Transient key.
 * @return mixed
 */
function get_transient( $key ) {
	return isset( $GLOBALS['wpyeg_test_transients'][ $key ] ) ? $GLOBALS['wpyeg_test_transients'][ $key ] : false;
}

/**
 * Transient setter test double.
 *
 * @param string $key   Transient key.
 * @param mixed  $value Value to store.
 * @param int    $ttl   Ignored.
 * @return bool
 */
function set_transient( $key, $value, $ttl = 0 ) {
	unset( $ttl );
	$GLOBALS['wpyeg_test_transients'][ $key ] = $value;
	return true;
}

/**
 * Transient deleter test double.
 *
 * The breach check clears a cached range response that no longer validates, so
 * the next call refetches instead of reusing bad data for the rest of its TTL.
 *
 * @param string $key Transient key.
 * @return bool
 */
function delete_transient( $key ) {
	unset( $GLOBALS['wpyeg_test_transients'][ $key ] );
	return true;
}

/**
 * HTTP test double.
 *
 * Tests stage $GLOBALS['wpyeg_test_http'] to control what the Have I Been Pwned
 * range endpoint returns. The suite never touches the network: unstaged calls
 * answer with an empty 200, i.e. "this prefix matched nothing".
 *
 * @param string $url  Requested URL.
 * @param array  $args Request arguments.
 * @return array|WP_Error
 */
function wp_remote_get( $url, $args = array() ) {
	$GLOBALS['wpyeg_test_last_http_url']  = $url;
	$GLOBALS['wpyeg_test_last_http_args'] = $args;

	return isset( $GLOBALS['wpyeg_test_http'] )
		? $GLOBALS['wpyeg_test_http']
		: array(
			'response' => array( 'code' => 200 ),
			'body'     => '',
		);
}

/**
 * Response-code test double.
 *
 * @param array|WP_Error $response HTTP response.
 * @return int
 */
function wp_remote_retrieve_response_code( $response ) {
	return isset( $response['response']['code'] ) ? $response['response']['code'] : 0;
}

/**
 * Response-body test double.
 *
 * @param array|WP_Error $response HTTP response.
 * @return string
 */
function wp_remote_retrieve_body( $response ) {
	return isset( $response['body'] ) ? $response['body'] : '';
}

/**
 * Home-URL test double.
 *
 * Present so a regression that sends attachment pages back to the homepage
 * fails as an assertion rather than an undefined-function fatal.
 *
 * @param string $path Optional path.
 * @return string
 */
function home_url( $path = '/' ) {
	return 'https://example.test' . $path;
}

/**
 * Template-lookup test double.
 *
 * @param array $templates Template names.
 * @return string Path when the theme provides one, '' otherwise.
 */
function locate_template( $templates ) {
	unset( $templates );
	return isset( $GLOBALS['wpyeg_test_template'] ) ? $GLOBALS['wpyeg_test_template'] : '';
}

/**
 * Post-parent test double.
 *
 * @param int $post_id Post ID.
 * @return int
 */
function wp_get_post_parent_id( $post_id ) {
	unset( $post_id );
	return isset( $GLOBALS['wpyeg_test_parent'] ) ? (int) $GLOBALS['wpyeg_test_parent'] : 0;
}

/**
 * Permalink test double.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function get_permalink( $post_id ) {
	return 'https://example.test/parent-post-' . (int) $post_id . '/';
}

/**
 * Attachment-URL test double.
 *
 * @param int $attachment_id Attachment ID.
 * @return string
 */
function wp_get_attachment_url( $attachment_id ) {
	unset( $attachment_id );
	return isset( $GLOBALS['wpyeg_test_file_url'] ) ? $GLOBALS['wpyeg_test_file_url'] : 'https://example.test/wp-content/uploads/photo.jpg';
}

require_once __DIR__ . '/class-wp-error.php';
require_once __DIR__ . '/class-wp-rest-request.php';
require_once dirname( __DIR__ ) . '/plugin/sane-defaults/sane-defaults.php';

/**
 * Assert a condition or stop.
 *
 * @param bool   $condition Assertion condition.
 * @param string $message   Failure message.
 */
function wpyeg_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "Assertion failed: {$message}\n" );
		exit( 1 );
	}
}

$schema = wpyeg_defaults_schema();
wpyeg_test_assert( 'no' === $schema['disable_application_passwords']['default'], 'Application Passwords remain available by default.' );
wpyeg_test_assert( 'minor' === $schema['core_update_policy']['default'], 'Core maintenance and security releases update automatically by default.' );
wpyeg_test_assert( ! isset( $schema['auto_update_translations'] ), 'WordPress retains ownership of translation-file updates.' );
wpyeg_test_assert(
	array(
		'code' => array(),
		'a'    => array( 'href' => true ),
	) === wpyeg_defaults_help_allowed_html(),
	'Help text permits only attribute-free code markup and href-only reference links.'
);
wpyeg_test_assert( false !== strpos( $schema['xmlrpc_allow_pingbacks']['help'], '<code>pingback.ping</code>' ), 'Machine-facing XML-RPC identifiers use code markup.' );

/*
 * The protocol detail moved out of the field help and into the reference doc
 * and readme.txt — see "Where password copy goes" in the feature matrix. The
 * assertions moved with it. A moved sentence with a deleted test is how a
 * disclosure quietly disappears on the next edit.
 */
$wpyeg_password_reference = file_get_contents( dirname( __DIR__ ) . '/docs/wordpress-default-settings.md' );
$wpyeg_plugin_readme      = file_get_contents( dirname( __DIR__ ) . '/plugin/sane-defaults/readme.txt' );

wpyeg_test_assert( false !== strpos( $wpyeg_password_reference, 'NIST SP 800-63B-4 § 3.1.1.2' ), 'External guidance names the specific publication and section.' );
wpyeg_test_assert( false !== strpos( $wpyeg_password_reference, 'https://pages.nist.gov/800-63-4/sp800-63b/authenticators/#passwordver' ), 'External guidance links to its authoritative source section.' );
wpyeg_test_assert( false !== strpos( $wpyeg_password_reference, 'https://haveibeenpwned.com/API/v3#SearchingPwnedPasswordsByRange' ), 'The breach-screening explanation links to the authoritative HIBP range API documentation.' );
wpyeg_test_assert( false !== strpos( $wpyeg_password_reference, 'first five characters' ), 'The breach-screening explanation states exactly what leaves the site.' );
wpyeg_test_assert( false !== strpos( $wpyeg_password_reference, 'full hash ever leaves the site' ), 'The breach-screening explanation states the privacy boundary.' );

// readme.txt is what Plugin Review actually reads for the external-services
// disclosure. It carried a fuller version than the field ever did; pin it so a
// tidy-up cannot quietly remove the thing compliance depends on.
wpyeg_test_assert( false !== strpos( $wpyeg_plugin_readme, 'first five characters' ), 'readme.txt discloses what the breach check sends.' );
wpyeg_test_assert( false !== strpos( $wpyeg_plugin_readme, 'WPYEG_DISABLE_HIBP' ), 'readme.txt names the opt-out constant.' );

/*
 * And the field help states the rule only. The word budget is the convention
 * made testable: this string oscillated six times in a day across three plugins
 * because "add one clarifying sentence" is always locally defensible.
 */
$wpyeg_field_help_words = str_word_count( preg_replace( '/<[^>]*>/', '', $schema['require_strong_passwords']['help'] ) );
wpyeg_test_assert( $wpyeg_field_help_words >= 40 && $wpyeg_field_help_words <= 55, "Password field help states the rule in 40-55 words (currently {$wpyeg_field_help_words})." );
wpyeg_test_assert( false === strpos( $schema['require_strong_passwords']['help'], 'haveibeenpwned.com' ), 'The protocol detail stays out of the field help; the reference doc and readme carry it.' );
wpyeg_test_assert( false === strpos( $schema['login_logo_behavior']['help'], 'trust leak' ), 'Login-logo guidance does not overstate an external destination as a trust leak.' );
wpyeg_test_assert( false !== strpos( $schema['login_logo_behavior']['help'], 'organizationally consistent' ), 'Login-logo guidance describes the actual branding and destination benefit.' );

foreach ( $schema as $field ) {
	$help_without_safe_markup = preg_replace(
		'#</?code>|<a href="https://[A-Za-z0-9./_\#?&=%:+~-]+">|</a>#',
		'',
		isset( $field['help'] ) ? $field['help'] : ''
	);
	wpyeg_test_assert( false === strpos( $help_without_safe_markup, '<' ) && false === strpos( $help_without_safe_markup, '>' ), 'Schema help contains no markup outside the narrow allowlist.' );
}

/*
 * The settings screen, rendered.
 *
 * These assertions used to grep the plugin source for the exact characters the
 * old inline template happened to emit — including one that matched a literal
 * `label for="<?php echo esc_attr( $field_id ); ?>"`. That is a test of a
 * template's punctuation, not of what the screen does, and rewriting the
 * renderer broke it while the output was byte-identical. Render it instead.
 */
ob_start();
wpyeg_defaults_render_settings_page();
$settings_markup = ob_get_clean();

wpyeg_test_assert( false === strpos( $settings_markup, 'Enabled</label>' ), 'Checkboxes do not use a generic Enabled label.' );
wpyeg_test_assert( false !== strpos( $settings_markup, '<table class="form-table" role="presentation">' ), 'The settings screen uses WordPress classic form-table styling.' );
wpyeg_test_assert( false !== strpos( $settings_markup, 'class="description"' ), 'Settings help uses WordPress classic description styling.' );

/*
 * Every setting reaches the screen, with a label bound to its control and its
 * help text connected by aria-describedby.
 *
 * The renderer walks wpyeg_defaults_groups(), so a schema entry naming a group
 * that array does not list would silently never render — a setting that exists,
 * saves, and takes effect, with no way to change it. Same for a `section` with
 * no title. Nothing caught that before; this does, per key.
 */
$rendered_groups   = wpyeg_defaults_groups();
$rendered_sections = wpyeg_defaults_section_labels();

foreach ( $schema as $rendered_key => $rendered_field ) {
	$rendered_id = 'wpyeg-defaults-' . str_replace( '_', '-', $rendered_key );

	wpyeg_test_assert(
		isset( $rendered_groups[ $rendered_field['group'] ] ),
		"Setting {$rendered_key} names a group the settings screen renders."
	);

	if ( isset( $rendered_field['section'] ) ) {
		wpyeg_test_assert(
			isset( $rendered_sections[ $rendered_field['section'] ] ),
			"Setting {$rendered_key} names a section with a title."
		);
	}

	wpyeg_test_assert(
		false !== strpos( $settings_markup, 'name="wpyeg_better_by_default[' . $rendered_key . ']"' ),
		"Setting {$rendered_key} renders a control on the settings screen."
	);
	wpyeg_test_assert(
		false !== strpos( $settings_markup, 'label for="' . $rendered_id . '"' ),
		"Setting {$rendered_key} has a label bound to its control."
	);
	wpyeg_test_assert(
		empty( $rendered_field['help'] ) || false !== strpos( $settings_markup, 'aria-describedby="' . $rendered_id . '-description"' ),
		"Setting {$rendered_key} connects its help text with aria-describedby."
	);
}

// A section renders as one row with a fieldset and a screen-reader legend, not
// as one row per setting.
wpyeg_test_assert( substr_count( $settings_markup, '<fieldset>' ) === count( $rendered_sections ), 'Each section draws exactly one fieldset.' );
wpyeg_test_assert( substr_count( $settings_markup, '<fieldset>' ) === substr_count( $settings_markup, '</fieldset>' ), 'Every section fieldset is closed.' );
wpyeg_test_assert( substr_count( $settings_markup, '<tr>' ) === substr_count( $settings_markup, '</tr>' ), 'Every settings row is closed.' );

// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local test fixture.
$plugin_source = file_get_contents( dirname( __DIR__ ) . '/plugin/sane-defaults/sane-defaults.php' );

/*
 * Every row has a left-hand heading.
 *
 * Toggles used to draw as a full-width `<td colspan="2">` with an empty left
 * column, so twenty bare rows sat interleaved with the seven that had one. The
 * assertion here used to require exactly that markup — it was describing the
 * ragged screen rather than a property worth keeping. Every setting names a
 * section now, and this is the invariant that keeps it true: no row without a
 * `<th>`, and no `<th>` left empty.
 */
wpyeg_test_assert( false === strpos( $settings_markup, 'colspan' ), 'No settings row spans the label column instead of using it.' );

preg_match_all( '#<tr>(.*?)</tr>#s', $settings_markup, $settings_rows );

foreach ( $settings_rows[1] as $settings_row ) {
	$row_heading = array();
	preg_match( '#<th[^>]*>(.*?)</th>#s', $settings_row, $row_heading );
	$heading_text = isset( $row_heading[1] ) ? trim( preg_replace( '#<[^>]+>#', '', $row_heading[1] ) ) : '';
	$row_control  = array();
	preg_match( '#name="wpyeg_better_by_default\[([a-z_]+)\]#', $settings_row, $row_control );
	$row_label = isset( $row_control[1] ) ? $row_control[1] : 'unknown';

	wpyeg_test_assert( '' !== $heading_text, "Settings row for {$row_label} carries a heading in the label column." );
}

/*
 * Left-column headings are Title Case.
 *
 * The group headings above them always were ("Admin and Front-End UX"), and the
 * sibling plugins' screens are too. Sentence case in the left column under a
 * Title Case heading is the sort of mixed convention nobody decides on — it
 * accumulates one setting at a time, which is why it is worth a guard rather
 * than a note.
 *
 * Articles and coordinating conjunctions stay lowercase inside a title.
 * Prepositions capitalise — "Accounts Per Step", not "Accounts per Step".
 *
 * That second rule is not the usual one, so here is the reasoning rather than a
 * pointer to whoever else does it. This list began as an ad-hoc set of "short
 * words" and was arbitrary: it held `via` but not `per`, `with`, `from` or
 * `into`, so it lowercased some prepositions and capitalised others with no
 * principle deciding which. A half-list is worse than either whole rule,
 * because nothing tells you which half a new word belongs to.
 *
 * Lowercasing every preposition and capitalising every preposition are both
 * defensible. Capitalising is what the shipped copy across these plugins
 * already does — "Accounts Per Step" was written that way by hand, before any
 * guard existed — so it is the rule derived from practice rather than imposed
 * on it, and adopting it changed no heading here. Whichever is chosen, the
 * rules must agree across the three plugins: they were mutually exclusive
 * before this, so a heading containing a preposition could not satisfy both
 * and no string could be copied between the repos.
 */
$title_case_small_words = array( 'a', 'an', 'the', 'and', 'or', 'nor', 'but' );

/**
 * Assert one heading is Title Case.
 *
 * @param string $heading Heading text.
 * @param string $source  Where it came from, for the failure message.
 * @return void
 */
$assert_title_case = static function ( $heading, $source ) use ( $title_case_small_words ) {
	foreach ( explode( ' ', $heading ) as $position => $heading_word ) {
		if ( '' === $heading_word ) {
			continue;
		}

		/*
		 * A small word inside a title stays lowercase; a leading one still
		 * capitalises. Checking only that big words are capitalised lets
		 * "Comments And Pings" through, which is Title Case done wrong rather
		 * than not done — Keel's guard caught that and this one did not.
		 */
		if ( $position > 0 && in_array( strtolower( $heading_word ), $title_case_small_words, true ) ) {
			wpyeg_test_assert(
				strtolower( $heading_word ) === $heading_word,
				"Heading \"{$heading}\" ({$source}): the short word \"{$heading_word}\" stays lowercase inside a title."
			);
			continue;
		}

		/*
		 * Both halves of a hyphenated compound capitalise — "Front-End", not
		 * "Front-end" — matching the "Admin and Front-End UX" group heading this
		 * plugin already shipped, and the sibling plugins. Splitting on spaces
		 * alone passes "Front-end" happily, since the F is uppercase.
		 */
		foreach ( explode( '-', $heading_word ) as $heading_half ) {
			if ( '' === $heading_half ) {
				continue;
			}

			wpyeg_test_assert(
				ucfirst( $heading_half ) === $heading_half,
				"Heading \"{$heading}\" ({$source}) is Title Case: \"{$heading_half}\" in \"{$heading_word}\" starts lowercase."
			);
		}
	}
};

foreach ( wpyeg_defaults_groups() as $heading_key => $heading ) {
	$assert_title_case( $heading, "group {$heading_key}" );
}

foreach ( wpyeg_defaults_section_labels() as $heading_key => $heading ) {
	$assert_title_case( $heading, "section {$heading_key}" );
}

/*
 * And every heading that actually reaches the screen.
 *
 * Checking those two arrays is not enough. A select or number field with no
 * section draws its row header from its schema *label*, not from a section
 * title — so three of them sat in sentence case under Title Case group
 * headings while a guard over the arrays alone passed happily. Read the
 * rendered column instead of the sources that feed it.
 */
$headings_from_section = 0;
$headings_from_label   = 0;
$section_titles        = wpyeg_defaults_section_labels();

foreach ( $settings_rows[1] as $settings_row ) {
	$rendered_heading = array();

	if ( ! preg_match( '#<th[^>]*>(.*?)</th>#s', $settings_row, $rendered_heading ) ) {
		continue;
	}

	$heading_text = trim( preg_replace( '#<[^>]+>#', '', $rendered_heading[1] ) );
	$assert_title_case( $heading_text, 'rendered label column' );

	if ( in_array( $heading_text, $section_titles, true ) ) {
		++$headings_from_section;
	} else {
		++$headings_from_label;
	}
}

/*
 * Both sources are actually represented in what was just checked.
 *
 * Reading the rendered column covers section titles and schema labels alike —
 * but only while both still appear. If a refactor moved every row to one
 * source, the loop above would keep passing while covering half the screen's
 * provenance. Assert the coverage, not just the result.
 */
wpyeg_test_assert( $headings_from_section > 0, 'Some row headings come from a section title, and were checked.' );
wpyeg_test_assert( $headings_from_label > 0, 'Some row headings come from a schema label, and were checked.' );

wpyeg_test_assert( false !== strpos( $plugin_source, "wp_kses( \$field['help'], wpyeg_defaults_help_allowed_html() )" ), 'Settings help is rendered through the narrow markup allowlist.' );

// The explicit update policy is stable across the installation-age defaults
// WordPress stores for major core updates.
wpyeg_test_assert( true === wpyeg_defaults_allow_minor_core_updates( false ), 'The default policy enables maintenance/security core releases.' );
wpyeg_test_assert( false === wpyeg_defaults_allow_major_core_updates( true ), 'The default policy blocks automatic major core releases.' );
wpyeg_test_assert( false === wpyeg_defaults_allow_dev_core_updates( true ), 'The default stable policy blocks development builds.' );

$GLOBALS['wpyeg_test_option'] = array( 'core_update_policy' => 'all' );
wpyeg_test_assert( true === wpyeg_defaults_allow_minor_core_updates( false ), 'The all-stable policy enables maintenance releases.' );
wpyeg_test_assert( true === wpyeg_defaults_allow_major_core_updates( false ), 'The all-stable policy enables major releases.' );
wpyeg_test_assert( false === wpyeg_defaults_allow_dev_core_updates( true ), 'All stable releases does not mean development builds.' );

$GLOBALS['wpyeg_test_option'] = array( 'core_update_policy' => 'manual' );
wpyeg_test_assert( false === wpyeg_defaults_allow_minor_core_updates( true ), 'The manual policy disables maintenance releases.' );
wpyeg_test_assert( false === wpyeg_defaults_allow_major_core_updates( true ), 'The manual policy disables major releases.' );

$GLOBALS['wpyeg_test_option'] = array( 'core_update_policy' => 'inherit' );
wpyeg_test_assert( true === wpyeg_defaults_allow_minor_core_updates( true ), 'The inherit policy preserves an enabled core decision.' );
wpyeg_test_assert( false === wpyeg_defaults_allow_major_core_updates( false ), 'The inherit policy preserves a disabled core decision.' );

unset( $GLOBALS['wpyeg_test_option'] );

/*
 * Policy snapshot, not endorsement.
 *
 * remove_version now ships OFF, per PR #1: stripping the generator tag is
 * obscurity, not hardening. It does not make an out-of-date site safer, and the
 * version still leaks from asset query strings and feeds — so it is offered as
 * noise reduction you opt into, not a security default.
 *
 * defer_scripts is gone, also per PR #1, but for a sharper reason than "generic":
 * WordPress 6.3 added a per-script loading strategy
 * ( wp_enqueue_script( ..., array( 'strategy' => 'defer' ) ) ), so blanket
 * script_loader_tag rewriting is teaching a superseded technique.
 *
 * security_headers was not dropped. It was split: nosniff and Referrer-Policy
 * are low-risk and stay on, while X-Frame-Options — the only one of the three
 * that can break a working site — became its own setting.
 *
 * Still open from PR #1: whether attachment redirects should also be opt-in.
 *
 * PR #1 also wanted disable_ai_connectors dropped as a no-op, which it was — it
 * only fired an action nobody listened to. It now does real work against core's
 * wp_supports_ai gate (WordPress 7.0), so it stays, and is covered below.
 */
wpyeg_test_assert( 'yes' === $schema['redirect_attachment_pages']['default'], 'Attachment redirects ship on (PR #1 proposed opt-in).' );
wpyeg_test_assert( 'no' === $schema['remove_version']['default'], 'Generator-tag removal is opt-in, not presented as hardening.' );
wpyeg_test_assert( ! isset( $schema['defer_scripts'] ), 'Blanket script deferral is gone; core has a per-script strategy since 6.3.' );
wpyeg_test_assert( isset( $schema['security_headers'], $schema['frame_options'] ), 'Security headers are split, so framing can be changed without giving up nosniff.' );
wpyeg_test_assert( 'SAMEORIGIN' === $schema['frame_options']['default'], 'Framing still defaults to SAMEORIGIN.' );
wpyeg_test_assert( array_key_exists( '', $schema['frame_options']['choices'] ), 'Framing can be left to the host or CDN.' );

$sanitized = wpyeg_defaults_sanitize(
	array(
		'xmlrpc_allow_pingbacks' => 'yes',
		'remember_me_days'       => -30,
		'login_logo_behavior'    => 'invalid',
	)
);
wpyeg_test_assert( 'yes' === $sanitized['xmlrpc_allow_pingbacks'], 'Enabled toggles sanitize to yes.' );
wpyeg_test_assert( 30 === $sanitized['remember_me_days'], 'Numeric settings sanitize to non-negative integers.' );
wpyeg_test_assert( 'keep_default' === $sanitized['login_logo_behavior'], 'Invalid select values fall back to schema defaults.' );

$short = wpyeg_defaults_validate_password( 'too-short' );
wpyeg_test_assert( is_wp_error( $short ) && 'wpyeg_password_too_short' === $short->get_error_code(), 'Short passwords are rejected.' );
wpyeg_test_assert( true === wpyeg_defaults_validate_password( 'a long passphrase with spaces' ), 'Long passphrases are accepted without composition rules.' );

/**
 * Reduce a validator result to a comparable error code.
 *
 * @param true|WP_Error $result Validator result.
 * @return string Error code, or an empty string when the password was accepted.
 */
function wpyeg_test_password_error( $result ) {
	return is_wp_error( $result ) ? $result->get_error_code() : '';
}

/**
 * Run an admin-side validator against a raw, still-slashed $_POST['pass1'].
 *
 * @param string      $validator Validator function name.
 * @param string      $raw_post  Raw $_POST['pass1'] value, as core receives it.
 * @param object|null $user      User context.
 * @return string First error code, or an empty string when accepted.
 */
function wpyeg_test_posted_password_error( $validator, $raw_post, $user = null ) {
	$_POST['pass1'] = $raw_post;
	$errors         = new WP_Error();

	if ( 'reset' === $validator ) {
		wpyeg_defaults_validate_reset_password( $errors, $user );
	} else {
		wpyeg_defaults_validate_profile_password( $errors, true, $user );
	}

	unset( $_POST['pass1'] );
	return $errors->get_error_code();
}

// Blocklist. Entries are compared case-insensitively, after normalization.
wpyeg_test_assert( 'wpyeg_password_common' === wpyeg_test_password_error( wpyeg_defaults_validate_password( 'wordpresswordpress' ) ), 'Blocklisted passwords at or above the minimum length are rejected.' );
wpyeg_test_assert( 'wpyeg_password_common' === wpyeg_test_password_error( wpyeg_defaults_validate_password( 'WordPressWordPress' ) ), 'The blocklist comparison is case-insensitive.' );

/*
 * Entries shorter than the default 15-character minimum are unreachable at the
 * default settings, but become load-bearing as soon as a site filters the
 * minimum down. They are deliberately kept in the list for this case.
 */
$GLOBALS['wpyeg_test_filter_values']['wpyeg_minimum_password_length'] = 8;
wpyeg_test_assert( 'wpyeg_password_common' === wpyeg_test_password_error( wpyeg_defaults_validate_password( 'password' ) ), 'Short blocklist entries apply when a site lowers wpyeg_minimum_password_length.' );
$GLOBALS['wpyeg_test_filter_values'] = array();

// Personal-context rejections.
$user_context                = new stdClass();
$user_context->user_login    = 'administrator';
$user_context->user_nicename = 'administrator';
$user_context->user_email    = 'admin@example.com';
wpyeg_test_assert( 'wpyeg_password_personal' === wpyeg_test_password_error( wpyeg_defaults_validate_password( 'administratorlonghorse', $user_context ) ), 'Passwords containing the username are rejected.' );
wpyeg_test_assert( 'wpyeg_password_personal' === wpyeg_test_password_error( wpyeg_defaults_validate_password( 'adminXXXXXXXXXXXXXXXX', $user_context ) ), 'Passwords containing the email name are rejected.' );
wpyeg_test_assert( '' === wpyeg_test_password_error( wpyeg_defaults_validate_password( 'correct horse battery staple', $user_context ) ), 'Unrelated passphrases pass the personal-context check.' );

$short_context             = new stdClass();
$short_context->user_login = 'bob';
$short_context->user_email = 'bob@example.com';
wpyeg_test_assert( '' === wpyeg_test_password_error( wpyeg_defaults_validate_password( 'bobbobbobbobbob', $short_context ) ), 'Context values under four characters are not substring-matched.' );

/*
 * Core's edit_user() trims $_POST['pass1'] and stores the trimmed value
 * (wp-admin/includes/user.php), while firing user_profile_update_errors with
 * $_POST untouched. A validator reading $_POST raw therefore measures a
 * different string than core saves, and whitespace padding slips past it.
 */
wpyeg_test_assert( 'wpyeg_password_too_short' === wpyeg_test_posted_password_error( 'profile', str_repeat( ' ', 15 ) . 'a' ), 'Whitespace padding cannot inflate a short password to the minimum length.' );
wpyeg_test_assert( 'wpyeg_password_common' === wpyeg_test_posted_password_error( 'profile', 'wordpresswordpress ' ), 'Whitespace padding cannot smuggle a blocklisted password past the blocklist.' );
wpyeg_test_assert( 'wpyeg_password_too_short' === wpyeg_test_posted_password_error( 'reset', str_repeat( ' ', 15 ) . 'a' ), 'The reset screen rejects whitespace-padded short passwords too.' );

// Internal whitespace is part of the password and must survive trimming.
wpyeg_test_assert( '' === wpyeg_test_posted_password_error( 'profile', 'a long passphrase with spaces' ), 'Passphrases with internal spaces remain acceptable.' );

// An omitted or whitespace-only field means "no password change"; core owns that case.
wpyeg_test_assert( '' === wpyeg_test_posted_password_error( 'profile', '' ), 'An empty password field is left alone.' );
wpyeg_test_assert( '' === wpyeg_test_posted_password_error( 'profile', '   ' ), 'A whitespace-only field is treated as empty, as core does.' );

/*
 * REST authentication gate.
 *
 * The value core hands this filter is not a plain boolean: rest_cookie_check_errors()
 * returns true after calling wp_set_current_user( 0 ) when a cookie arrives without an
 * X-WP-Nonce. Treating any truthy result as "authenticated" would let that request
 * through to dispatch as user 0, so only a WP_Error may short-circuit.
 */
$GLOBALS['wpyeg_test_current_user'] = 0;
$anonymous                          = wpyeg_defaults_require_rest_auth( null );
wpyeg_test_assert( is_wp_error( $anonymous ) && 'rest_not_logged_in' === $anonymous->get_error_code(), 'Anonymous REST requests are rejected.' );
wpyeg_test_assert( array( 'status' => 401 ) === $anonymous->get_error_data(), 'The REST rejection carries a 401 status.' );

$nonceless = wpyeg_defaults_require_rest_auth( true );
wpyeg_test_assert( is_wp_error( $nonceless ), 'A cookie without a nonce resolves to user 0 and is still rejected.' );

$GLOBALS['wpyeg_test_current_user'] = 7;
wpyeg_test_assert( null === wpyeg_defaults_require_rest_auth( null ), 'Authenticated requests pass through untouched.' );
wpyeg_test_assert( true === wpyeg_defaults_require_rest_auth( true ), 'A successful auth result is preserved.' );

$prior = new WP_Error( 'rest_cookie_invalid_nonce', 'Cookie check failed' );
wpyeg_test_assert( wpyeg_defaults_require_rest_auth( $prior ) === $prior, 'An existing authentication error is returned unchanged.' );

/**
 * Stand-in for the core users controller's check_user_password() sanitize callback.
 *
 * @param mixed  $value   Submitted password.
 * @param object $request REST request.
 * @param string $param   Parameter name.
 * @return string|WP_Error
 */
function wpyeg_test_core_check_user_password( $value, $request, $param ) {
	unset( $request, $param );
	$password = (string) $value;
	if ( false !== strpos( $password, '\\' ) ) {
		return new WP_Error( 'rest_user_invalid_password', 'Passwords cannot contain the "\\" character.', array( 'status' => 400 ) );
	}
	return $password;
}

/**
 * Build an endpoint map shaped like the routes core registers.
 *
 * @return array
 */
function wpyeg_test_rest_endpoints() {
	$password_arg = array(
		'password' => array(
			'type'              => 'string',
			'sanitize_callback' => 'wpyeg_test_core_check_user_password',
		),
	);

	return array(
		'/wp/v2/users'               => array(
			array(
				'methods' => 'POST',
				'args'    => $password_arg,
			),
		),
		'/wp/v2/users/(?P<id>[\d]+)' => array(
			array(
				'methods' => 'POST',
				'args'    => $password_arg,
			),
		),
		'/wp/v2/users/me'            => array(
			array(
				'methods' => 'POST',
				'args'    => $password_arg,
			),
		),
		'/wp/v2/users/(?P<user_id>[\d]+)/application-passwords' => array(
			array(
				'methods' => 'POST',
				'args'    => $password_arg,
			),
		),
		// Posts carry an unrelated `password` argument that must not be policed.
		'/wp/v2/posts'               => array(
			array(
				'methods' => 'POST',
				'args'    => $password_arg,
			),
		),
	);
}

/**
 * Run a route's (possibly wrapped) password sanitize callback.
 *
 * @param array           $endpoints Endpoint map.
 * @param string          $route     Route key.
 * @param string          $value     Submitted password.
 * @param WP_REST_Request $request   REST request.
 * @return mixed
 */
function wpyeg_test_sanitize_rest_password( $endpoints, $route, $value, $request ) {
	return call_user_func( $endpoints[ $route ][0]['args']['password']['sanitize_callback'], $value, $request, 'password' );
}

/*
 * REST password argument guard.
 *
 * rest_pre_insert_user cannot report a policy failure — the controller never checks its
 * return for an error — so the policy runs as an argument sanitize callback, where
 * sanitize_params() converts a WP_Error into a 400 before the callback is reached.
 */
$guarded = wpyeg_defaults_guard_rest_password_arg( wpyeg_test_rest_endpoints() );

$create_request = new WP_REST_Request( array( 'username' => 'newuser' ), '/wp/v2/users' );

$weak = wpyeg_test_sanitize_rest_password( $guarded, '/wp/v2/users', 'short', $create_request );
wpyeg_test_assert( is_wp_error( $weak ) && 'wpyeg_password_too_short' === $weak->get_error_code(), 'A weak REST password is rejected at the argument level.' );
wpyeg_test_assert( array( 'status' => 400 ) === $weak->get_error_data(), 'The REST password rejection carries a 400 status.' );

$strong = wpyeg_test_sanitize_rest_password( $guarded, '/wp/v2/users', 'correct horse battery staple', $create_request );
wpyeg_test_assert( 'correct horse battery staple' === $strong, 'A compliant REST password sanitizes through unchanged.' );

// Core sanitizes first, and its rejection must survive rather than be replaced.
$backslashed = wpyeg_test_sanitize_rest_password( $guarded, '/wp/v2/users', 'a long password with \\ in it', $create_request );
wpyeg_test_assert( is_wp_error( $backslashed ) && 'rest_user_invalid_password' === $backslashed->get_error_code(), "Core's own password rejection is preserved." );

// The policy applies to a create using only what the request carries.
$named = wpyeg_test_sanitize_rest_password( $guarded, '/wp/v2/users', 'newuserlongpassword', $create_request );
wpyeg_test_assert( is_wp_error( $named ) && 'wpyeg_password_personal' === $named->get_error_code(), 'A new user cannot embed their submitted username in the password.' );

// On an update the context is the stored user the route id points at.
$stored                          = new stdClass();
$stored->user_login              = 'administrator';
$stored->user_email              = 'admin@example.com';
$stored->user_nicename           = 'administrator';
$GLOBALS['wpyeg_test_users'][12] = $stored;

$update_request = new WP_REST_Request( array( 'id' => 12 ), '/wp/v2/users/12' );
$update         = wpyeg_test_sanitize_rest_password( $guarded, '/wp/v2/users/(?P<id>[\d]+)', 'administratorlonghorse', $update_request );
wpyeg_test_assert( is_wp_error( $update ) && 'wpyeg_password_personal' === $update->get_error_code(), 'An update is checked against the stored user the route points at.' );

// /wp/v2/users/me carries no id; the context is the authenticated user.
$GLOBALS['wpyeg_test_current_user'] = 12;
$me_request                         = new WP_REST_Request( array(), '/wp/v2/users/me' );
$me                                 = wpyeg_test_sanitize_rest_password( $guarded, '/wp/v2/users/me', 'administratorlonghorse', $me_request );
wpyeg_test_assert( is_wp_error( $me ) && 'wpyeg_password_personal' === $me->get_error_code(), 'The /users/me route resolves context from the current user.' );
$GLOBALS['wpyeg_test_current_user'] = 0;

// Application Passwords are core-generated; a human policy must not touch them.
$app_password = wpyeg_test_sanitize_rest_password( $guarded, '/wp/v2/users/(?P<user_id>[\d]+)/application-passwords', 'short', $create_request );
wpyeg_test_assert( 'short' === $app_password, 'Application Password routes are left alone.' );

// A post password is a different concept that happens to share the argument name.
$post_password = wpyeg_test_sanitize_rest_password( $guarded, '/wp/v2/posts', 'short', $create_request );
wpyeg_test_assert( 'short' === $post_password, 'Non-user routes with a password argument are left alone.' );

wpyeg_defaults_bootstrap();
$registered_hooks = array_column( $GLOBALS['wpyeg_test_hooks'], 'hook' );
wpyeg_test_assert( in_array( 'xmlrpc_methods', $registered_hooks, true ), 'XML-RPC method removal is registered.' );
wpyeg_test_assert( in_array( 'rest_pre_insert_user', $registered_hooks, true ), 'REST password validation is registered.' );
wpyeg_test_assert( in_array( 'allow_minor_auto_core_updates', $registered_hooks, true ), 'The maintenance/security core update policy is registered.' );
wpyeg_test_assert( in_array( 'allow_major_auto_core_updates', $registered_hooks, true ), 'The major core update policy is registered.' );
wpyeg_test_assert( in_array( 'allow_dev_auto_core_updates', $registered_hooks, true ), 'Development core builds are kept out of stable policies.' );
wpyeg_test_assert( ! in_array( 'auto_update_translation', $registered_hooks, true ), 'Translation updates remain on WordPress defaults.' );
wpyeg_test_assert( ! in_array( 'auto_update_plugin', $registered_hooks, true ), 'Plugin code updates remain on WordPress per-item settings.' );
wpyeg_test_assert( ! in_array( 'auto_update_theme', $registered_hooks, true ), 'Theme code updates remain on WordPress per-item settings.' );
wpyeg_test_assert( ! in_array( 'script_loader_tag', $registered_hooks, true ), 'Blanket script-tag mutation is not registered.' );

// XML-RPC stays granular: remove unused core families without erasing methods
// registered by integrations such as Jetpack.
$xmlrpc_hook    = wpyeg_test_find_hook( 'xmlrpc_methods' );
$xmlrpc_methods = call_user_func(
	$xmlrpc_hook['callback'],
	array(
		'pingback.ping'    => 'core-pingback',
		'wp.getUsersBlogs' => 'core-publishing',
		'demo.sayHello'    => 'core-demo',
		'jetpack.jsonAPI'  => 'third-party',
	)
);
wpyeg_test_assert( ! isset( $xmlrpc_methods['pingback.ping'] ), 'Default XML-RPC policy removes incoming pingbacks.' );
wpyeg_test_assert( ! isset( $xmlrpc_methods['wp.getUsersBlogs'] ), 'Default XML-RPC policy removes core remote publishing.' );
wpyeg_test_assert( ! isset( $xmlrpc_methods['demo.sayHello'] ), 'Default XML-RPC policy removes inert demo methods.' );
wpyeg_test_assert( isset( $xmlrpc_methods['jetpack.jsonAPI'] ), 'Default XML-RPC policy preserves third-party methods.' );

// Assert the callback, not the hook name: restrict_rest_user_discovery also
// filters rest_endpoints, so a name-only check passes with the guard removed.
$registered_callbacks = array_column( $GLOBALS['wpyeg_test_hooks'], 'callback' );
wpyeg_test_assert( in_array( 'wpyeg_defaults_guard_rest_password_arg', $registered_callbacks, true ), 'The REST password argument guard is registered.' );

/**
 * Find the first registration for a hook.
 *
 * @param string $hook Hook name.
 * @return array|null
 */
function wpyeg_test_find_hook( $hook ) {
	foreach ( $GLOBALS['wpyeg_test_hooks'] as $entry ) {
		if ( $entry['hook'] === $hook ) {
			return $entry;
		}
	}
	return null;
}

/*
 * Accuracy guard for the repository's teaching copy.
 *
 * Everything else in this suite compares structure — keys, counts, one artifact
 * against another. None of it can tell that a paragraph describes a rule the
 * code no longer follows, and that has now happened twice: the "thousands of
 * guesses per multicall" claim survived until WordPress 4.4 made it false, and
 * the "set only if unset" header rule survived the release that replaced it,
 * so the talk contradicted the plugin through several merges.
 *
 * This is the answer, and it is a narrow one. It catches only drift someone
 * thought to name. That is worth having anyway, because the moment you change
 * behaviour is the moment you know which sentence just became false — and
 * writing it down here costs one line.
 *
 * WHEN YOU RETIRE A BEHAVIOUR, ADD THE SENTENCE THAT DESCRIBED IT.
 *
 * One trap, learned the hard way. Forbid only phrasings that assert the old
 * rule as current. Corrected copy often *quotes* the old rule to explain what
 * changed — the headers slide now says the original rule was "set only if
 * unset", which is exactly right and must keep passing. Pick the phrase that
 * cannot survive an honest rewrite.
 */
$retired_claims = array(
	'amplifier that batches thousands of login guesses' => 'the obsolete multicall claim',
	'Gate the whole endpoint off'                       => 'the xmlrpc_enabled misdescription',
	'Only fill in what nothing else has set'            => 'the "set only if unset" header rule the reference doc kept teaching after 1.1.1 replaced it',
	'all three check whether the'                       => 'the claim that this plugin only writes a header nobody else has set',
	"wrap.closest('p').style.display"                   => 'the login_footer script the Remember Me default deliberately does not use',
	'wpyeg_is_pwned('                                   => 'a breach-check function this plugin has never shipped',
	'hide_for_non_admins'                               => 'the misspelled admin-bar choice value',
	'stopped testing credentials after the first failed login' => 'the pre-correction multicall wording',
	'only fill in what nothing else set'                => 'the replaced "set only if unset" header rule',
	'Note the isset() guards'                           => 'the replaced isset()-guard header note',
	'we should not fight that layer'                    => 'deference to an upstream header that is now compared against',
);

$accuracy_files = array(
	dirname( __DIR__ ) . '/README.md',
	dirname( __DIR__ ) . '/docs/wordpress-default-settings.md',
	dirname( __DIR__ ) . '/docs/when-two-plugins-set-the-same-default.md',
	dirname( __DIR__ ) . '/plugin/sane-defaults/README.md',
	dirname( __DIR__ ) . '/plugin/sane-defaults/readme.txt',
	dirname( __DIR__ ) . '/plugin/sane-defaults/sane-defaults.php',
	dirname( __DIR__ ) . '/workshop/Better-by-Default.ia.md',
	dirname( __DIR__ ) . '/workshop/better-by-default.iapresenter/text.md',
	dirname( __DIR__ ) . '/workshop/build_deck.js',
);

foreach ( $accuracy_files as $accuracy_file ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local test fixture.
	$accuracy_copy = file_get_contents( $accuracy_file );

	foreach ( $retired_claims as $retired_claim => $claim_description ) {
		wpyeg_test_assert(
			false === strpos( $accuracy_copy, $retired_claim ),
			basename( $accuracy_file ) . ' does not repeat ' . $claim_description . '.'
		);
	}
}

// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local test fixture.
$xmlrpc_reference = file_get_contents( dirname( __DIR__ ) . '/docs/wordpress-default-settings.md' );
wpyeg_test_assert( false !== strpos( $xmlrpc_reference, 'core.trac.wordpress.org/ticket/34336' ), 'The XML-RPC reference cites the WordPress 4.4 authentication fix.' );

// disable_rest ships off, so stage it on to check how it registers.
$GLOBALS['wpyeg_test_option'] = array( 'disable_rest' => 'yes' );
$GLOBALS['wpyeg_test_hooks']  = array();
wpyeg_defaults_bootstrap();
$auth_gate = wpyeg_test_find_hook( 'rest_authentication_errors' );
unset( $GLOBALS['wpyeg_test_option'] );

wpyeg_test_assert( null !== $auth_gate, 'Enabling disable_rest registers the authentication gate.' );
wpyeg_test_assert( PHP_INT_MAX === $auth_gate['priority'], 'The gate runs after core resolves cookie and Application Password auth.' );

// disable_ai_connectors ships on. It used to only fire its own action, which is
// what made it a no-op; check it now reaches core's WP 7.0 AI gate.
$GLOBALS['wpyeg_test_hooks']       = array();
$GLOBALS['wpyeg_test_did_actions'] = array();
wpyeg_defaults_bootstrap();
$ai_gate = wpyeg_test_find_hook( 'wp_supports_ai' );

wpyeg_test_assert( null !== $ai_gate, 'Disabling AI connectors filters core wp_supports_ai.' );
wpyeg_test_assert( '__return_false' === $ai_gate['callback'], 'The AI gate returns false, so provider connectors never register.' );
wpyeg_test_assert( null !== wpyeg_test_find_hook( 'admin_menu' ), 'The core Connectors screen is removed from the menu.' );
wpyeg_test_assert( null !== wpyeg_test_find_hook( 'admin_init' ), 'The Connectors screen is closed, not merely unlinked.' );

/*
 * The seam fires on init, not during bootstrap. Firing it at plugin load put it
 * before every plugin that registers its own hooks from a plugins_loaded
 * callback, and before the providers it exists to unregister had registered at
 * all — a seam nothing could reach. Assert both halves: that bootstrap does not
 * dispatch it, and that the init callback it registers does.
 */
wpyeg_test_assert(
	! in_array( 'wpyeg_disable_ai_connectors', $GLOBALS['wpyeg_test_did_actions'], true ),
	'The AI seam does not fire during bootstrap, when nothing could have hooked it yet.'
);

$ai_seam = null;
foreach ( $GLOBALS['wpyeg_test_hooks'] as $ai_hook ) {
	if ( 'init' === $ai_hook['hook'] && 20 === $ai_hook['priority'] ) {
		$ai_seam = $ai_hook;
	}
}

wpyeg_test_assert( null !== $ai_seam, 'The AI seam is registered on init at priority 20.' );

if ( $ai_seam ) {
	call_user_func( $ai_seam['callback'] );
}

wpyeg_test_assert(
	in_array( 'wpyeg_disable_ai_connectors', $GLOBALS['wpyeg_test_did_actions'], true ),
	'The seam still fires for AI integrations core does not know about.'
);

/**
 * Run every registered wp_headers filter over a starting set of headers.
 *
 * @param array $headers Starting headers.
 * @return array
 */
function wpyeg_test_send_headers( $headers ) {
	foreach ( $GLOBALS['wpyeg_test_hooks'] as $entry ) {
		if ( 'wp_headers' === $entry['hook'] ) {
			$headers = call_user_func( $entry['callback'], $headers );
		}
	}
	return $headers;
}

// Header defaults: the two low-risk ones ship on, framing is its own setting.
$GLOBALS['wpyeg_test_hooks'] = array();
wpyeg_defaults_bootstrap();
$sent = wpyeg_test_send_headers( array() );

wpyeg_test_assert( 'nosniff' === $sent['X-Content-Type-Options'], 'nosniff ships on.' );
wpyeg_test_assert( isset( $sent['Referrer-Policy'] ), 'Referrer-Policy ships on.' );
wpyeg_test_assert( 'SAMEORIGIN' === $sent['X-Frame-Options'], 'Framing ships as SAMEORIGIN.' );

// --- HIBP opt-out -----------------------------------------------------------
// The lookup is k-anonymous, but an air-gapped site needs a switch, not an
// argument. Both forms: the constant is an operator declaration, the filter
// lets a plugin decide per password.
// The assertion that matters is that *no request is made*: returning false is
// also what an unreachable API does, so a false return proves nothing on its own.
$GLOBALS['wpyeg_test_last_http_url'] = null;
wpyeg_test_assert( false === wpyeg_defaults_password_is_pwned( 'a password nobody has' ), 'Screening answers "not breached" when the API returns nothing.' );
wpyeg_test_assert( null !== $GLOBALS['wpyeg_test_last_http_url'], 'Baseline: screening normally does reach the API.' );

$GLOBALS['wpyeg_test_filter_values']['wpyeg_disable_hibp'] = true;
$GLOBALS['wpyeg_test_last_http_url']                       = null;
wpyeg_test_assert( false === wpyeg_defaults_password_is_pwned( 'a password nobody has' ), 'With screening off the answer is "not breached".' );
wpyeg_test_assert( null === $GLOBALS['wpyeg_test_last_http_url'], 'And nothing leaves the server: the filter short-circuits before wp_remote_get.' );
unset( $GLOBALS['wpyeg_test_filter_values']['wpyeg_disable_hibp'] );

// A host or CDN may already own these — but "already set" is not the same as
// "stronger", and deferring to a weaker value lets it win purely by arriving
// first. Compare instead of yielding.
$preset = wpyeg_test_send_headers(
	array(
		'X-Frame-Options'        => 'DENY',
		'X-Content-Type-Options' => 'set-by-the-cdn',
	)
);
wpyeg_test_assert( 'DENY' === $preset['X-Frame-Options'], 'A stronger X-Frame-Options is never downgraded to the configured SAMEORIGIN.' );
wpyeg_test_assert( 'nosniff' === $preset['X-Content-Type-Options'], 'A meaningless X-Content-Type-Options is corrected, not deferred to: nosniff is the only value that does anything.' );

// Case-insensitivity. HTTP header names are case-insensitive and PHP array keys
// are not, so matching on the exact string emits a second, conflicting line.
$cased = wpyeg_test_send_headers( array( 'x-content-type-options' => 'set-by-the-cdn' ) );
wpyeg_test_assert( ! isset( $cased['X-Content-Type-Options'] ), 'A differently cased header is not duplicated under the canonical spelling.' );
wpyeg_test_assert( 'nosniff' === $cased['x-content-type-options'], 'The correction is written back to the key that was already there.' );

$cased_frame = wpyeg_test_send_headers( array( 'x-frame-options' => 'DENY' ) );
wpyeg_test_assert( ! isset( $cased_frame['X-Frame-Options'] ), 'A differently cased X-Frame-Options is not duplicated either.' );

// An existing value browsers do not honour is left alone rather than guessed at:
// tightening a deprecated ALLOW-FROM would reverse its permissive intent.
$unknown = wpyeg_test_send_headers( array( 'X-Frame-Options' => 'ALLOW-FROM https://example.com' ) );
wpyeg_test_assert( 'ALLOW-FROM https://example.com' === $unknown['X-Frame-Options'], 'An unrecognised X-Frame-Options value is left untouched.' );

// Referrer-Policy has no single strictness axis across its tokens, so an
// existing policy is deferred to — case-insensitively.
$referrer = wpyeg_test_send_headers( array( 'referrer-policy' => 'same-origin' ) );
wpyeg_test_assert( 'same-origin' === $referrer['referrer-policy'], 'An existing Referrer-Policy is respected whatever its casing.' );
wpyeg_test_assert( ! isset( $referrer['Referrer-Policy'] ), 'And is not duplicated.' );

/*
 * Strictness ordering, which the assertions above do not reach.
 *
 * They check that a header is not duplicated and that an unrecognised value is
 * left alone. Neither notices whether DENY and SAMEORIGIN are ranked correctly
 * against each other — ranking DENY equal to SAMEORIGIN kept every one of them
 * passing, because "do not downgrade" and "do not touch" look the same when the
 * two values compare equal.
 *
 * The ordering is the whole point of the header logic: a host or CDN that
 * already sends DENY must never be relaxed to this site's SAMEORIGIN, and a
 * site that deliberately configures DENY must still tighten a weaker value it
 * finds in place.
 */
wpyeg_test_assert( 2 === wpyeg_defaults_frame_option_strength( 'DENY' ), 'DENY is the strictest ranked value.' );
wpyeg_test_assert( 1 === wpyeg_defaults_frame_option_strength( 'SAMEORIGIN' ), 'SAMEORIGIN ranks below DENY.' );
wpyeg_test_assert(
	wpyeg_defaults_frame_option_strength( 'DENY' ) > wpyeg_defaults_frame_option_strength( 'SAMEORIGIN' ),
	'DENY outranks SAMEORIGIN, so a stricter existing header is never downgraded.'
);
wpyeg_test_assert( null === wpyeg_defaults_frame_option_strength( 'ALLOW-FROM https://example.com' ), 'An unranked value has no strength.' );
wpyeg_test_assert( 2 === wpyeg_defaults_frame_option_strength( '  deny  ' ), 'Ranking ignores case and surrounding space.' );

// End to end: the default is SAMEORIGIN, so an existing DENY survives it.
$wpyeg_saved_option = isset( $GLOBALS['wpyeg_test_option'] ) ? $GLOBALS['wpyeg_test_option'] : null;
$kept_deny          = wpyeg_test_send_headers( array( 'X-Frame-Options' => 'DENY' ) );
wpyeg_test_assert( 'DENY' === $kept_deny['X-Frame-Options'], 'A stricter existing X-Frame-Options is kept, not downgraded to the configured value.' );

// And the converse, so the assertion above cannot pass by never writing at all:
// configured DENY tightens a weaker existing SAMEORIGIN.
$GLOBALS['wpyeg_test_option'] = array( 'frame_options' => 'DENY' );
$GLOBALS['wpyeg_test_hooks']  = array();
wpyeg_defaults_bootstrap();
$tightened = wpyeg_test_send_headers( array( 'X-Frame-Options' => 'SAMEORIGIN' ) );
wpyeg_test_assert( 'DENY' === $tightened['X-Frame-Options'], 'A configured DENY tightens a weaker existing value.' );

if ( null === $wpyeg_saved_option ) {
	unset( $GLOBALS['wpyeg_test_option'] );
} else {
	$GLOBALS['wpyeg_test_option'] = $wpyeg_saved_option;
}
$GLOBALS['wpyeg_test_hooks'] = array();
wpyeg_defaults_bootstrap();

/*
 * The unfiltered_html clamp had no test at all.
 *
 * It is the one default here that removes a capability rather than adding
 * behaviour, so every exemption in it is load-bearing: drop the Super Admin
 * branch and a network's owner silently loses the ability to save a script tag,
 * with nothing to say why.
 *
 * The recursion note in its docblock is the reason it reads $user->roles and the
 * already-resolved $allcaps instead of calling current_user_can() — these
 * assertions pin the decisions, not the mechanism.
 */
$wpyeg_editor        = new stdClass();
$wpyeg_editor->ID    = 7;
$wpyeg_editor->roles = array( 'editor' );

$wpyeg_admin        = new stdClass();
$wpyeg_admin->ID    = 1;
$wpyeg_admin->roles = array( 'administrator' );

$wpyeg_caps = array(
	'unfiltered_html' => true,
	'edit_posts'      => true,
);

$GLOBALS['wpyeg_test_multisite']    = false;
$GLOBALS['wpyeg_test_super_admins'] = array();

$clamped = wpyeg_defaults_limit_unfiltered_html( $wpyeg_caps, array(), array(), $wpyeg_editor );
wpyeg_test_assert( empty( $clamped['unfiltered_html'] ), 'An Editor loses unfiltered_html.' );

$kept = wpyeg_defaults_limit_unfiltered_html( $wpyeg_caps, array(), array(), $wpyeg_admin );
wpyeg_test_assert( ! empty( $kept['unfiltered_html'] ), 'An Administrator keeps unfiltered_html.' );

// manage_options is the resolved-cap proxy for "is an admin", for a user whose
// role slug is not literally 'administrator'.
$wpyeg_manager        = new stdClass();
$wpyeg_manager->ID    = 8;
$wpyeg_manager->roles = array( 'site_manager' );
$manager_caps         = array(
	'unfiltered_html' => true,
	'manage_options'  => true,
);
$manager_result       = wpyeg_defaults_limit_unfiltered_html( $manager_caps, array(), array(), $wpyeg_manager );
wpyeg_test_assert( ! empty( $manager_result['unfiltered_html'] ), 'A non-administrator role holding manage_options keeps unfiltered_html.' );

// Super Admins keep it on multisite — and the guard is multisite-only, because
// is_super_admin() calls has_cap() on single site and would recurse.
$GLOBALS['wpyeg_test_multisite']    = true;
$GLOBALS['wpyeg_test_super_admins'] = array( 7 );
$super                              = wpyeg_defaults_limit_unfiltered_html( $wpyeg_caps, array(), array(), $wpyeg_editor );
wpyeg_test_assert( ! empty( $super['unfiltered_html'] ), 'A Super Admin keeps unfiltered_html on multisite.' );

$GLOBALS['wpyeg_test_super_admins'] = array();
$not_super                          = wpyeg_defaults_limit_unfiltered_html( $wpyeg_caps, array(), array(), $wpyeg_editor );
wpyeg_test_assert( empty( $not_super['unfiltered_html'] ), 'A non-Super-Admin on multisite still loses it.' );
$GLOBALS['wpyeg_test_multisite'] = false;

// A user who never had the capability is returned untouched rather than having
// the key invented as false.
$without = wpyeg_defaults_limit_unfiltered_html( array( 'edit_posts' => true ), array(), array(), $wpyeg_editor );
wpyeg_test_assert( ! array_key_exists( 'unfiltered_html', $without ), 'A user without the capability is left exactly as found.' );

/*
 * oEmbed author identity.
 *
 * Redirecting author archives does nothing about /wp-json/oembed/1.0/embed,
 * which returns author_name and an author_url carrying the account nicename —
 * so a site that had hidden its authors was still handing out login names
 * through a route nobody thinks of as a user endpoint. Both fields go, and
 * nothing else does, or embeds on other sites break.
 */
$oembed = wpyeg_defaults_strip_oembed_author(
	array(
		'author_name'   => 'admin',
		'author_url'    => 'https://example.test/author/admin/',
		'title'         => 'A post',
		'provider_name' => 'Example',
		'html'          => '<blockquote>…</blockquote>',
	)
);
wpyeg_test_assert( ! array_key_exists( 'author_name', $oembed ), 'oEmbed no longer returns author_name.' );
wpyeg_test_assert( ! array_key_exists( 'author_url', $oembed ), 'oEmbed no longer returns author_url, which carried the nicename.' );
wpyeg_test_assert( 'A post' === $oembed['title'], 'The title survives, so embeds elsewhere keep working.' );
wpyeg_test_assert( 'Example' === $oembed['provider_name'], 'The provider survives.' );
wpyeg_test_assert( false !== strpos( $oembed['html'], 'blockquote' ), 'The embed markup survives.' );
wpyeg_test_assert( 'nope' === wpyeg_defaults_strip_oembed_author( 'nope' ), 'A non-array response is returned untouched.' );

// The point of splitting them: framing can be handed back to the host without
// also giving up nosniff, which the old single toggle made impossible.
$GLOBALS['wpyeg_test_option'] = array( 'frame_options' => '' );
$GLOBALS['wpyeg_test_hooks']  = array();
wpyeg_defaults_bootstrap();
$unframed = wpyeg_test_send_headers( array() );
unset( $GLOBALS['wpyeg_test_option'] );

wpyeg_test_assert( ! isset( $unframed['X-Frame-Options'] ), 'Choosing "leave unchanged" sends no X-Frame-Options.' );
wpyeg_test_assert( 'nosniff' === $unframed['X-Content-Type-Options'], 'Turning framing off does not also give up nosniff.' );

// Attachment pages: where the redirect actually points.
$GLOBALS['wpyeg_test_template'] = '';
$GLOBALS['wpyeg_test_parent']   = 42;
wpyeg_test_assert(
	'https://example.test/parent-post-42/' === wpyeg_defaults_attachment_redirect_target( 7 ),
	'An attached file redirects to its parent post.'
);

// The case the old code got wrong: unattached media has no parent, and sending
// every one of those to the homepage is a soft 404.
$GLOBALS['wpyeg_test_parent'] = 0;
$target                       = wpyeg_defaults_attachment_redirect_target( 7 );
wpyeg_test_assert( 'https://example.test/wp-content/uploads/photo.jpg' === $target, 'Unattached media falls back to the file, as core does.' );
wpyeg_test_assert( false === strpos( $target, 'example.test/?' ) && '/' !== substr( $target, -1 ), 'Unattached media is never sent to the homepage.' );

// A theme that ships an attachment template meant to render these pages.
$GLOBALS['wpyeg_test_template'] = '/themes/portfolio/attachment.php';
wpyeg_test_assert( '' === wpyeg_defaults_attachment_redirect_target( 7 ), 'A theme attachment template suppresses the redirect.' );

// ...and a site can overrule that either way.
$GLOBALS['wpyeg_test_filter_values']['wpyeg_keep_attachment_page'] = false;
wpyeg_test_assert( '' !== wpyeg_defaults_attachment_redirect_target( 7 ), 'wpyeg_keep_attachment_page can force the redirect anyway.' );
unset( $GLOBALS['wpyeg_test_filter_values']['wpyeg_keep_attachment_page'] );
unset( $GLOBALS['wpyeg_test_template'], $GLOBALS['wpyeg_test_parent'] );

/*
 * Breach screening (Have I Been Pwned, k-anonymity).
 *
 * The hash is derived here rather than hard-coded so the fixtures cannot drift
 * from what the helper actually sends. The staged responses stand in for the
 * range endpoint; the suite never touches the network.
 */
$hibp_password = 'correct horse battery staple';
$hibp_hash     = strtoupper( sha1( $hibp_password ) );
$hibp_suffix   = substr( $hibp_hash, 5 );

// A real match: the suffix is present with a non-zero count.
$GLOBALS['wpyeg_test_transients'] = array();
$GLOBALS['wpyeg_test_http']       = array(
	'response' => array( 'code' => 200 ),
	'body'     => "0000000000000000000000000000000000A:3\r\n{$hibp_suffix}:42\r\n",
);
wpyeg_test_assert( true === wpyeg_defaults_password_is_pwned( $hibp_password ), 'A hash suffix returned with a non-zero count is treated as breached.' );
wpyeg_test_assert( 128 * 1024 === $GLOBALS['wpyeg_test_last_http_args']['limit_response_size'], 'HIBP responses are capped at 128 KiB through the WordPress HTTP API.' );
wpyeg_test_assert( 'true' === $GLOBALS['wpyeg_test_last_http_args']['headers']['Add-Padding'], 'HIBP range requests ask for padded responses.' );

// Add-Padding rows come back with a count of 0 and must not count as a match.
$GLOBALS['wpyeg_test_transients'] = array();
$GLOBALS['wpyeg_test_http']       = array(
	'response' => array( 'code' => 200 ),
	'body'     => "{$hibp_suffix}:0\r\n",
);
wpyeg_test_assert( false === wpyeg_defaults_password_is_pwned( $hibp_password ), 'Padded rows (count 0) are ignored rather than read as a breach.' );

// An unreachable API must fail open, never lock out a password change.
$GLOBALS['wpyeg_test_transients'] = array();
$GLOBALS['wpyeg_test_http']       = new WP_Error( 'http_request_failed', 'offline' );
wpyeg_test_assert( false === wpyeg_defaults_password_is_pwned( $hibp_password ), 'A transport failure fails open.' );

$GLOBALS['wpyeg_test_transients'] = array();
$GLOBALS['wpyeg_test_http']       = array(
	'response' => array( 'code' => 503 ),
	'body'     => '',
);
wpyeg_test_assert( false === wpyeg_defaults_password_is_pwned( $hibp_password ), 'A non-200 response fails open.' );

// A body that reaches the transport limit may be truncated and is unavailable.
$GLOBALS['wpyeg_test_transients'] = array();
$GLOBALS['wpyeg_test_http']       = array(
	'response' => array( 'code' => 200 ),
	'body'     => str_repeat( 'A', 128 * 1024 ),
);
wpyeg_test_assert( false === wpyeg_defaults_password_is_pwned( $hibp_password ), 'A response reaching the 128 KiB transport cap fails open.' );
wpyeg_test_assert( array() === $GLOBALS['wpyeg_test_transients'], 'A capped response is never cached.' );

/*
 * The cap guard has to hold for *well-formed* data too. The check above is
 * satisfied by the format regex alone — 128 KiB of "A" has no colon — so it
 * cannot tell whether the length check works. This body is structurally valid
 * and reaches the cap on a row boundary, which is the case that only the length
 * check can catch: measured after trim() it slips under the cap, validates, and
 * would then be cached for 12 hours with its tail silently missing.
 */
$hibp_cap = 39 * 27; // 35 hex + ":" + 1 digit + CRLF, times a whole number of rows.
$GLOBALS['wpyeg_test_filter_values']['wpyeg_hibp_max_response_bytes'] = $hibp_cap;
$hibp_rows = array();
for ( $i = 0; $i < 26; $i++ ) {
	$hibp_rows[] = strtoupper( substr( sha1( 'row' . $i ), 5 ) ) . ':' . ( $i % 7 );
}
// The real suffix rides in this truncated body: if the cap guard misses, the
// call returns true instead of failing open, so the assertion below is real.
$hibp_rows[] = $hibp_suffix . ':4';

$hibp_capped_body                 = implode( "\r\n", $hibp_rows ) . "\r\n";
$GLOBALS['wpyeg_test_transients'] = array();
$GLOBALS['wpyeg_test_http']       = array(
	'response' => array( 'code' => 200 ),
	'body'     => $hibp_capped_body,
);
wpyeg_test_assert( strlen( $hibp_capped_body ) === $hibp_cap, 'The truncation fixture reaches the cap exactly.' );
wpyeg_test_assert( false === wpyeg_defaults_password_is_pwned( $hibp_password ), 'A well-formed response reaching the cap fails open rather than trusting a truncated range.' );
wpyeg_test_assert( array() === $GLOBALS['wpyeg_test_transients'], 'A well-formed capped response is never cached.' );
unset( $GLOBALS['wpyeg_test_filter_values']['wpyeg_hibp_max_response_bytes'] );

// A cached response that no longer validates is cleared, not reused for 12h.
$GLOBALS['wpyeg_test_transients'] = array( 'wpyeg_hibp_' . substr( $hibp_hash, 0, 5 ) => 'CORRUPTED CACHE ENTRY' );
$GLOBALS['wpyeg_test_http']       = new WP_Error( 'http_request_failed', 'network down' );
wpyeg_test_assert( false === wpyeg_defaults_password_is_pwned( $hibp_password ), 'An invalid cached range response fails open.' );
wpyeg_test_assert( array() === $GLOBALS['wpyeg_test_transients'], 'An invalid cached range response is deleted so the next call refetches.' );

// One invalid row invalidates the response, even if another row looks like a match.
$GLOBALS['wpyeg_test_transients'] = array();
$GLOBALS['wpyeg_test_http']       = array(
	'response' => array( 'code' => 200 ),
	'body'     => "MALFORMED\r\n{$hibp_suffix}:42\r\n",
);
wpyeg_test_assert( false === wpyeg_defaults_password_is_pwned( $hibp_password ), 'A malformed range response fails open instead of trusting partial data.' );
wpyeg_test_assert( array() === $GLOBALS['wpyeg_test_transients'], 'A malformed range response is never cached.' );

// The range response is cached per prefix, so a second call makes no request.
$GLOBALS['wpyeg_test_transients'] = array();
$GLOBALS['wpyeg_test_http']       = array(
	'response' => array( 'code' => 200 ),
	'body'     => "{$hibp_suffix}:9\r\n",
);
wpyeg_defaults_password_is_pwned( $hibp_password );
$GLOBALS['wpyeg_test_http'] = new WP_Error( 'http_request_failed', 'must not be called' );
wpyeg_test_assert( true === wpyeg_defaults_password_is_pwned( $hibp_password ), 'The cached range response is reused instead of refetching.' );

// The valid-response scan remains allocation-light and exits on the first match.
wpyeg_test_assert( false !== strpos( $plugin_source, "foreach ( preg_split( '/\\r\\n|\\n/', \$body ) as \$line )" ), 'HIBP suffixes are scanned directly rather than copied into another result array.' );
wpyeg_test_assert( false !== strpos( $plugin_source, "\t\t\tbreak;" ), 'The HIBP scan exits immediately after a matching suffix.' );

/*
 * Cross-artifact parity. The schema is the source of truth for the reference
 * table and the workshop schema map, so a setting cannot silently drift out of
 * learner-facing material.
 */
$repo_root = dirname( __DIR__ );
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local test fixtures.
$reference_doc = file_get_contents( $repo_root . '/docs/wordpress-default-settings.md' );
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local test fixtures.
$workshop_source = file_get_contents( $repo_root . '/workshop/Better-by-Default.ia.md' );
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local test fixtures.
$presenter_source = file_get_contents( $repo_root . '/workshop/better-by-default.iapresenter/text.md' );
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local test fixtures.
$deck_source = file_get_contents( $repo_root . '/workshop/build_deck.js' );
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local test fixtures.
$plugin_readme = file_get_contents( $repo_root . '/plugin/sane-defaults/readme.txt' );

$group_labels = array(
	'security'    => 'Security',
	'updates'     => 'Updates',
	'content'     => 'Content',
	'ux'          => 'UX',
	'login'       => 'Login',
	'branding'    => 'Branding',
	'performance' => 'Performance',
	'email'       => 'Email',
);

foreach ( $schema as $key => $field ) {
	$default = (string) $field['default'];
	$display = '' === $default ? "''" : $default;
	$doc_row = '/^\\| [^|]+ \\| `' . preg_quote( $key, '/' ) . '` \\| `' . preg_quote( $display, '/' ) . '` \\| ' . preg_quote( $group_labels[ $field['group'] ], '/' ) . ' \\|$/m';
	$map_row = '/^\\| `' . preg_quote( $key, '/' ) . '` \\| `' . preg_quote( $display, '/' ) . '` \\|/m';
	$js_row  = '["' . $key . '", "' . $display . '",';

	/*
	 * readme.txt is the shipped plugin's own description of itself, so it lists
	 * every setting with its default — and now gets checked like the rest. Two
	 * releases ago this file and both READMEs described the split in prose that
	 * nothing read, and two settings whose defaults flipped in 1.1.3 went on
	 * being documented as off. Prose cannot be guarded; a table can.
	 */
	$readme_row = '/^\\| [^|]+ \\| `' . preg_quote( $key, '/' ) . '` \\| `' . preg_quote( $display, '/' ) . '` \\|$/m';

	wpyeg_test_assert( 1 === preg_match( $doc_row, $reference_doc ), "Reference table matches schema key {$key}." );
	wpyeg_test_assert( 1 === preg_match( $map_row, $workshop_source ), "Workshop schema map matches schema key {$key}." );
	wpyeg_test_assert( false !== strpos( $deck_source, $js_row ), "Deck source matches schema key {$key}." );
	wpyeg_test_assert( 1 === preg_match( $readme_row, $plugin_readme ), "readme.txt lists schema key {$key} with its shipped default." );
}

wpyeg_test_assert( $workshop_source === $presenter_source, 'The iA Presenter source is identical to the canonical workshop source.' );
wpyeg_test_assert( false === strpos( $reference_doc . $workshop_source . $deck_source, 'remember_me_policy' ), 'Removed setting names do not survive in learner materials.' );
wpyeg_test_assert( false === strpos( $reference_doc . $workshop_source . $deck_source, 'PMP-specific' ), 'BBD settings are not mislabeled as PMP-specific.' );
wpyeg_test_assert( false === strpos( $deck_source, 'OPTION   ' ), 'Workshop chips identify schema keys rather than separate options.' );

/*
 * Every PHP sample on a slide has to be PHP that parses.
 *
 * This is a teaching deck: a sample on screen is something an attendee copies
 * into a real site. Two shipped slides carried code that does not compile —
 * `add_filter( 'heartbeat_settings', fn( $s ) => { ... } )`, an arrow function
 * given a block body, and `add_action( 'init', fn )`, where `fn` was meant as a
 * placeholder but has been a reserved word since PHP 7.4. Both were on built
 * slides, and every check in this file passed the whole time, because nothing
 * had ever asked whether the code on a slide was code.
 *
 * Samples appear in build_deck.js in two shapes: arrays of
 * { t: "<line>", k: "<kind>" } for the code panels, and a bare ex: "<line>" for
 * the actions/filters glossary. Both are checked.
 *
 * A slide passes if the WHOLE slide parses, or FAILING THAT if every
 * blank-line-separated block within it parses on its own. Both readings are
 * needed and neither is sufficient: some slides split one listing across a
 * blank line for spacing, so only the whole slide parses; the "add your own
 * setting" slide deliberately shows an array entry and a statement block side
 * by side, so only the individual blocks do.
 *
 * "Parses" means in any reasonable context — as statements, or as an excerpt
 * from an array literal, which is how the schema slides show a single entry.
 * The aim is not to reject fragments, it is that no sample gets to be
 * unparseable under every reading.
 */

/**
 * Whether a slide sample parses as PHP in any reasonable reading.
 *
 * @param string $sample Sample source, comments stripped.
 * @return string Empty when it parses, otherwise the parser's complaint.
 */
function wpyeg_deck_sample_error( $sample ) {
	if ( '' === trim( $sample ) ) {
		return '';
	}

	$contexts    = array( '%s', '$sample = array(' . "\n" . '%s' . "\n" . ');' );
	$first_error = '';

	foreach ( $contexts as $context ) {
		$tmp = tempnam( sys_get_temp_dir(), 'bbd-deck' ) . '.php';
		file_put_contents( $tmp, "<?php\n" . sprintf( $context, $sample ) . "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		$output = array();
		$status = 0;
		exec( escapeshellarg( PHP_BINARY ) . ' -l ' . escapeshellarg( $tmp ) . ' 2>&1', $output, $status ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
		unlink( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- a temp file in a CLI test; wp_delete_file() is not loaded here.

		if ( 0 === $status ) {
			return '';
		}

		if ( '' === $first_error ) {
			$first_error = trim( (string) reset( $output ) );
		}
	}

	return '' !== $first_error ? $first_error : 'did not parse';
}

/**
 * Strip full-line comments so a sample is judged on its code.
 *
 * @param string $sample Sample source.
 * @return string
 */
function wpyeg_deck_sample_code( $sample ) {
	return trim( preg_replace( '/^\s*\/\/.*$/m', '', $sample ) );
}

// --- gather the code panels, one group per slide ---
$deck_slide_samples = array();
$deck_slide_lines   = array();
$deck_slide_cursor  = null;

if ( preg_match_all( '/\{\s*t:\s*"((?:[^"\\\\]|\\\\.)*)"\s*,\s*k:\s*"[a-z]*"\s*\}/', $deck_source, $deck_slide_matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
	foreach ( $deck_slide_matches as $deck_slide_match ) {
		$deck_slide_start = (int) $deck_slide_match[0][1];

		// A closing bracket between two samples means a new slide started.
		if ( null !== $deck_slide_cursor
			&& false !== strpos( substr( $deck_source, $deck_slide_cursor, $deck_slide_start - $deck_slide_cursor ), ']' ) ) {
			$deck_slide_samples[] = $deck_slide_lines;
			$deck_slide_lines     = array();
		}

		$deck_slide_lines[] = stripcslashes( $deck_slide_match[1][0] );
		$deck_slide_cursor  = $deck_slide_start + strlen( $deck_slide_match[0][0] );
	}
}
if ( $deck_slide_lines ) {
	$deck_slide_samples[] = $deck_slide_lines;
}

wpyeg_test_assert( count( $deck_slide_samples ) > 10, 'The deck scan found the slide code panels (' . count( $deck_slide_samples ) . ' slides).' );

// The glossary rows carry a single line each, checked the same way.
if ( preg_match_all( '/ex:\s*"((?:[^"\\\\]|\\\\.)*)"/', $deck_source, $deck_glossary_matches ) ) {
	foreach ( $deck_glossary_matches[1] as $deck_glossary_sample ) {
		$deck_slide_samples[] = array( stripcslashes( $deck_glossary_sample ) );
	}
	wpyeg_test_assert( count( $deck_glossary_matches[1] ) > 1, 'The deck scan found the glossary samples.' );
}

$deck_samples_checked = 0;

foreach ( $deck_slide_samples as $deck_slide ) {
	$deck_whole = wpyeg_deck_sample_code( implode( "\n", $deck_slide ) );

	// Nothing that calls or declares anything is not code worth linting.
	if ( ! preg_match( '/\w\s*\(/', $deck_whole ) ) {
		continue;
	}

	++$deck_samples_checked;

	$deck_whole_error = wpyeg_deck_sample_error( $deck_whole );

	if ( '' === $deck_whole_error ) {
		continue;
	}

	// Fall back to judging each block on its own, for the slides that
	// deliberately place two contexts side by side.
	$deck_blocks  = array();
	$deck_current = array();

	foreach ( $deck_slide as $deck_line ) {
		if ( '' === trim( $deck_line ) ) {
			if ( $deck_current ) {
				$deck_blocks[] = implode( "\n", $deck_current );
				$deck_current  = array();
			}
			continue;
		}
		$deck_current[] = $deck_line;
	}
	if ( $deck_current ) {
		$deck_blocks[] = implode( "\n", $deck_current );
	}

	$deck_block_error = '';

	foreach ( $deck_blocks as $deck_block ) {
		$deck_block_code = wpyeg_deck_sample_code( $deck_block );

		if ( '' === $deck_block_code ) {
			continue;
		}

		$deck_block_error = wpyeg_deck_sample_error( $deck_block_code );

		if ( '' !== $deck_block_error ) {
			break;
		}
	}

	wpyeg_test_assert(
		'' === $deck_block_error,
		'A PHP sample on a slide does not parse (' . $deck_block_error . '); sample begins: '
			. trim( strtok( $deck_whole, "\n" ) )
	);
}

wpyeg_test_assert( $deck_samples_checked > 15, "The lint pass reached the slide samples ({$deck_samples_checked} checked)." );

/*
 * The setting count is prose, so the per-key row checks above never saw it. It
 * sat at 27 through two commits that took the schema to 31.
 */
$setting_count = count( $schema );

$counted_sources = array(
	'workshop script' => $workshop_source,
	'deck generator'  => $deck_source,
);

foreach ( $counted_sources as $count_label => $count_source ) {
	wpyeg_test_assert( false !== strpos( $count_source, "We have {$setting_count} settings" ), "The {$count_label} states the current setting count." );
	wpyeg_test_assert( false !== strpos( $count_source, "activates {$setting_count} sensible" ), "The {$count_label} opens with the current setting count." );
}

/*
 * The rendered deck. Everything above reads build_deck.js; nothing read what it
 * produces, which is how a corrected sentence sat in the generator for two days
 * while Better-by-Default.pptx still carried the wrong one. A .pptx is a zip of
 * XML, so pull the text runs out of the slides and the speaker notes and hold
 * the artifact to the same standard as its sources.
 *
 * This is also the only check that exercises the generator. A key added to
 * build_deck.js but never built into the deck fails here, and the only way to
 * fix it is to run the generator — which is the one thing that proves the
 * generator still runs at all. It did not, for one commit, and nothing noticed.
 */

/**
 * Extract slide text and speaker notes from a .pptx.
 *
 * @param string $pptx_path Path to the presentation.
 * @return string Every text run, one slide or notes page per line.
 */
function wpyeg_test_pptx_text( $pptx_path ) {
	$zip = new ZipArchive();

	if ( true !== $zip->open( $pptx_path ) ) {
		return '';
	}

	$text        = '';
	$entry_count = count( $zip );

	for ( $i = 0; $i < $entry_count; $i++ ) {
		if ( ! preg_match( '#^ppt/(slides|notesSlides)/[^/]+\.xml$#', $zip->getNameIndex( $i ) ) ) {
			continue;
		}

		preg_match_all( '#<a:t>(.*?)</a:t>#s', $zip->getFromIndex( $i ), $runs );
		$text .= implode( ' ', $runs[1] ) . "\n";
	}

	$zip->close();

	return $text;
}

$deck_artifact = $repo_root . '/workshop/Better-by-Default.pptx';

if ( ! class_exists( 'ZipArchive' ) ) {
	echo "Skipped the rendered-deck guard: this PHP build has no ZipArchive.\n";
} else {
	wpyeg_test_assert( is_readable( $deck_artifact ), 'The rendered deck is committed alongside its generator.' );

	$deck_text = wpyeg_test_pptx_text( $deck_artifact );
	wpyeg_test_assert( '' !== $deck_text, 'The rendered deck yields readable slide text.' );

	foreach ( $schema as $key => $field ) {
		wpyeg_test_assert( false !== strpos( $deck_text, $key ), "The rendered deck carries schema key {$key}." );
	}

	wpyeg_test_assert( false !== strpos( $deck_text, "We have {$setting_count} settings" ), 'The rendered deck states the current setting count.' );

	// Same list as the sources, not a copy of it. A retired claim corrected in
	// build_deck.js but never rebuilt into the deck is precisely the drift that
	// shipped last time.
	foreach ( $retired_claims as $retired_claim => $claim_description ) {
		wpyeg_test_assert(
			false === strpos( $deck_text, $retired_claim ),
			'The rendered deck does not repeat ' . $claim_description . '.'
		);
	}

	/*
	 * The PDF handout, as far as it can honestly be checked. Its text lives in
	 * subsetted font encodings that do not survive naive extraction, so this
	 * compares structure only: one page per slide.
	 *
	 * Be clear about the limit. This catches a slide added or removed without
	 * regenerating the handout, which is the common drift. It cannot catch a
	 * wording correction, because that does not change the page count — so a
	 * green suite is not proof the handout is current. Rebuild it whenever the
	 * deck changes: composer build:deck, then the soffice line in README.
	 */
	$pdf_handout = $repo_root . '/workshop/Better-by-Default.pdf';
	wpyeg_test_assert( is_readable( $pdf_handout ), 'The PDF handout is committed alongside the deck.' );

	$slide_count = 0;
	$zip         = new ZipArchive();

	if ( true === $zip->open( $deck_artifact ) ) {
		$entry_count = count( $zip );

		for ( $i = 0; $i < $entry_count; $i++ ) {
			if ( preg_match( '#^ppt/slides/slide[0-9]+\.xml$#', $zip->getNameIndex( $i ) ) ) {
				++$slide_count;
			}
		}
		$zip->close();
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local test fixture.
	$page_count = preg_match_all( '#/Type\s*/Page[^s]#', file_get_contents( $pdf_handout ) );

	wpyeg_test_assert( $slide_count > 0, 'The rendered deck reports a slide count.' );
	wpyeg_test_assert( $slide_count === $page_count, "The PDF handout has one page per slide (deck {$slide_count}, handout {$page_count})." );
}

/*
 * The two prose READMEs point at a guarded table instead of restating it.
 *
 * They used to carry their own on/off lists, checked only for three magic
 * strings — enough to prove the words "AI connectors" appeared somewhere, and
 * not enough to notice that both files called two on-by-default settings
 * opt-in for two releases. The lists are gone; what is checked now is that
 * each file still sends the reader somewhere the defaults are guaranteed
 * current, and that neither has quietly grown a replacement list.
 */
foreach ( array( 'README.md', 'plugin/sane-defaults/README.md' ) as $readme_path ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local test fixtures.
	$readme = file_get_contents( $repo_root . '/' . $readme_path );

	wpyeg_test_assert(
		false !== strpos( $readme, 'wordpress-default-settings.md' ) || false !== strpos( $readme, 'readme.txt' ),
		"{$readme_path} points at a table that is checked against the schema."
	);
	wpyeg_test_assert( false !== strpos( $readme, '14 days' ), "{$readme_path} documents the remembered-session length." );

	// A prose list of settings is the shape that went stale. Schema keys are how
	// one gets written: naming three or more of them here means the enumeration
	// is back, in a file nothing checks per-key.
	$key_mentions = 0;
	foreach ( array_keys( $schema ) as $schema_key ) {
		if ( false !== strpos( $readme, $schema_key ) ) {
			++$key_mentions;
		}
	}

	wpyeg_test_assert( $key_mentions < 3, "{$readme_path} has not regrown an unguarded list of settings ({$key_mentions} keys named)." );
}

unset( $GLOBALS['wpyeg_test_http'] );
$GLOBALS['wpyeg_test_transients'] = array();

/*
 * Login & sessions: unit consistency (days), per-field floors, and the
 * Remember Me >= regular guardrail. These three controls carried inherited
 * confusions — mixed units (days vs hours), a "0 = core default" sentinel that
 * read as a real value, and no floor, so a short Remember Me length could make
 * ticking the box *shorten* the login. All three are fixed here.
 */
$login_schema = wpyeg_defaults_schema();

// Both length fields are day-based numbers now; the legacy hours field is gone.
wpyeg_test_assert( isset( $login_schema['session_regular_days'] ), 'session_regular_days field exists.' );
wpyeg_test_assert( ! isset( $login_schema['session_regular_hours'] ), 'Legacy session_regular_hours field is gone.' );
wpyeg_test_assert( 'number' === $login_schema['session_regular_days']['type'], 'Regular session is a number field.' );
wpyeg_test_assert( 'number' === $login_schema['remember_me_days']['type'], 'Remember Me length is a number field.' );

// Defaults are prefilled with WordPress's real values — no "0 = core default" sentinel.
wpyeg_test_assert( 2 === $login_schema['session_regular_days']['default'], 'Regular default is WordPress\'s 2 days.' );
wpyeg_test_assert( 14 === $login_schema['remember_me_days']['default'], 'Remember Me default is WordPress\'s 14 days.' );
wpyeg_test_assert( 1 === (int) $login_schema['session_regular_days']['min'], 'Regular session has a 1-day floor.' );
wpyeg_test_assert( 1 === (int) $login_schema['remember_me_days']['min'], 'Remember Me length has a 1-day floor.' );

// A per-field minimum clamps a below-floor submission up to the floor.
$floored = wpyeg_defaults_sanitize(
	array(
		'session_regular_days' => '0',
		'remember_me_days'     => '0',
	)
);
wpyeg_test_assert( 1 === $floored['session_regular_days'], 'Regular session clamps 0 up to its 1-day floor.' );

// The guardrail: a remembered login can never be shorter than a regular one.
$guardrail = wpyeg_defaults_sanitize(
	array(
		'session_regular_days' => '10',
		'remember_me_days'     => '3',
	)
);
wpyeg_test_assert( 10 === $guardrail['remember_me_days'], 'Remember Me is clamped up to the regular session length (10).' );

// A coherent pair (remember >= regular) is left untouched.
$coherent = wpyeg_defaults_sanitize(
	array(
		'session_regular_days' => '2',
		'remember_me_days'     => '30',
	)
);
wpyeg_test_assert( 2 === $coherent['session_regular_days'] && 30 === $coherent['remember_me_days'], 'A valid remember >= regular pair passes through unchanged.' );

/**
 * Run the registered auth_cookie_expiration filter for a given login type.
 *
 * @param bool $remember Whether the login ticked "Remember Me".
 * @return int Cookie lifetime in seconds, or the untouched input when the
 *             plugin did not register the filter at all.
 */
function wpyeg_test_auth_cookie_expiration( $remember ) {
	$hook = wpyeg_test_find_hook( 'auth_cookie_expiration' );

	if ( null === $hook ) {
		return 7 * DAY_IN_SECONDS;
	}

	return (int) call_user_func( $hook['callback'], 7 * DAY_IN_SECONDS, 1, $remember );
}

/*
 * Out of the box the filter is not registered at all, and that is the point.
 *
 * auth_cookie_expiration is a replacing filter: the callback returns its own
 * number and discards the one it was handed, so two plugins registering one do
 * not compose — WordPress keeps whichever ran last and the other silently loses
 * while its settings screen goes on showing a number the site is not using.
 * Both defaults here are WordPress's own values, so registering on an untouched
 * site would enter that fight to assert the answer core already gives.
 */
$GLOBALS['wpyeg_test_hooks']  = array();
$GLOBALS['wpyeg_test_option'] = array();
wpyeg_defaults_bootstrap();
wpyeg_test_assert( null === wpyeg_test_find_hook( 'auth_cookie_expiration' ), 'At the default lengths the session filter is not registered, so it cannot contest another plugin.' );
wpyeg_test_assert( false === wpyeg_defaults_session_policy_is_custom(), 'Schema defaults are recognised as "no opinion".' );

// Change either length and the plugin does have something to say, so it says it.
$GLOBALS['wpyeg_test_hooks']  = array();
$GLOBALS['wpyeg_test_option'] = array( 'session_regular_days' => 1 );
wpyeg_defaults_bootstrap();
wpyeg_test_assert( true === wpyeg_defaults_session_policy_is_custom(), 'A changed length counts as an opinion.' );
wpyeg_test_assert( DAY_IN_SECONDS === wpyeg_test_auth_cookie_expiration( false ), 'A regular login lasts the configured regular length.' );
wpyeg_test_assert( 14 * DAY_IN_SECONDS === wpyeg_test_auth_cookie_expiration( true ), 'A remembered login lasts the remembered length (14 days).' );

// The callback is a named function, not a closure, so $wp_filter can identify
// this plugin on the one hook most likely to be contested.
$session_hook = wpyeg_test_find_hook( 'auth_cookie_expiration' );
wpyeg_test_assert( 'wpyeg_defaults_auth_cookie_expiration' === $session_hook['callback'], 'The session callback is nameable in $wp_filter rather than an anonymous closure.' );

// Disabling Remember Me routes every login — even a remembered one — through the
// regular length, and strips the forged flag server-side (not just via JS/CSS).
$GLOBALS['wpyeg_test_hooks']  = array();
$GLOBALS['wpyeg_test_option'] = array(
	'disable_remember_me'  => 'yes',
	'session_regular_days' => 3,
);
wpyeg_defaults_bootstrap();
wpyeg_test_assert( 3 * DAY_IN_SECONDS === wpyeg_test_auth_cookie_expiration( true ), 'Disabling Remember Me makes even a remembered login use the regular length.' );
wpyeg_test_assert( null !== wpyeg_test_find_hook( 'login_init' ), 'Disabling Remember Me strips the rememberme POST server-side, not only via the checkbox-hiding script.' );

// Disabling Remember Me is a policy even when both lengths are left alone.
$GLOBALS['wpyeg_test_hooks']  = array();
$GLOBALS['wpyeg_test_option'] = array( 'disable_remember_me' => 'yes' );
wpyeg_defaults_bootstrap();
wpyeg_test_assert( 2 * DAY_IN_SECONDS === wpyeg_test_auth_cookie_expiration( true ), 'Disabling Remember Me registers the filter even at the default lengths.' );

unset( $GLOBALS['wpyeg_test_option'] );
$GLOBALS['wpyeg_test_hooks'] = array();

/*
 * Comment teardown: the parts that reach past the theme template.
 *
 * The REST case is the one that matters. Core's comments controller declares
 * `'type' => array( 'default' => 'comment' )`, so every GET /wp/v2/comments
 * arrives asking for that type — an implementation that exempts `comment`
 * queries leaves the main exposure open while looking careful.
 */

/**
 * Minimal WP_Comment_Query stand-in: only query_vars is consulted.
 *
 * @param array $vars Query vars.
 * @return object
 */
function wpyeg_test_comment_query( $vars ) {
	$query             = new stdClass();
	$query->query_vars = $vars;
	return $query;
}

wpyeg_test_assert( array( 'note' ) === wpyeg_defaults_allowed_comment_types(), 'Block Notes are exempt from the comment teardown by default.' );

wpyeg_test_assert(
	array() === wpyeg_defaults_empty_comment_queries( null, wpyeg_test_comment_query( array( 'type' => 'comment' ) ) ),
	'A type=comment query is answered empty — that is every GET /wp/v2/comments.'
);
wpyeg_test_assert(
	array() === wpyeg_defaults_empty_comment_queries( null, wpyeg_test_comment_query( array() ) ),
	'An untyped comment query is answered empty.'
);
wpyeg_test_assert(
	null === wpyeg_defaults_empty_comment_queries( null, wpyeg_test_comment_query( array( 'type' => 'note' ) ) ),
	'A note query runs normally.'
);
wpyeg_test_assert(
	null === wpyeg_defaults_empty_comment_queries( null, wpyeg_test_comment_query( array( 'type__in' => array( 'comment', 'note' ) ) ) ),
	'A query naming notes among other types still runs.'
);
wpyeg_test_assert(
	0 === wpyeg_defaults_empty_comment_queries( null, wpyeg_test_comment_query( array( 'count' => true ) ) ),
	'A count query is answered with 0, not an array.'
);
wpyeg_test_assert(
	array() === wpyeg_defaults_empty_comment_queries( null, null ),
	'A query object without query vars is answered empty rather than let through.'
);

wpyeg_test_assert( false === wpyeg_defaults_remove_comment_blocks( false ), 'A prior deny-all (false) is preserved, not expanded.' );
wpyeg_test_assert( array() === wpyeg_defaults_remove_comment_blocks( array() ), 'An empty allowlist is a deliberate deny-all and stays empty.' );

$wpyeg_test_blocks = wpyeg_defaults_remove_comment_blocks( array( 'core/paragraph', 'core/comments', 'core/latest-comments', 'core/image' ) );
wpyeg_test_assert( array( 'core/paragraph', 'core/image' ) === array_values( $wpyeg_test_blocks ), 'Comment blocks are removed from an explicit allowlist; everything else survives.' );

/*
 * Jetpack and the XML-RPC endpoint block.
 *
 * Jetpack talks to WordPress.com over XML-RPC, so the blanket 403 breaks the
 * connection. The plugin warns and then obeys: it never quietly refuses to do
 * what the toggle says, because a control that lies is worse than one that
 * warns. The default is already off, so nothing breaks unattended.
 */
$jetpack_schema = wpyeg_defaults_schema();
wpyeg_test_assert( 'no' === $jetpack_schema['block_xmlrpc_endpoint']['default'], 'The endpoint block ships off, so activating the plugin cannot break a Jetpack connection.' );
wpyeg_test_assert(
	false !== strpos( $jetpack_schema['block_xmlrpc_endpoint']['help'], 'unless connection and feature testing proves' ),
	'The help text states the rule: leave XML-RPC reachable while Jetpack is active until testing proves otherwise.'
);

wpyeg_test_assert( '' === wpyeg_defaults_jetpack_warning( 'block_xmlrpc_endpoint' ), 'With Jetpack absent, no warning is shown.' );
wpyeg_test_assert( '' === wpyeg_defaults_jetpack_warning( 'disable_rest' ), 'The warning belongs to the endpoint block, not to every setting.' );

define( 'JETPACK__VERSION', '14.0' );
wpyeg_test_assert( '' !== wpyeg_defaults_jetpack_warning( 'block_xmlrpc_endpoint' ), 'With Jetpack active, the endpoint block carries a warning.' );
wpyeg_test_assert( '' === wpyeg_defaults_jetpack_warning( 'disable_rest' ), 'Even with Jetpack active the warning stays on its own setting.' );

/*
 * Every public filter is documented.
 *
 * The reference doc already tracks the schema; filters had no such guard, and
 * five of the eight had drifted out of it entirely. A filter nobody can find is
 * not an extension point.
 */
$plugin_source = file_get_contents( dirname( __DIR__ ) . '/plugin/sane-defaults/sane-defaults.php' );
$filter_doc    = file_get_contents( dirname( __DIR__ ) . '/docs/wordpress-default-settings.md' );

// Actions count too. The scan used to read apply_filters() only, so
// wpyeg_disable_ai_connectors — a seam the AI-connector default advertises in
// its own help text — was never held to this.
preg_match_all( "/(?:apply_filters|do_action)\(\s*'(wpyeg_[a-z_]+)'/", $plugin_source, $filter_matches );
$public_filters = array_values( array_unique( $filter_matches[1] ) );

wpyeg_test_assert( count( $public_filters ) > 0, 'Hook scan found at least one public hook.' );

foreach ( $public_filters as $filter ) {
	wpyeg_test_assert(
		false !== strpos( $filter_doc, '`' . $filter . '`' ),
		"Public hook {$filter} is documented in the reference doc."
	);
}

/*
 * Version consistency: the plugin header, readme.txt's Stable tag, and the
 * newest changelog heading all name the same release.
 *
 * Nothing checks that a release with behaviour changes carries an upgrade
 * notice — that judgement is not mechanisable — but these three drifting apart
 * is, and it is the cheap half of the same problem.
 */
preg_match( '/^ \* Version:\s+(\S+)$/m', $plugin_source, $header_version );
preg_match( '/^Stable tag:\s+(\S+)$/m', $plugin_readme, $stable_tag );
preg_match( '/^= (\d+\.\d+\.\d+) =$/m', $plugin_readme, $newest_entry );

wpyeg_test_assert( ! empty( $header_version[1] ), 'The plugin header declares a version.' );
wpyeg_test_assert(
	isset( $stable_tag[1] ) && $header_version[1] === $stable_tag[1],
	"readme.txt's Stable tag matches the plugin header version ({$header_version[1]})."
);
wpyeg_test_assert(
	isset( $newest_entry[1] ) && $header_version[1] === $newest_entry[1],
	"The newest changelog entry is for the version being shipped ({$header_version[1]})."
);

/*
 * Password policy role scoping.
 *
 * The ordering assertion is the important one: breach screening must run before
 * the role gate, or an exempt account skips the one rule that costs nothing.
 */
$make_user = static function ( array $roles ) {
	$user             = new stdClass();
	$user->ID         = 1;
	$user->user_login = 'jsmith';
	$user->user_email = 'jsmith@example.com';
	$user->roles      = $roles;

	return $user;
};

wpyeg_test_assert( true === wpyeg_defaults_password_enforced_for_user( $make_user( array( 'administrator' ) ) ), 'A privileged role is enforced.' );
wpyeg_test_assert( false === wpyeg_defaults_password_enforced_for_user( $make_user( array( 'subscriber' ) ) ), 'A subscriber-only account is exempt by default.' );
wpyeg_test_assert( true === wpyeg_defaults_password_enforced_for_user( $make_user( array( 'subscriber', 'editor' ) ) ), 'Holding any non-exempt role enforces — exemption needs every role to be exempt.' );
wpyeg_test_assert( true === wpyeg_defaults_password_enforced_for_user( $make_user( array() ) ), 'An empty role set enforces: unknown means strict.' );
wpyeg_test_assert( true === wpyeg_defaults_password_enforced_for_user( null ), 'No user context at all enforces.' );

// The gate is filterable, and can be emptied to enforce on everyone.
$GLOBALS['wpyeg_test_filter_values']['wpyeg_weak_roles'] = array( 'subscriber', 'author' );
wpyeg_test_assert( false === wpyeg_defaults_password_enforced_for_user( $make_user( array( 'author' ) ) ), 'wpyeg_weak_roles can widen the exempt list.' );
$GLOBALS['wpyeg_test_filter_values']['wpyeg_weak_roles'] = array();
wpyeg_test_assert( true === wpyeg_defaults_password_enforced_for_user( $make_user( array( 'subscriber' ) ) ), 'An empty exempt list enforces on everyone.' );
unset( $GLOBALS['wpyeg_test_filter_values']['wpyeg_weak_roles'] );

// Breach screening is not role-scoped: it runs ahead of the gate.
$source_of_validator = $plugin_source;
$fn_start            = strpos( $source_of_validator, 'function wpyeg_defaults_validate_password(' );
$fn_body             = substr( $source_of_validator, $fn_start, 3000 );
$pwned_at            = strpos( $fn_body, 'wpyeg_defaults_password_is_pwned(' );
$gate_at             = strpos( $fn_body, 'wpyeg_defaults_password_enforced_for_user(' );
wpyeg_test_assert(
	false !== $pwned_at && false !== $gate_at && $pwned_at < $gate_at,
	'Breach screening runs before the role gate, so an exempt account is still screened.'
);

/*
 * wp-config.php override notices.
 *
 * The settings screen claims a constant supersedes a control, disables that
 * control, and says why. A claim like that is worth exactly what it costs to
 * verify, and until now it cost nothing: this feature shipped in 8376e0a and
 * 8bb0fc7 with no assertions at all. The honest-UI lesson it is meant to teach
 * is only true while the code behind it stays true.
 *
 * READ THIS BEFORE ADDING TO THIS BLOCK. PHP constants cannot be undefined, so
 * this runs last and in one direction only: every unlocked assertion happens
 * before the first define(), and the constants are defined least-precedent
 * first. Moving any of it earlier breaks the core-update assertions above,
 * which require the filters to still be registered.
 */
$GLOBALS['wpyeg_test_hooks'] = array();

// Unlocked: nothing claimed, nothing disabled.
wpyeg_test_assert( null === wpyeg_defaults_config_lock( 'core_update_policy' ), 'With no constants set, the core-update control is not reported as locked.' );
wpyeg_test_assert( null === wpyeg_defaults_config_lock( 'limit_unfiltered_html_to_admins' ), 'With no constants set, the unfiltered-HTML control is not reported as locked.' );
wpyeg_test_assert( null === wpyeg_defaults_config_lock( 'disable_comments' ), 'Settings no constant supersedes are never reported as locked.' );

/*
 * Why the hidden inputs in the render matter. A disabled control submits
 * nothing, so without them a save under a constant would put the schema
 * default into storage and silently discard the operator's stored preference.
 * The sanitizer's fallback is real — assert it, so the reason the carry exists
 * cannot be forgotten and deleted as dead weight.
 */
$absent = wpyeg_defaults_sanitize( array() );
wpyeg_test_assert( 'minor' === $absent['core_update_policy'], 'A select absent from the POST falls back to its schema default.' );
wpyeg_test_assert( 'no' === $absent['disable_comments'], 'A toggle absent from the POST is stored as off.' );

// The carry itself is asserted against rendered markup at the end of this file,
// once the wp-config constants that trigger a lock have been defined.

/*
 * DISALLOW_UNFILTERED_HTML is independent of the update constants. Core strips
 * unfiltered_html from every role when it is set, administrators included, so
 * this setting cannot add anything on top of it and the screen has to say so.
 */
define( 'DISALLOW_UNFILTERED_HTML', true );
$lock_unfiltered = wpyeg_defaults_config_lock( 'limit_unfiltered_html_to_admins' );
wpyeg_test_assert( null !== $lock_unfiltered, 'DISALLOW_UNFILTERED_HTML is reported on the unfiltered-HTML control.' );
wpyeg_test_assert( false !== strpos( $lock_unfiltered, 'DISALLOW_UNFILTERED_HTML' ), 'The notice names the constant that already did the job.' );
wpyeg_test_assert( null === wpyeg_defaults_config_lock( 'core_update_policy' ), 'DISALLOW_UNFILTERED_HTML is reported on its own control and not on the core-update one.' );

// Least precedent first: DISALLOW_FILE_MODS alone.
define( 'DISALLOW_FILE_MODS', true );
$lock_file_mods = wpyeg_defaults_config_lock( 'core_update_policy' );
wpyeg_test_assert( null !== $lock_file_mods, 'DISALLOW_FILE_MODS is reported on the core-update control.' );
wpyeg_test_assert( false !== strpos( $lock_file_mods, 'DISALLOW_FILE_MODS' ), 'The notice names DISALLOW_FILE_MODS as the constant responsible.' );

// AUTOMATIC_UPDATER_DISABLED is checked first, so it now wins.
define( 'AUTOMATIC_UPDATER_DISABLED', true );
$lock_updater = wpyeg_defaults_config_lock( 'core_update_policy' );
wpyeg_test_assert( false !== strpos( $lock_updater, 'AUTOMATIC_UPDATER_DISABLED' ), 'AUTOMATIC_UPDATER_DISABLED takes precedence over DISALLOW_FILE_MODS in the notice.' );

// Neither of those stops the plugin filtering; core is what declines to update.
$GLOBALS['wpyeg_test_hooks'] = array();
wpyeg_defaults_bootstrap();
wpyeg_test_assert( null !== wpyeg_test_find_hook( 'allow_minor_auto_core_updates' ), 'The update constants report core behaviour; they do not change what the plugin registers.' );

/*
 * WP_AUTO_UPDATE_CORE is different in kind, and this is the assertion that
 * makes the notice honest rather than decorative. It says the control cannot
 * take effect, so the plugin has to actually stand down — not merely grey the
 * control out while its filters keep running.
 */
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
$lock_core = wpyeg_defaults_config_lock( 'core_update_policy' );
wpyeg_test_assert( false !== strpos( $lock_core, 'WP_AUTO_UPDATE_CORE' ), 'WP_AUTO_UPDATE_CORE takes precedence over the updater constants in the notice.' );

$GLOBALS['wpyeg_test_hooks']  = array();
$GLOBALS['wpyeg_test_option'] = array( 'core_update_policy' => 'all' );
wpyeg_defaults_bootstrap();
unset( $GLOBALS['wpyeg_test_option'] );

wpyeg_test_assert( null === wpyeg_test_find_hook( 'allow_minor_auto_core_updates' ), 'Under WP_AUTO_UPDATE_CORE the plugin registers no minor-update filter.' );
wpyeg_test_assert( null === wpyeg_test_find_hook( 'allow_major_auto_core_updates' ), 'Under WP_AUTO_UPDATE_CORE the plugin registers no major-update filter.' );
wpyeg_test_assert( null === wpyeg_test_find_hook( 'allow_dev_auto_core_updates' ), 'Under WP_AUTO_UPDATE_CORE the plugin registers no dev-update filter, so the constant is genuinely obeyed.' );

/*
 * Mail deliverability warning.
 *
 * The value of this setting is entirely in when it stays quiet: a notice that
 * cries wolf on every staging box teaches people to ignore notices. So the
 * cases below are half negative on purpose.
 */
$GLOBALS['wpyeg_test_filter_values']['wp_mail_from'] = 'wordpress@realdomain.ca';
wpyeg_test_assert( false === wpyeg_defaults_mail_is_risky(), 'A plain deliverable domain is not flagged.' );

$GLOBALS['wpyeg_test_filter_values']['wp_mail_from'] = 'hello@agency.co.uk';
wpyeg_test_assert( false === wpyeg_defaults_mail_is_risky(), 'A multi-part public suffix is not flagged.' );

foreach ( array(
	'wordpress@example.com'  => 'the RFC 2606 example domain',
	'wordpress@mysite.local' => 'an mDNS .local address',
	'wordpress@staging.test' => 'a reserved .test address',
	'wordpress@localhost'    => 'a bare localhost address',
	'not-an-email'           => 'a value that is not an address at all',
) as $address => $description ) {
	$GLOBALS['wpyeg_test_filter_values']['wp_mail_from'] = $address;
	wpyeg_test_assert( true === wpyeg_defaults_mail_is_risky(), "The deliverability check flags {$description}." );
}

unset( $GLOBALS['wpyeg_test_filter_values']['wp_mail_from'] );

// The notice ships on, and hangs off admin_notices rather than intervening in mail.
$GLOBALS['wpyeg_test_hooks'] = array();
wpyeg_defaults_bootstrap();
$mail_notice = wpyeg_test_find_hook( 'admin_notices' );
wpyeg_test_assert( null !== $mail_notice, 'The deliverability notice registers on admin_notices.' );
wpyeg_test_assert( 'wpyeg_defaults_render_mail_config_notice' === $mail_notice['callback'], 'It is the deliverability renderer that is hooked.' );

$mail_schema = wpyeg_defaults_schema();
wpyeg_test_assert( 'yes' === $mail_schema['mail_deliverability_notice']['default'], 'The deliverability notice ships on.' );
wpyeg_test_assert( 'email' === $mail_schema['mail_deliverability_notice']['group'], 'It lives in its own Email group.' );

// Off means silent: no hook at all, rather than a hook that returns early.
$GLOBALS['wpyeg_test_option'] = array( 'mail_deliverability_notice' => 'no' );
$GLOBALS['wpyeg_test_hooks']  = array();
wpyeg_defaults_bootstrap();
unset( $GLOBALS['wpyeg_test_option'] );
wpyeg_test_assert( null === wpyeg_test_find_hook( 'admin_notices' ), 'Turning the notice off unhooks it entirely.' );

/*
 * The mail warning's three gates, each reachable now that the decision is its
 * own function. The environment one is the reason it is: a local development
 * site reports 'local' and nothing in-process talks it out of that, so before
 * this split the production branch could not be exercised at all.
 */
$GLOBALS['wpyeg_test_filter_values']['wp_mail_from'] = 'wordpress@mysite.local';
$GLOBALS['wpyeg_test_environment']                   = 'production';
$GLOBALS['wpyeg_test_can']                           = true;
wpyeg_test_assert( true === wpyeg_defaults_should_warn_about_mail(), 'A risky address on a production site warns.' );

$GLOBALS['wpyeg_test_environment'] = 'local';
wpyeg_test_assert( false === wpyeg_defaults_should_warn_about_mail(), 'The same address on a local site stays silent, because there it is correct.' );

$GLOBALS['wpyeg_test_environment'] = 'staging';
wpyeg_test_assert( true === wpyeg_defaults_should_warn_about_mail(), 'Staging is not local: a staging site that cannot send mail is worth saying so.' );

$GLOBALS['wpyeg_test_can'] = false;
wpyeg_test_assert( false === wpyeg_defaults_should_warn_about_mail(), 'Someone who cannot fix it is not shown it.' );

$GLOBALS['wpyeg_test_can']                           = true;
$GLOBALS['wpyeg_test_filter_values']['wp_mail_from'] = 'hello@realdomain.ca';
wpyeg_test_assert( false === wpyeg_defaults_should_warn_about_mail(), 'A deliverable address on production says nothing.' );

unset( $GLOBALS['wpyeg_test_filter_values']['wp_mail_from'], $GLOBALS['wpyeg_test_environment'], $GLOBALS['wpyeg_test_can'] );

/*
 * A locked control, as it actually renders.
 *
 * This has to run last: it depends on WP_AUTO_UPDATE_CORE and
 * DISALLOW_UNFILTERED_HTML being defined, and a constant cannot be undefined
 * again. Both are set by the config-lock assertions above.
 *
 * A disabled control is not submitted, so without a hidden carry the next save
 * would read the locked setting as unset and quietly overwrite a stored
 * preference the user cannot currently see or change. The sanitizer's fallback
 * for an absent value is asserted above; this is the other half.
 */
$GLOBALS['wpyeg_test_option'] = array(
	'core_update_policy'              => 'all',
	'limit_unfiltered_html_to_admins' => 'yes',
);

ob_start();
wpyeg_defaults_render_settings_page();
$locked_markup = ob_get_clean();

unset( $GLOBALS['wpyeg_test_option'] );

wpyeg_test_assert(
	false !== strpos( $locked_markup, '<input type="hidden" name="wpyeg_better_by_default[core_update_policy]" value="all" />' ),
	'A locked select carries its current value in a hidden input.'
);
wpyeg_test_assert(
	false !== strpos( $locked_markup, '<input type="hidden" name="wpyeg_better_by_default[limit_unfiltered_html_to_admins]" value="yes" />' ),
	'A locked toggle carries "yes" so a save cannot flip it off.'
);
wpyeg_test_assert(
	1 === preg_match( '/<select id="wpyeg-defaults-core-update-policy"[^>]* disabled="disabled"/', $locked_markup ),
	'The locked control is disabled rather than left looking effective.'
);
wpyeg_test_assert(
	false !== strpos( $locked_markup, 'WP_AUTO_UPDATE_CORE' ),
	'The locked control says which constant supersedes it.'
);

// A toggle that is locked while stored off carries nothing: sanitize treats any
// submitted value as checked, so carrying "no" would switch it on.
$GLOBALS['wpyeg_test_option'] = array( 'limit_unfiltered_html_to_admins' => 'no' );

ob_start();
wpyeg_defaults_render_settings_page();
$locked_off_markup = ob_get_clean();

unset( $GLOBALS['wpyeg_test_option'] );

wpyeg_test_assert(
	false === strpos( $locked_off_markup, '<input type="hidden" name="wpyeg_better_by_default[limit_unfiltered_html_to_admins]"' ),
	'A locked toggle stored off carries nothing, because any carried value would read as on.'
);

fwrite( STDOUT, "Better by Default policy tests passed.\n" );
