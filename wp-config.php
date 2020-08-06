<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'stevetinc_db' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'uo}XC0:o99eouPDKCp|<R.J)TT$x}fI{}|)Mco/r@(T[hdABk?qvQG[2{hk?TWR&' );
define( 'SECURE_AUTH_KEY',  'p26SY;8lRO9D.b+#Pg@2jH8m%?e>mX0ER{&Ih~K<[|[xb~NeD2>:@E}Ai?QA$#+M' );
define( 'LOGGED_IN_KEY',    '?TDBrX/#]Xo%QKNW{4Tu!B*A2P*X_U>H]$rgFC=Nz#,fz0,;C4+%1$21CX<}e3,d' );
define( 'NONCE_KEY',        '-+LtS=Z mJ_iU@/%k^BNQEV}?LcCa<km4<lk}8&W,vYUg$gZ:=ggMvE{K4:Aal:s' );
define( 'AUTH_SALT',        'HH5vV^pcG4.py#pAS&!ObZd,DJKQ(WxxvB6n~W4w+s9Tp~jv/p3NAiCs7JRN,zc3' );
define( 'SECURE_AUTH_SALT', 'f)D!4(viMC?YXaM^v6M5t(=aR}1A$c)N{}s8QL@8L47KI;&o?eZkdA$aC^YYUjrB' );
define( 'LOGGED_IN_SALT',   'L~OCM:XtItHr`!TrtncchLO#RL2jA9JaH57}9n!x-?hvdUOhWLA{dW$A5oKEmT8K' );
define( 'NONCE_SALT',       '/V/bJOLJvV2`z0UvP/`GqE2*OPF(Sbe*Y,p|L]tpt:S{Cy7quP?_a-0=O9?Pb%5s' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
