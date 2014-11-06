<?php
/**
 * Created by PhpStorm.
 * User: jloosli
 * Date: 11/6/14
 * Time: 12:05 PM
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class POM_Bloom_Program {
    /**
     * Message displayed if user isn't subscribed to the program.
     * @var string
     * @since 1.0.0
     */
    public $msg_not_subscribed;

    /**
     * Message displayed if user isn't logged in.
     * @var string
     * @since 1.0.0
     */
    public $msg_not_logged_in;

    /**
     * Array of available routes
     * @var array
     */
    public $routes;

    /**
     * Partial directory
     * @var string
     */
    public $partial_directory;

    /**
     * Partial variables used in partials
     * @var mixed
     */
    protected $pvars;

    public function __construct($parent) {
        $this->parent = $parent;
        $this->setup();

        add_shortcode('bloom-program',array($this,'bloom_shortcode_func'));
    }

    public function bloom_shortcode_func(  ) {
        $this->enqueue_stuff();

        if(!is_user_logged_in()) {
            return sprintf($this->msg_not_logged_in);
        }
        if(!$this->check_access()) {
            return sprintf($this->msg_not_subscribed, get_page_link(get_option($this->parent->settings->base.'sales_page')));
        }

        $route = $this->getRoute();
        $html = $this->page($route);

        return $html;
    }

    protected function getRoute() {
        if(empty($_GET) || empty($_GET['page'])) {
            $route = array_filter($this->routes, function($route) {
                return isset($route['default']) && $route['default'] === true;
            });
        } else {
            $route = array_filter($this->routes, function($route) {
                return $route['page'] === strtolower($_GET['page']);
            });
            if (!$route) {
                $route = [['page' => 'bad']];
            }
        }

        return end($route);

    }

    protected function page($route) {
        $html = "<div id='bloom'>\n";
        $html .= $this->get_partial('nav',['active'=>'preferences']);
        $html .= "</div>";
        $html .= $this->get_partial($route['template'], $route['vars']());
        return $html."You've made it this far.\n";
    }

    protected function enqueue_stuff() {
        // Enqueue scripts and css here.
        wp_enqueue_script( $this->parent->_token . '-frontend' );
    }

    protected function check_access() {
        return current_user_can("manage_options") ||
               wlmapi_is_user_a_member(
                   get_option($this->parent->settings->base.'membership_level'),
                   get_current_user_id()
               );
    }

    /**
     * Get template partial
     * @param $partial
     *
     * @return string
     */
    protected function get_partial($partial, $vars = []) {
        $html = '';
        $thePartial = $this->partial_directory . $partial . ".php";
        if(file_exists($thePartial)) {
            ob_start();
            include $thePartial;
            $html = ob_get_clean();

        }
        return $html;
    }

    protected function setup() {
        $this->msg_not_subscribed = <<<MESSAGE
Sorry. You're not currently subscribed to this program. Please go to <a href='%s'>Bloom Program Page</a> for more information.
MESSAGE;
        $this->msg_not_logged_in = <<<MESSAGE
Looks like you're not logged in. Please try logging in above for this program to display.
MESSAGE;
        $this->partial_directory = __DIR__.'/../assets/partials/';

        $this->routes= [
            [
                'page' => 'options',
                'default' => true
            ],
            [
                'page' => 'preferences',
                'template' => 'preferences',
                'vars' => function() {
                    return [
                        'current_user' => wp_get_current_user()
                    ];
                }
            ],
            [
                'page' => 'nav',
                'template' => 'nav'
            ]
        ];

    }
}