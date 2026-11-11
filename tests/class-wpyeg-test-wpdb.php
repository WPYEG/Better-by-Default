<?php
/**
 * Minimal wpdb test double.
 *
 * Just enough of the database object for the one statement the plugin issues
 * through it in this suite: the clean-up that deletes the breach replies an
 * earlier version kept. The double applies that statement to a staged list of
 * option names, LIKE escapes included, so a test can assert which rows survive
 * rather than how the statement is spelled.
 *
 * @package BetterByDefault
 */

/** Database test double. */
class Wpyeg_Test_Wpdb {
	/**
	 * Options table name.
	 *
	 * @var string
	 */
	public $options = 'wp_options';

	/**
	 * Staged option names.
	 *
	 * @var string[]
	 */
	public $rows = array();

	/**
	 * Statements received, as prepared.
	 *
	 * @var string[]
	 */
	public $queries = array();

	/**
	 * Whether the next statement is refused, as a database error would.
	 *
	 * @var bool
	 */
	public $refuse = false;

	/**
	 * LIKE-escape test double. Mirrors wpdb::esc_like().
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	public function esc_like( $text ) {
		return addcslashes( $text, '_%\\' );
	}

	/**
	 * Prepare test double: quoted %s placeholders only.
	 *
	 * @param string $query   Statement with %s placeholders.
	 * @param mixed  ...$args Values, in order.
	 * @return string
	 */
	public function prepare( $query, ...$args ) {
		$offset = 0;

		foreach ( $args as $arg ) {
			$position = strpos( $query, '%s', $offset );

			if ( false === $position ) {
				break;
			}

			$quoted = "'" . str_replace( "'", "''", (string) $arg ) . "'";
			$query  = substr_replace( $query, $quoted, $position, 2 );
			$offset = $position + strlen( $quoted );
		}

		return $query;
	}

	/**
	 * Query test double.
	 *
	 * Applies `DELETE FROM <options> WHERE ( option_name LIKE a OR option_name
	 * LIKE b ) AND option_name NOT IN ( c, d )` to the staged rows. Any other
	 * statement is recorded and changes nothing, which the row assertions catch.
	 *
	 * @param string $query Prepared statement.
	 * @return int|false Rows deleted, or false when the statement is refused.
	 */
	public function query( $query ) {
		$this->queries[] = $query;

		if ( $this->refuse ) {
			return false;
		}

		$shape = "/\\ADELETE FROM wp_options WHERE \\( option_name LIKE '([^']*)' OR option_name LIKE '([^']*)' \\) AND option_name NOT IN \\( '([^']*)', '([^']*)' \\)\\z/";

		if ( 1 !== preg_match( $shape, $query, $parts ) ) {
			return 0;
		}

		$before = count( $this->rows );
		$kept   = array();

		foreach ( $this->rows as $name ) {
			$named  = self::like( $parts[1], $name ) || self::like( $parts[2], $name );
			$spared = in_array( $name, array( $parts[3], $parts[4] ), true );

			if ( ! $named || $spared ) {
				$kept[] = $name;
			}
		}

		$this->rows = $kept;

		return $before - count( $this->rows );
	}

	/**
	 * Whether a value matches a SQL LIKE pattern.
	 *
	 * `%` matches any run of characters and `_` any one character, unless a
	 * backslash escapes them, which is the difference wpdb::esc_like() makes:
	 * unescaped, the underscores in an option name would each match anything.
	 * Compared without case, as MySQL's default collations do.
	 *
	 * @param string $pattern LIKE pattern.
	 * @param string $value   Value to test.
	 * @return bool
	 */
	public static function like( $pattern, $value ) {
		$regex  = '';
		$length = strlen( $pattern );

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $pattern[ $i ];

			if ( '\\' === $char && $i + 1 < $length ) {
				++$i;
				$regex .= preg_quote( $pattern[ $i ], '/' );
			} elseif ( '%' === $char ) {
				$regex .= '.*';
			} elseif ( '_' === $char ) {
				$regex .= '.';
			} else {
				$regex .= preg_quote( $char, '/' );
			}
		}

		return 1 === preg_match( '/\A' . $regex . '\z/is', $value );
	}
}
