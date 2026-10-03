<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'og_of_wp_test' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          'wQ-Yr6F[D]Hi3RLD.=<Igo~E<P.Dh:%Usrvb6:x]6!`<b2Jik,e~g6$j+(m`fEh<' );
define( 'SECURE_AUTH_KEY',   'Sz@?s3B> 0gx68MQVR6GU#j*F1mIaB/6r <rhqQ-{As2S0Q)cfCLa6>V=xr$/yv3' );
define( 'LOGGED_IN_KEY',     '1YUEFD/`?<YpBH/b_=v}G_=_~#qZmUP?:;$YgCG`9MDEy7Nv`Q_(9f3T2e)_`62V' );
define( 'NONCE_KEY',         'xw1.sQ][0GD9j?h JC7Ut1_$iX@#X1Pm[;V$bDH?}~DS]!Gt|ZX9>[GiYV)9)6[T' );
define( 'AUTH_SALT',         '(`?=#,f:DDu4!VK`M19uCLIvzD8/SvHc?HGG[VUTNT)M-hw.e,acRykFSgI3zd$W' );
define( 'SECURE_AUTH_SALT',  'Gz4[xIltycJw.SJ[4S*6]Kqn<,<]i-DSNp74v(Y+x4n}t~^[Q9D|vCek+x|dy.3[' );
define( 'LOGGED_IN_SALT',    's&Z{WgYj&UzTU%Q[9vw<VC`|qd=G6PHvKhS4zdfk9zA Z}TR?_+!4([{[1:`XO=/' );
define( 'NONCE_SALT',        'p>sPE~Q?.CwN%LcH]M cqaG+<g YvE!G/U+$;V`S9QeNQG*qfh>lEc]-MR{3.e/d' );
define( 'WP_CACHE_KEY_SALT', 'xi[]nIM]{r?`Z)NQg>La$D~Zl-}0+K,.DTi/{~v3J`>yb:<<!j;3[C=^BRoTCl{$' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
