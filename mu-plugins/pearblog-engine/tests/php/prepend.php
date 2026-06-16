<?php
/**
 * Auto-prepend file for PHPUnit.
 *
 * Ensures $_SERVER['REQUEST_TIME_FLOAT'] is set before PHPUnit's timer
 * initializes (required by older PHPUnit PHAR builds that don't fall back
 * gracefully when this value is missing).
 */
if ( ! isset( $_SERVER['REQUEST_TIME_FLOAT'] ) ) {
    $_SERVER['REQUEST_TIME_FLOAT'] = microtime( true );
}
