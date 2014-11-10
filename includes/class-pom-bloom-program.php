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

    /**
     * Number of weeks to show in overview
     * @var int
     */
    protected $weeks_to_show;

    public function __construct( $parent ) {
        $this->parent = $parent;
        $this->setup();

        add_shortcode( 'bloom-program', array( $this, 'bloom_shortcode_func' ) );
        add_action( 'wp_ajax_pom_bloom', array( $this, 'ajax_callback' ) );
    }

    public function bloom_shortcode_func() {
        $this->enqueue_stuff();

        if ( !is_user_logged_in() ) {
            return sprintf( $this->msg_not_logged_in );
        }
        if ( !$this->check_access() ) {
            return sprintf( $this->msg_not_subscribed, get_page_link( get_option( $this->parent->settings->base . 'sales_page' ) ) );
        }

        $route = $this->getRoute();
        $html  = $this->page( $route );

        return $html;
    }

    public function ajax_callback() {
        $result = array( 'success' => false );
        switch ( $_POST['route'] ) {
            case 'preferences':
                update_user_meta( $_POST['user'], $this->parent->_token . 'preference_level', $_POST['preference'] );
                $result = array( 'success' => true );
                break;
            case 'assessments':
                $user               = (int) $_POST['user'];
                $average            = [ 'sum' => 0, 'count' => 0 ];
                $assessment_results = array_map( function ( $a ) use ( &$average ) {
                    if ( (int) $a['value'] > 0 ) {
                        $average['sum'] += (int) $a['value'];
                        $average['count'] ++;
                    }

                    return [
                        'q'      => (int) str_replace( "q_", "", $a['name'] ),
                        'rating' => (int) $a['value']
                    ];
                }, $_POST['assessment'] );

                $result = [
                    'assessment_date'    => date( "Y-m-d H:i:s" ),
                    'average'            => $average['count'] > 0 ? round( $average['sum'] / $average['count'], 1 ) : 0,
                    'assessment_results' => $assessment_results
                ];
                add_user_meta( $user, $this->parent->_token . '_assessment', $result );
                $result['success'] = true;
                break;
        }
        die( json_encode( $result ) );
    }

    protected function getRoute() {
        if ( empty( $_GET ) || empty( $_GET['page'] ) ) {
            $route = array_filter( $this->routes, function ( $route ) {
                return isset( $route['default'] ) && $route['default'] === true;
            } );
        } else {
            $route = array_filter( $this->routes, function ( $route ) {
                return $route['page'] === strtolower( $_GET['page'] );
            } );
            if ( !$route ) {
                $route = [ [ 'page' => 'bad', 'template' => 'bad' ] ];
            }
        }

        return end( $route );

    }

    protected function page( $route ) {
        $vars = [ ];
        if ( $route['vars'] && is_object( $route['vars'] ) && $route['vars'] instanceof Closure ) {
            $vars = $route['vars']();
        }
        $template = $route['template'] ? $route['template'] : $route['name'];
        $html     = "<div id='bloom'>\n";
        $html .= $this->get_partial( 'nav', [ 'active' => 'preferences' ] );
        $html .= $this->get_partial( $template, $vars );
        $html .= "</div>";

        return $html;
    }

    protected function enqueue_stuff() {
        // Enqueue scripts and css here.
        wp_enqueue_script( $this->parent->_token . '-frontend' );
        wp_localize_script(
            $this->parent->_token . '-frontend',
            'POM_BLOOM',
            array(
                'ajax_url'     => admin_url( 'admin-ajax.php' ),
                'current_user' => get_current_user_id()
            )
        );
        wp_enqueue_script( 'underscore' );
    }

    protected function check_access() {
        return current_user_can( "manage_options" ) ||
               wlmapi_is_user_a_member(
                   get_option( $this->parent->settings->base . 'membership_level' ),
                   get_current_user_id()
               );
    }

    /**
     * Get template partial
     *
     * @param $partial
     *
     * @return string
     */
    protected function get_partial( $partial, $vars = [ ] ) {
        $html       = '';
        $thePartial = $this->partial_directory . $partial . ".php";
        if ( file_exists( $thePartial ) ) {
            extract( $vars );
            ob_start();
            include $thePartial;
            $html = ob_get_clean();

        }

        return $html;
    }

    protected function generate_categories() {
        $terms = get_terms( 'bloom-categories',
            array(
                'hide_empty' => false,
                'orderby'    => 'slug',
                'order'      => 'ASC'
            )
        );

        return $terms;
    }

    protected function generate_hierarchy( $terms, $parent = 0 ) {
        $h = [ ];
        foreach ( $terms as $term ) {
            if ( (int) $term->parent === (int) $parent ) {
                $questions = $this->get_category_questions( $term->term_id );

                $h[] = [
                    'name'      => $term->name,
                    'questions' => $questions,
                    'sections'  => $this->generate_hierarchy( $terms, $term->term_id )
                ];
            }
        }

        return $h;
    }

    protected function format_questionaire_hierarchy( $hierarchy, $level = 0 ) {
        $html = '';
        foreach ( $hierarchy as $sect ) {
            $html .= "<fieldset class='level level_$level'>\n";
            $html .= "<legend>{$sect['name']}</legend>\n";
            foreach ( $sect['questions'] as $q ) {
                $quest = $q->post_title;
                $qid   = $q->ID;
                $html .= "<div id='q_{$qid}_group' class='qgroup'>\n";
                $html .= "<strong>$quest</strong>\n";
//                    $html .= "<input type='hidden' name='q_{$qid}' value='x' />\n";
                $html .= "<table class='scale'>\n";
                $html .= "<tr>\n";
                $html .= "<th title='Help!'><label for='q_{$qid}_1'>1</label></th>\n";
                $html .= "<th title='Not so great'><label for='q_{$qid}_2'>2</label></th>\n";
                $html .= "<th title='Okay'><label for='q_{$qid}_3'>3</label></th>";
                $html .= "<th title='Pretty good'><label for='q_{$qid}_4'>4</label></th>\n";
                $html .= "<th title='Wonderful'><label for='q_{$qid}_5'>5</label></th>\n";
                $html .= "<th class='empty' rowspan='2'>&nbsp;</th>\n";
                $html .= "<th title='Not Applicable'><label for='q_{$qid}_0'>N/A</label></th>\n";
                $html .= "</tr>\n";
                $html .= "<tr>\n";
                $html .= "<td title='Help!'><input type='radio' name='q_{$qid}' value='1' id='q_{$qid}_1'/></td>\n";
                $html .= "<td title='Not so great'><input type='radio' name='q_{$qid}' value='2' id='q_{$qid}_2'/></td>\n";
                $html .= "<td title='Okay'><input type='radio' name='q_{$qid}' value='3' id='q_{$qid}_3'/></td>\n";
                $html .= "<td title='Pretty good'><input type='radio' name='q_{$qid}' value='4' id='q_{$qid}_4'/></td>\n";
                $html .= "<td title='Wonderful'><input type='radio' name='q_{$qid}' value='5' id='q_{$qid}_5'/></td>\n";
                $html .= "<td title='Not Applicable'><input type='radio' name='q_{$qid}' value='0' id='q_{$qid}_0'/></td>\n";
                $html .= "</tr>\n";
                $html .= "</table>\n";
                $html .= "</div>\n";
            }
            if ( $sect['sections'] ) {
                $html .= $this->format_questionaire_hierarchy( $sect['sections'], $level + 1 );
            }
            $html .= "</fieldset>";
        }

        return $html;
    }

    protected function format_summary_hierarchy( $hierarchy, $assessments, $level = 0 ) {
        $html = '<table class="assessment_summary">\n';
        $html .= '<thead>\n';
        foreach ( $hierarchy as $sect ) {
            $html .= "<tr>\n";
            $html .= "<th>Categories and Questions</th>\n";
            foreach ( $assessments as $a ) {
                $html .= sprintf( "<th>%s</th>", $a['date'] );
            }
            $html .= "<th>Average</th>\n";
            $html .= "</tr>\n";
            $html .= "</thead>\n";
            $html .= "<tbody>\n";
            $html .= "<fieldset class='level level_$level'>\n";
            $html .= "<legend>{$sect['name']}</legend>\n";
            foreach ( $sect['questions'] as $q ) {
                $quest = $q->post_title;
                $qid   = $q->ID;
                $html .= "<div id='q_{$qid}_group' class='qgroup'>\n";
                $html .= "<strong>$quest</strong>\n";
//                    $html .= "<input type='hidden' name='q_{$qid}' value='x' />\n";
                $html .= "<table class='scale'>\n";
                $html .= "<tr>\n";
                $html .= "<th title='Help!'><label for='q_{$qid}_1'>1</label></th>\n";
                $html .= "<th title='Not so great'><label for='q_{$qid}_2'>2</label></th>\n";
                $html .= "<th title='Okay'><label for='q_{$qid}_3'>3</label></th>";
                $html .= "<th title='Pretty good'><label for='q_{$qid}_4'>4</label></th>\n";
                $html .= "<th title='Wonderful'><label for='q_{$qid}_5'>5</label></th>\n";
                $html .= "<th class='empty' rowspan='2'>&nbsp;</th>\n";
                $html .= "<th title='Not Applicable'><label for='q_{$qid}_0'>N/A</label></th>\n";
                $html .= "</tr>\n";
                $html .= "<tr>\n";
                $html .= "<td title='Help!'><input type='radio' name='q_{$qid}' value='1' id='q_{$qid}_1'/></td>\n";
                $html .= "<td title='Not so great'><input type='radio' name='q_{$qid}' value='2' id='q_{$qid}_2'/></td>\n";
                $html .= "<td title='Okay'><input type='radio' name='q_{$qid}' value='3' id='q_{$qid}_3'/></td>\n";
                $html .= "<td title='Pretty good'><input type='radio' name='q_{$qid}' value='4' id='q_{$qid}_4'/></td>\n";
                $html .= "<td title='Wonderful'><input type='radio' name='q_{$qid}' value='5' id='q_{$qid}_5'/></td>\n";
                $html .= "<td title='Not Applicable'><input type='radio' name='q_{$qid}' value='0' id='q_{$qid}_0'/></td>\n";
                $html .= "</tr>\n";
                $html .= "</table>\n";
                $html .= "</div>\n";
            }
            if ( $sect['sections'] ) {
                $html .= $this->format_questionaire_hierarchy( $sect['sections'], $level + 1 );
            }
            $html .= "</fieldset>";
        }

        return $html;
    }

    protected function get_category_questions( $cat_id ) {
        $args = [
            'post_type' => 'bloom-assessments',
            'orderby'   => 'title',
            'tax_query' => [
                [
                    'taxonomy'         => 'bloom-categories',
                    'field'            => 'term_id',
                    'terms'            => $cat_id,
                    'include_children' => false
                ]
            ]
        ];

        $posts = get_posts( $args );

        return $posts;
    }

    protected function getAssessmentResponses($question, $assessments) {
        return array_map(function($assessment) use ($question) {
            return array_filter($assessment['assessment_results'], function($result) use ($question) {
                return $result['q'] === $question->ID;
            });
        },$assessments);
    }

    protected function setup() {
        $this->msg_not_subscribed = <<<MESSAGE
Sorry. You're not currently subscribed to this program. Please go to <a href='%s'>Bloom Program Page</a> for more information.
MESSAGE;
        $this->msg_not_logged_in  = <<<MESSAGE
Looks like you're not logged in. Please try logging in above for this program to display.
MESSAGE;
        $this->partial_directory  = __DIR__ . '/../assets/partials/';
        $this->weeks_to_show      = 4;

        $this->routes = [
            [
                'page'     => 'overview',
                'template' => 'overview',
                'default'  => true,
                'vars'     => function () {
                    return [
                        'current_user' => wp_get_current_user(),
                        'goalsets'     => array_slice( get_terms( 'bloom-goalsets', array(
                                'hide_empty' => false,
                                'orderby'    => 'name',
                                'order'      => 'DESC'
                            ) ),
                            0,
                            $this->weeks_to_show
                        ),
                        'categories'   => get_terms( 'bloom-categories', array(
                            'hide_empty' => false,
                            'parent'     => 0
                        ) )
                    ];
                }
            ],
            [
                'page'     => 'preferences',
                'template' => 'preferences',
                'vars'     => function () {
                    return [
                        'current_user'     => wp_get_current_user(),
                        'preference_level' =>
                            get_user_meta( get_current_user_id(), $this->parent->_token . 'preference_level', true )
                    ];
                }
            ],
            [
                'page'     => 'instructions',
                'template' => 'instructions',
                'vars'     => function () {
                    return array();
                }
            ],
            [
                'page'     => 'serendipity-examples',
                'template' => 'serendipity-examples',
                'vars'     => function () {
                    return array();
                }
            ],
            [
                'page'     => 'goals.set',
                'template' => 'goals.set',
                'vars'     => function () {
                    return [
                        'current_user'    => wp_get_current_user(),
                        'current_goalset' => '2014-01-01'
                    ];
                }
            ],
            [
                'page'     => 'assessments.create',
                'template' => 'assessments.create',
                'vars'     => function () {
                    $categories = $this->generate_categories();
                    $hierarchy  = $this->generate_hierarchy( $categories );
                    $formatted  = $this->format_questionaire_hierarchy( $hierarchy );

                    return [
                        'current_user'        => wp_get_current_user(),
                        'generated_questions' => $formatted

                    ];
                }
            ],
            [
                'page'     => 'assessments',
                'template' => 'assessments',
                'vars'     => function () {
                    $categories = $this->generate_categories();
                    $hierarchy  = $this->generate_hierarchy( $categories );
//                    $formatted  = $this->format_questionaire_hierarchy( $hierarchy );

                    $assessments = get_user_meta( get_current_user_id(), $this->parent->_token . '_assessment' );
                    $assessments = array_slice( $assessments, -4);
                    $assessments = array_reverse($assessments);
                    return [
                        'meta'        => get_user_meta( get_current_user_id(), $this->parent->_token . '_assessment' ),
                        'assessments' => $assessments,
                        'hierarchy' => $hierarchy
                    ];
                }
            ]
        ];

    }
}