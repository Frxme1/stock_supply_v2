<?php
/**
 *  Frontend php file for Business Review module.
 *
 *  @package UABB Business Review file Frontend.php file
 */

$module = $module->render();
echo ( ! is_null( $module ) ? wp_kses_post( $module ) : '' );


