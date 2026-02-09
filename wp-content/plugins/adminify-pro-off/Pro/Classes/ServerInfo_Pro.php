<?php

namespace WPAdminify\Pro;

use WPAdminify\Inc\Classes\ServerInfo;

// no direct access allowed
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post Columns: Featured Image and ID
 *
 * @package WP Adminify
 *
 * @author WP Adminify <support@wpadminify.com>
 */

class ServerInfo_Pro extends ServerInfo {

	/**
	 * Get Server Uptime
	 */
	public function get_server_uptime() {
		// error_reporting( 0 );
		$disabled_funcs = array();

		if ( ini_get( 'disable_functions' ) ) {
			$disabled_funcs = array_map( 'trim', explode( ',', ini_get( 'disable_functions' ) ) );
		}

		// For Windows OS
		if ( ( strpos( strtolower( PHP_OS ), 'Windows' ) !== false ) || ( strpos( strtolower( PHP_OS ), 'WINNT' ) !== false ) || ( strpos( strtolower( PHP_OS ), 'WIN32' ) !== false ) ) {

			if ( ! in_array( 'exec', $disabled_funcs ) ) {
				set_time_limit( 150 ); // 'systeminfo' command can take a while...

				$uptime = exec( 'systeminfo | find "System Up"' );
				$parts  = explode( ':', $uptime );
				$parts  = array_pop( $parts );
				$parts  = explode( ',', trim( $parts ) );

				foreach ( array( 'days', 'hours', 'mins', 'secs' ) as $k => $v ) {
					$parts[ $k ] = explode( ' ', trim( $parts[ $k ] ) );
					$$v          = ( $k ) ? str_pad( array_shift( $parts[ $k ] ), 2, '0', STR_PAD_LEFT ) : array_shift( $parts[ $k ] );
				}

				return ( $days * DAY_IN_SECONDS ) + ( $hours * HOUR_IN_SECONDS ) + ( $mins * MINUTE_IN_SECONDS ) + $secs;
			}
		} else {

			// For *nix OS

			if ( in_array( 'shell_exec', $disabled_funcs ) ) {
				$uptime_text = file_get_contents( '/proc/uptime' );
				$uptime      = substr( $uptime_text, 0, strpos( $uptime_text, ' ' ) );
			} else {
				$uptime = shell_exec( 'cut -d. -f1 /proc/uptime' );
			}

			$days  = floor( $uptime / 60 / 60 / 24 );
			$hours = str_pad( $uptime / 60 / 60 % 24, 2, '0', STR_PAD_LEFT );
			$mins  = str_pad( $uptime / 60 % 60, 2, '0', STR_PAD_LEFT );
			$secs  = str_pad( $uptime % 60, 2, '0', STR_PAD_LEFT );

			return ( $days * DAY_IN_SECONDS ) + ( $hours * HOUR_IN_SECONDS ) + ( $mins * MINUTE_IN_SECONDS ) + $secs;
		}

		return false;
	}

	/**
	 * CPU Core Count
	 */

	public function get_cpu_core_count() {
		$cmd = 'uname';

		$OS = strtolower( trim( shell_exec( $cmd ) ) );

		switch ( $OS ) {
			case ( 'linux' ):
				$cmd = 'cat /proc/cpuinfo | grep processor | wc -l';
				break;
			case ( 'freebsd' ):
				$cmd = "sysctl -a | grep 'hw.ncpu' | cut -d ':' -f2";
				break;
			default:
				unset( $cmd );
		}

		if ( isset( $cmd ) && $cmd != '' ) {
			$cpuCoreNo = intval( trim( shell_exec( $cmd ) ) );
		}

		return empty( $cpuCoreNo ) ? 1 : $cpuCoreNo;
	}

	/**
	 * Shell Enable/Disable
	 */
	public function is_shell_enable() {
		if ( function_exists( 'shell_exec' ) && ! in_array( 'shell_exec', array_map( 'trim', explode( ', ', ini_get( 'disable_functions' ) ) ) ) ) {
			// If enabled, check if shell_exec() actually have execution power
			$returnVal = shell_exec( 'cat /proc/cpuinfo' );
			if ( ! empty( $returnVal ) ) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/** CPU Load */
	public function get_cpu_load() {
		$cpu_load = 'N/A';
		if ( $this->is_shell_enable() ) {
			$cpu_load .= trim( shell_exec( "echo $((`ps aux|awk 'NR > 0 { s +=$3 }; END {print s}'| cut -d . -f 1` / `cat /proc/cpuinfo | grep cores | grep -o '[0-9]' | wc -l`))" ) );
		}
		return $cpu_load;
	}

	/**
	 * CPU Count
	 */
	public function check_cpu_count() {
		if ( $this->is_shell_enable() ) {
			$cpu_count = shell_exec( 'cat /proc/cpuinfo |grep "physical id" | sort | uniq | wc -l' );
			set_transient( 'wphave_admin_cpu_count', $cpu_count, WEEK_IN_SECONDS );
		} else {
			$cpu_count = 'N/A';
		}

		return $cpu_count;
	}


	/**
	 * ******************
	 * SERVER CPU CORE COUNT
	 * ******************
	 *
	 *  Get the count of available server CPU cores.
	 */

	public function check_core_count() {

        if ( function_exists( 'shell_exec' ) && ! in_array( 'shell_exec', array_map( 'trim', explode( ', ', ini_get( 'disable_functions' ) ) ) ) ) {
            $cmd = 'uname';
            $OS = strtolower( trim( shell_exec( $cmd ) ) );

            switch ( $OS ) {
                case ( 'linux' ):
                    $cmd = 'cat /proc/cpuinfo | grep processor | wc -l';
                    break;
                case ( 'freebsd' ):
                    $cmd = "sysctl -a | grep 'hw.ncpu' | cut -d ':' -f2";
                    break;
                default:
                    unset( $cmd );
            }

            if ( isset( $cmd ) && $cmd != '' ) {
                $cpuCoreNo = intval( trim( shell_exec( $cmd ) ) );
            }
        }

		return empty( $cpuCoreNo ) ? 1 : $cpuCoreNo;
	}
}
