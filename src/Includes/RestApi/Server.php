<?php
/**
 * Initialize the REST API.
 *
 * @since 1.2.5
 *
 * @package    WordPress
 * @subpackage Plausible Analytics
 */

namespace Plausible\Analytics\WP\Includes\RestApi;

// Bailout, if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loading the REST API and all REST API namespaces.
 */
class Server {

	/**
	 * Endpoints.
	 *
	 * @var array
	 */
	protected $controllers = [];

	public function __construct() {
		// WP REST API.
		$this->init();
	}

	/**
	 * Hook into WordPress ready to init the REST API as needed.
	 */
	public function init() {
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ], 10 );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		foreach ( $this->get_rest_namespaces() as $namespace => $controllers ) {
			foreach ( $controllers as $controller_name => $controller_class ) {
				$controller_class                                    = self::get_controllers_namespace() . $controller_class;
				$this->controllers[ $namespace ][ $controller_name ] = new  $controller_class();
				$this->controllers[ $namespace ][ $controller_name ]->register_routes();
			}
		}
	}

	/**
	 * Get API namespaces - new namespaces should be registered here.
	 *
	 * @return array List of Namespaces and Main controller classes.
	 */
	protected function get_rest_namespaces() {
		return [
			'stats/api/' => $this->get_controllers(),
		];
	}

	/**
	 * List of controllers.
	 *
	 * @return array
	 */
	protected function get_controllers() {
		return [
			'event' => 'RestEventController',
		];
	}

	/**
	 * Controllers in the namespace.
	 * @return string
	 */
	protected static function get_controllers_namespace() {
		return __NAMESPACE__ . '\Controllers\\';
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_path() {
		return dirname( __DIR__ );
	}
}
