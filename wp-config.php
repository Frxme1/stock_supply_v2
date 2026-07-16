<?php

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'tbssta_supply' );

/** Database username */
define( 'DB_USER', 'tbssta_supply' );

/** Database password */
define( 'DB_PASSWORD', 'fLk17!8g7' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',         'SZP,{K=>d4.ukrFpWfbbtMr``FTnQ./zP<arp2K9fNYh~72D5wT/>`k>o,lP,{sv' );
define( 'SECURE_AUTH_KEY',  'z[H*1g>1Nvsx %B$9PkG fy(^0_w:gL/Oi:owVZfLvzZ3BWAl{E%;Y-K{qR~-y(M' );
define( 'LOGGED_IN_KEY',    'x#hkBQaiXhLbb4;B?6dY/;v>tKu& ~`u3/6di~n6>.Szo3~Eg&=)@V-O2+^WotOa' );
define( 'NONCE_KEY',        'b>:A7+4v/`hc*.RcXTjO5r-&dk6/eZ>:>b&Z5IX{(BY=gG0:M1-a;=0Vnbt$=&##' );
define( 'AUTH_SALT',        'jU;h`^^:=LZe_)p*=+xyl*TH;*#$w6,q/d/!C2rTwoVWW~}#a,(]T@lp2<+?Q(Y6' );
define( 'SECURE_AUTH_SALT', 'NWhst9~m,zm2Er?[iN%gz7cLa#[+e@]}>c9):8zbS&%(IpI? XM(PF.}:L5zic!-' );
define( 'LOGGED_IN_SALT',   '!/YQ|M< &#hK<1M3W65Dxc:61Hr&nsU%~B o|Vl_MYf+_)mDF,4L6jXtBls7M672' );
define( 'NONCE_SALT',       '7vfL@A>>:0V,gk`o?_:]Xm+RB~o`x`/uWHb}$nYr/A:n1Ug(`&EMz{%L.Cf,)&UQ' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
