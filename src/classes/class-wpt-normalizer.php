<?php
/**
 * XPoster Normalizer fallback class
 *
 * @category Fallbacks
 * @package  XPoster
 * @author   Joe Dolson
 * @license  GPLv3
 * @link     https://www.joedolson.com/wp-to-twitter/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalizer is a PHP fallback implementation of the Normalizer class provided by the intl extension.
 *
 * It has been validated with Unicode 6.1 Normalization Conformance Test.
 * See http://www.unicode.org/reports/tr15/ for detailed info about Unicode normalizations.
 */
class WPT_Normalizer {
	const

	NONE    = 1,
	FORM_D  = 2, NFD  = 2,
	FORM_KD = 3, NFKD = 3,
	FORM_C  = 4, NFC  = 4,
	FORM_KC = 5, NFKC = 5;

	/**
	 * Character containers.
	 *
	 * @var $c
	 */
	protected static $c;
	/**
	 * Character containers.
	 *
	 * @var $d
	 */
	protected static $d;
	/**
	 * Character containers.
	 *
	 * @var $kd
	 */
	protected static $kd;
	/**
	 * Character containers.
	 *
	 * @var $cc
	 */
	protected static $cc;
	/**
	 * U length mask.
	 *
	 * @var $ulen_mask
	 */
	protected static $ulen_mask = array(
		"\xC0" => 2,
		"\xD0" => 2,
		"\xE0" => 3,
		"\xF0" => 4,
	);

	/**
	 * Index of ASCII characters.
	 *
	 * @var $ascii
	 */
	protected static $ascii = "\x20\x65\x69\x61\x73\x6E\x74\x72\x6F\x6C\x75\x64\x5D\x5B\x63\x6D\x70\x27\x0A\x67\x7C\x68\x76\x2E\x66\x62\x2C\x3A\x3D\x2D\x71\x31\x30\x43\x32\x2A\x79\x78\x29\x28\x4C\x39\x41\x53\x2F\x50\x22\x45\x6A\x4D\x49\x6B\x33\x3E\x35\x54\x3C\x44\x34\x7D\x42\x7B\x38\x46\x77\x52\x36\x37\x55\x47\x4E\x3B\x4A\x7A\x56\x23\x48\x4F\x57\x5F\x26\x21\x4B\x3F\x58\x51\x25\x59\x5C\x09\x5A\x2B\x7E\x5E\x24\x40\x60\x7F\x00\x01\x02\x03\x04\x05\x06\x07\x08\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F";

	/**
	 * Check whether is already normalized.
	 *
	 * @param string $s String to check.
	 * @param object $form Self.
	 *
	 * @return boolean
	 */
	public static function is_normalized( $s, $form = self::NFC ) {
		if ( strspn( $s, self::$ascii ) === strlen( $s ) ) {
			return true;
		}
		if ( self::NFC === $form && preg_match( '//u', $s ) && ! preg_match( '/[^\x00-\x{2FF}]/u', $s ) ) {
			return true;
		}
		return false; // Pretend false as quick checks implementented in PHP won't be so quick.
	}

	/**
	 * Normalize a string.
	 *
	 * @param string $s String to normalize.
	 * @param object $form Self.
	 *
	 * @return boolean or normalized string.
	 */
	public static function normalize( $s, $form = self::NFC ) {
		if ( ! preg_match( '//u', $s ) ) {
			return false;
		}

		switch ( $form ) {
			case self::NONE:
				return $s;
			case self::NFC:
				$c = true;
				$k = false;
				break;
			case self::NFD:
				$c = false;
				$k = false;
				break;
			case self::NFKC:
				$c = true;
				$k = true;
				break;
			case self::NFKD:
				$c = false;
				$k = true;
				break;
			default:
				return false;
		}

		if ( ! strlen( $s ) ) {
			return '';
		}

		if ( $k && empty( self::$kd ) ) {
			self::$kd = self::get_data( 'compatibilityDecomposition' );
		}

		if ( empty( self::$d ) ) {
			self::$d  = self::get_data( 'canonicalDecomposition' );
			self::$cc = self::get_data( 'combiningClass' );
		}

		if ( $c ) {
			if ( empty( self::$c ) ) {
				self::$c = self::get_data( 'canonicalComposition' );
			}
			return self::recompose( self::decompose( $s, $k ) );
		} else {
			return self::decompose( $s, $k );
		}
	}

	/**
	 * Recompose a string.
	 *
	 * @param string $s String to check.
	 *
	 * @return string
	 */
	protected static function recompose( $s ) {
		$ascii      = self::$ascii;
		$comp_map   = self::$c;
		$comb_class = self::$cc;
		$ulen_mask  = self::$ulen_mask;

		$result = '';
		$tail   = '';

		$i   = $s[0] < "\x80" ? 1 : $ulen_mask[ $s[0] & "\xF0" ];
		$len = strlen( $s );

		$last_uchr = substr( $s, 0, $i );
		$last_ucls = isset( $comb_class[ $last_uchr ] ) ? 256 : 0;

		while ( $i < $len ) {
			if ( $s[ $i ] < "\x80" ) {
				// ascii chars.
				if ( $tail ) {
					$last_uchr .= $tail;
					$tail       = '';
				}
				$j = strspn( $s, $ascii, $i + 1 );
				if ( $j ) {
					$last_uchr .= substr( $s, $i, $j );
					$i         += $j;
				}

				$result   .= $last_uchr;
				$last_uchr = $s[ $i ];
				++$i;
			} else {
				$ulen = $ulen_mask[ $s[ $i ] & "\xF0" ];
				$uchr = substr( $s, $i, $ulen );

				if ( $last_uchr < "\xE1\x84\x80" || "\xE1\x84\x92" < $last_uchr
					|| $uchr < "\xE1\x85\xA1" || "\xE1\x85\xB5" < $uchr
					|| $last_ucls ) {
					// Table lookup and combining chars composition.
					$ucls = isset( $comb_class[ $uchr ] ) ? $comb_class[ $uchr ] : 0;

					if ( isset( $comp_map[ $last_uchr . $uchr ] ) && ( ! $last_ucls || $last_ucls < $ucls ) ) {
						$last_uchr = $comp_map[ $last_uchr . $uchr ];
					} elseif ( $last_ucls === $ucls ) {
						$tail .= $uchr;
					} else {
						if ( $tail ) {
							$last_uchr .= $tail;
							$tail       = '';
						}

						$result   .= $last_uchr;
						$last_uchr = $uchr;
					}
				} else {
					// Hangul chars.
					$l = ord( $last_uchr[2] ) - 0x80;
					$v = ord( $uchr[2] ) - 0xA1;
					$t = 0;

					$uchr = substr( $s, $i + $ulen, 3 );

					if ( "\xE1\x86\xA7" <= $uchr && $uchr <= "\xE1\x87\x82" ) {
						$t            = ord( $uchr[2] ) - 0xA7;
						0 > $t && $t += 0x40;
						$ulen        += 3;
					}

					$l         = 0xAC00 + ( $l * 21 + $v ) * 28 + $t;
					$last_uchr = chr( 0xE0 | $l >> 12 ) . chr( 0x80 | $l >> 6 & 0x3F ) . chr( 0x80 | $l & 0x3F );
				}

				$i += $ulen;
			}
		}

		return $result . $last_uchr . $tail;
	}

	/**
	 * Decompose a string.
	 *
	 * @param string  $s String to check.
	 * @param boolean $c use compat map.
	 *
	 * @return string
	 */
	protected static function decompose( $s, $c ) {
		$result = '';

		$ascii      = self::$ascii;
		$decomp_map = self::$d;
		$comb_class = self::$cc;
		$ulen_mask  = self::$ulen_mask;
		if ( $c ) {
			$compat_map = self::$kd;
		}

		$c   = array();
		$i   = 0;
		$len = strlen( $s );

		while ( $i < $len ) {
			if ( $s[ $i ] < "\x80" ) {
				// ascii chars.
				if ( $c ) {
					ksort( $c );
					$result .= implode( '', $c );
					$c       = array();
				}

				$j       = 1 + strspn( $s, $ascii, $i + 1 );
				$result .= substr( $s, $i, $j );
				$i      += $j;
			} else {
				$ulen = $ulen_mask[ $s[ $i ] & "\xF0" ];
				$uchr = substr( $s, $i, $ulen );
				$i   += $ulen;

				if ( isset( $comb_class[ $uchr ] ) ) {
					// Combining chars, for sorting.
					if ( ! isset( $c[ $comb_class[ $uchr ] ] ) ) {
						$c[ $comb_class[ $uchr ] ] = '';
					}
					$c[ $comb_class[ $uchr ] ] .= isset( $compat_map[ $uchr ] ) ? $compat_map[ $uchr ] : ( isset( $decomp_map[ $uchr ] ) ? $decomp_map[ $uchr ] : $uchr );
				} else {
					if ( $c ) {
						ksort( $c );
						$result .= implode( '', $c );
						$c       = array();
					}

					if ( $uchr < "\xEA\xB0\x80" || "\xED\x9E\xA3" < $uchr ) {
						// Table lookup.
						$j = isset( $compat_map[ $uchr ] ) ? $compat_map[ $uchr ] : ( isset( $decomp_map[ $uchr ] ) ? $decomp_map[ $uchr ] : $uchr );

						if ( $uchr !== $j ) {
							$uchr = $j;

							$j    = strlen( $uchr );
							$ulen = $uchr[0] < "\x80" ? 1 : $ulen_mask[ $uchr[0] & "\xF0" ];

							if ( $ulen !== $j ) {
								// Put trailing chars in $s.
								$j -= $ulen;
								$i -= $j;

								if ( 0 > $i ) {
									$s    = str_repeat( ' ', -$i ) . $s;
									$len -= $i;
									$i    = 0;
								}

								while ( $j-- ) {
									$s[ $i + $j ] = $uchr[ $ulen + $j ];
								}

								$uchr = substr( $uchr, 0, $ulen );
							}
						}
					} else {
						// Hangul chars.
						$uchr = unpack( 'C*', $uchr );
						$j    = ( ( $uchr[1] - 224 ) << 12 ) + ( ( $uchr[2] - 128 ) << 6 ) + $uchr[3] - 0xAC80;

						$uchr = "\xE1\x84" . chr( 0x80 + (int) ( $j / 588 ) ) . "\xE1\x85" . chr( 0xA1 + (int) ( ( $j % 588 ) / 28 ) );

						if ( $j %= 28 ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.Found
							$uchr .= $j < 25 ? ( "\xE1\x86" . chr( 0xA7 + $j ) ) : ( "\xE1\x87" . chr( 0x67 + $j ) );
						}
					}

					$result .= $uchr;
				}
			}
		}

		if ( $c ) {
			ksort( $c );
			$result .= implode( '', $c );
		}

		return $result;
	}

	/**
	 * Data fetcher.
	 *
	 * @param string $file Get data file.
	 *
	 * @return file contents or false.
	 */
	protected static function get_data( $file ) {
		$file = __DIR__ . '/unidata/' . $file . '.ser';
		if ( file_exists( $file ) ) {
			return unserialize( file_get_contents( $file ) );
		} else {
			return false;
		}
	}
}
