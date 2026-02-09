<?php
/**
 * Main tool class
 *
 * @package Tutor\Tools
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.6.0
 */

namespace TutorPro\Tools;

use AllowDynamicProperties;
use TUTOR\Container;
use TUTOR\Singleton;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Tools
 *
 * @since 3.6.0
 */
#[AllowDynamicProperties]
class Tools extends Singleton {

	/**
	 * Set tools property
	 *
	 * @since 3.6.0
	 */
	public function __construct() {
		$this->exporter        = Container::make( Exporter::class );
		$this->importer        = Container::make( Importer::class );
		$this->course_exporter = Container::make( CourseExporter::class );
		$this->helper          = Container::make( Helper::class );
	}
}
