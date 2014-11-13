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
        add_action( 'wp', array( $this, 'setup_cron' ) );
    }

    function setup_cron() {
        if ( !( wp_next_scheduled( 'bloom_create_goalset_event' ) ) ) {
            wp_schedule_event( time(), 'daily', 'bloom_create_goalset_event' );
        }
        add_action( 'bloom_create_goalset_event', array( $this, 'bloom_create_goalset' ) );
    }

    function deactive_cron() {
        wp_clear_scheduled_hook( 'bloom_create_goalset' );
    }


    function bloom_create_goalset() {
        $dow = 0; // Sunday
        if ( date( 'w' ) === $dow ) {
            $this->addGoalset( date( 'Y-m-d', strtotime( 'next Monday' ) ) );
        }
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
            case 'goal_suggestions':
                $goals   = get_posts( [
                    'posts_per_page' => - 1,
                    'post_type'      => 'bloom_suggested',
                    'orderby'        => 'title',
                    'order'          => 'ASC',
                    'tax_query'      => array(
                        array(
                            'taxonomy'         => 'bloom-categories',
                            'field'            => 'id',
                            'terms'            => $_POST['category_id'],
                            'include_children' => false
                        )
                    )
                ] );
                $cleaned = array_map( function ( $goal ) {
                    return [
                        'id'         => $goal->ID,
                        'suggestion' => $goal->post_title,
                        'per_week'   => (int) get_post_meta( $goal->ID, 'bloom_per_week', true )
                    ];
                }, $goals );
                $result  = [
                    'goals'   => $cleaned,
                    'success' => true
                ];
                break;
            case 'add_goals':
                $opts = $_POST;
                $data = array();
                parse_str( $opts['data'], $data );
                $goalCount = count( $data['goals'] );
                $goals     = [ ];
                for ( $i = 0; $i < $goalCount; $i ++ ) {
                    $goals[] = [
                        'suggestion_id' => $data['suggestions'][ $i ],
                        'category_id'   => $data['cat'][ $i ],
                        'goal'          => $data['goals'][ $i ],
                        'per_week'      => $data['per_week'][ $i ]
                    ];
                }
                array_map( function ( $goal ) use ( $opts, $data ) {
                    $post    = [
                        'post_title'  => $goal['goal'],
                        'post_author' => $opts['user'],
                        'tax_input'   => array(
                            'bloom-categories' => array( $goal['category_id'] ),
                            'bloom-goalsets'   => array( $data['goalset'] )
                        ),
                        'post_status' => 'publish',
                        'post_type'   => 'bloom-user-goals'
                    ];
                    $goal_id = wp_insert_post( $post, true );
                    add_post_meta( $goal_id, 'per_week', $goal['per_week'] );
                    add_post_meta( $goal_id, 'suggested_id', $goal['suggested_id'] );
                }, $goals );
                // Serendipity
                $post        = [
                    'post_title'  => '',
                    'post_author' => $opts['user'],
                    'post_status' => 'publish',
                    'post_type'   => 'bloom-user-goals',
                    'tax_input'   => array(
                        'bloom-goalsets' => array( $data['goalset'] )
                    ),

                ];
                $is_advanced = get_user_meta( get_current_user_id(), $this->parent->_token . 'preference_level', true ) === 'advanced';
                for ( $i = $is_advanced ? 2 : 1; $i > 0; $i -- ) {
                    $goal_id = wp_insert_post( $post, true );
                }
                $result['success'] = true;
                break;
            case 'update_goals':
                $opts      = $_POST;
                $completed = get_post_meta( $opts['goal'], 'completed', true );
                if ( empty( $completed ) ) {
                    $completed = [ ];
                }
                $completed[ $opts['day'] ] = $opts['set'] === 'true';
                update_post_meta( $opts['goal'], 'completed', $completed );
                $result['success'] = true;
                $result['set']     = $opts['set'] === 'true';
                break;
            case 'update_serendipity':
                $args              = [
                    'ID'         => $_POST['id'],
                    'post_title' => $_POST['serendipity']
                ];
                $update            = wp_update_post( $args );
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
        $template = $route['template'] ? $route['template'] : $route['page'];
        $html     = "<div id='bloom'>\n";
        $html .= $this->get_partial( 'nav', [ 'active' => $route['page'] ] );
        $html .= $this->get_partial( $template, $vars );
        $html .= $this->get_partial( 'footer', [ ] );
        $html .= "</div>";

        return $html;
    }

    protected function enqueue_stuff() {
        // Enqueue scripts and css here.
        wp_enqueue_script( 'jquery-dotdotdot' );
        wp_enqueue_script( 'underscore' );
        wp_enqueue_script( $this->parent->_token . '-frontend' );
        wp_localize_script(
            $this->parent->_token . '-frontend',
            'POM_BLOOM',
            array(
                'ajax_url'     => admin_url( 'admin-ajax.php' ),
                'current_user' => get_current_user_id()
            )
        );
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

    protected function getAssessmentResponses( $question, $assessments ) {
        return array_map( function ( $assessment ) use ( $question ) {
            return array_filter( $assessment['assessment_results'], function ( $result ) use ( $question ) {
                return $result['q'] === $question->ID;
            } );
        }, $assessments );
    }

    protected function getSubCategories( $id, $level = 0 ) {
        $theTerms = [ ];
        $terms    = get_terms( 'bloom-categories', array(
            'hide_empty' => false,
            'parent'     => $id
        ) );
        foreach ( $terms as $term ) {
            $theTerms[] = [
                'id'   => $term->term_id,
                'name' => str_repeat( '-', $level * 2 ) . $term->name
            ];

            $subs     = $this->getSubCategories( $term->term_id, $level + 1 );
            $theTerms = array_merge( $theTerms, $subs );
        }

        return $theTerms;
    }

    protected function getCategoryAverages( $user_id, $category_id, $last = 4 ) {
        $assessments    = $this->getAssessments( $user_id );
        $category_terms = get_terms( 'bloom-categories', [
            'hide_empty' => false,
            'parent'     => $category_id
        ] );
//        $categories = array_merge([$category_id],array_map(function($term) {
//            return $term->term_id;
//        }, $category_terms));
        $args = [
            'post_type' => 'bloom-assessments',
            'tax_query' => [
                [
                    'taxonomy'         => 'bloom-categories',
                    'field'            => 'term_id',
                    'terms'            => $category_id,
                    'include_children' => true
                ]
            ]
        ];

        $questions_raw = get_posts( $args );
        $questions     = array_map( function ( $q ) {
            return $q->ID;
        }, $questions_raw );
        $averages      = [ ];
        foreach ( $assessments as $a ) {
            $avg = [ 0, 0 ];
            foreach ( $a['assessment_results'] as $r ) {
                if ( in_array( $r['q'], $questions ) and $r['rating'] > 0 ) {
                    $avg[0] += $r['rating'];
                    $avg[1] ++;
                }
            }
            $averages[ $a['assessment_date'] ] = $avg[1] > 0 ? round( $avg[0] / $avg[1], 1 ) : 0;
        }

        return $averages;
    }

    protected function getAssessments( $user_id, $number = 4 ) {
        $assessments = get_user_meta( $user_id, $this->parent->_token . '_assessment' );
        if ( $number ) {
            $assessments = array_slice( $assessments, - $number );
        }
        $assessments = array_reverse( $assessments );

        return $assessments;
    }

    public function addGoalset( $goalset = null ) {
        if ( empty( $goalset ) ) {
            $goalset = date( "Y-m-d" );
        }
        wp_insert_term( $goalset, 'bloom-goalsets' );
    }

    protected function getLatestGoalset() {
        $latest = get_terms( 'bloom-goalsets', array(
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'DESC',
                'number'     => 1
            )
        );

        return $latest[0];
    }


    protected function canUserAddGoals( $user_id ) {
        $latest = $this->getLatestGoalset();
        $posts  = get_posts( [
            'author'    => $user_id,
            'post_type' => 'bloom-user-goals',
            'tax_query' => array(
                array(
                    'taxonomy' => 'bloom-goalsets',
                    'field'    => 'id',
                    'terms'    => array( $latest->term_id )
                )
            )
        ] );

        return count( $posts ) === 0;
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
                    $goalsets = array_slice( get_terms( 'bloom-goalsets', array(
                            'hide_empty' => false,
                            'orderby'    => 'name',
                            'order'      => 'DESC'
                        ) ),
                        0,
                        $this->weeks_to_show
                    );
                    $terms    = array_map( function ( $set ) {
                        return $set->term_id;
                    }, $goalsets );
                    $goals    = get_posts( [
                        'post_type'      => 'bloom-user-goals',
                        'author'         => get_current_user_id(),
                        'posts_per_page' => - 1,
                        'tax_query'      => array(
                            array(
                                'taxonomy' => 'bloom-goalsets',
                                'field'    => 'id',
                                'terms'    => $terms
                            )
                        )

                    ] );

                    $grouped = [ ];
                    array_map( function ( $goal ) use ( &$grouped ) {
                        $goal->category     = wp_get_post_terms( $goal->ID, 'bloom-categories', [ 'fields' => 'all' ] )[0];
                        $goal->goalset      = wp_get_post_terms( $goal->ID, 'bloom-goalsets', [ 'fields' => 'all' ] )[0];
                        $goal->completed    = get_post_meta( $goal->ID, 'completed', true );
                        $goal->per_week     = get_post_meta( $goal->ID, 'per_week', true );
                        $goal->is_completed = is_null( $goal->category ) ? // Serendipity if null
                            strlen( $goal->post_title ) > 0
                            : array_sum( $goal->completed ) >= $goal->per_week;

                        $grouped[ $goal->goalset->name ][ $goal->category->name ? $goal->category->name : 'serendipity' ][] = $goal;
                    }, $goals );

                    return [
                        'current_user' => wp_get_current_user(),
                        'goalsets'     => $goalsets,
                        'goals'        => $grouped,
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
                    wp_enqueue_script( 'google-jsapi', 'https://www.google.com/jsapi' );
                    $goalCategories = [ ];
                    $categories     = get_terms( 'bloom-categories', array(
                            'hide_empty' => false,
                            'parent'     => 0,
                            'orderby'    => 'slug'
                        )
                    );
                    $level          = get_user_meta( get_current_user_id(), $this->parent->_token . 'preference_level', true );
                    foreach ( $categories as $cat ) {
                        $goalCategories[] = [
                            'id'       => $cat->term_id,
                            'name'     => $cat->name,
                            'goal_num' => 1
                        ];
                        if ( $level === 'advanced' && $cat !== end( $categories ) ) {
                            $copy = end( $goalCategories );
                            $copy['goal_num'] ++;
                            $goalCategories[] = $copy;
                        }
                    }

                    return [
                        'current_user'    => wp_get_current_user(),
                        'current_goalset' => '2014-01-01',
                        'categories'      => $goalCategories
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

                    $assessments = $this->getAssessments( get_current_user_id() );

                    return [
                        'meta'        => get_user_meta( get_current_user_id(), $this->parent->_token . '_assessment' ),
                        'assessments' => $assessments,
                        'hierarchy'   => $hierarchy
                    ];
                }
            ],
            [
                'page'     => 'goals.update',
                'template' => 'goals.update',
                'vars'     => function () {

                    $goalset  = $_GET['goalset'];
                    $goals    = get_posts( [
                        'posts_per_page' => - 1,
                        'post_type'      => 'bloom-user-goals',
                        'tax_query'      => array(
                            array(
                                'taxonomy' => 'bloom-goalsets',
                                'field'    => 'slug',
                                'terms'    => $goalset
                            )
                        ),
                        'author'         => get_current_user_id()
                    ] );
                    $modified = array_map( function ( $goal ) {
                        $goal->category  = wp_get_post_terms( $goal->ID, 'bloom-categories', [ 'fields' => 'all' ] )[0];
                        $goal->goalset   = wp_get_post_terms( $goal->ID, 'bloom-goalsets', [ 'fields' => 'all' ] )[0];
                        $goal->completed = get_post_meta( $goal->ID, 'completed', true );
                        $goal->per_week  = get_post_meta( $goal->ID, 'per_week', true );

                        return $goal;
                    }, $goals );

                    $dow = [ 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' ];

                    return [
                        'goals' => $modified,
                        'dow'   => $dow
                    ];
                }
            ],
            [
                'page' => 'conversion'
            ]
        ];

    }

    protected function getOldBloomDB() {
        if ( empty( $this->oldBloom ) ) {
            $this->oldBloom = new wpdb( 'root', 'root', 'powerofmoms_blm', 'localhost' );
        }

        return $this->oldBloom;
    }

    protected function removeAllPostTypes( $post_type ) {
        $posts = get_posts( [
            'post_type'      => $post_type,
            'posts_per_page' => - 1
        ] );
        array_map( function ( $post ) {
            $deleted = wp_delete_post( $post->ID, true );
        }, $posts );
    }

    protected function removeCategories( $termType ) {
        $terms = get_terms( $termType, [
            'hide_empty' => false
        ] );
        array_map( function ( $term ) use ( $termType ) {
            wp_delete_term( $term->term_id, $termType );
        }, $terms );
    }

    protected function getOldBloomCategories() {
        $oldbloom = $this->getOldBloomDB();
        $sql      = <<<SQL
SELECT *
FROM `pom_gls_cats`;
SQL;
        $results  = $oldbloom->get_results( $sql, OBJECT );

        return $results;
    }

    protected function addCategories( $categories, $parent = null ) {
        array_map( function ( $category ) use ( $parent, $categories ) {
            $args = [ 'slug' => $category->order . '-' . sanitize_title( $category->category ) ];
            if ( !is_null( $parent ) ) {
                $parent_obj     = end( array_filter( $categories, function ( $cat ) use ( $parent ) {
                    return $cat->id === $parent;
                } ) );
                $parent         = get_term_by( 'name', $parent_obj->category, 'bloom-categories' );
                $args['parent'] = $parent->term_id;
            }
            $term = wp_insert_term( $category->category, 'bloom-categories', $args );
            $this->addCategories( $categories, $category->id );
        }, array_filter( $categories, function ( $cat ) use ( $parent ) {
            return $cat->parent === $parent;
        } ) );
    }

    protected function addSuggestions() {
        $oldbloom = $this->getOldBloomDB();
        $sql      = <<<SQL
SELECT *
FROM `pom_gls_recomendations`
LEFT JOIN `pom_gls_cats` ON `pom_gls_cats`.id = `pom_gls_recomendations`.cat_id;
SQL;
        $results  = $oldbloom->get_results( $sql, OBJECT );
        $posts    = [ ];
        foreach ( $results as $recommendation ) {
            $theCat = end( array_filter( $this->oldCats(), function ( $catGroup ) use ( $recommendation ) {
                return $catGroup['old']->id === $recommendation->id;
            } ) );
            $post   = [
                'post_title'  => $recommendation->recommendation,
                'post_type'   => 'bloom_suggested',
                'post_status' => 'publish',
                'tax_input'   => array( 'bloom-categories' => array( $theCat['new']->term_id ) )
            ];
            if ( is_null( $theCat['new']->term_id ) ) {
                var_dump( $recommendation );
                die;
            }
            $posts[] = [
                'post' => $post,
                'meta' => [ 'key' => 'bloom_per_week', 'value' => $recommendation->per_week ]
            ];
        }
        array_map( function ( $post ) {
            $post_id = wp_insert_post( $post['post'] );
            add_post_meta( $post_id, $post['meta']['key'], $post['meta']['value'] );
        }, $posts );

    }

    protected function addAssessmentQuestions() {
        $categories = $this->oldCats();
        $oldbloom   = $this->getOldBloomDB();
        $sql        = <<<SQL
SELECT *
FROM `pom_gls_a_quests`
LEFT JOIN `pom_gls_cats` ON `pom_gls_cats`.id = `pom_gls_a_quests`.category_id;
SQL;
        $results    = $oldbloom->get_results( $sql, OBJECT );
        array_map( function ( $q ) use ( $categories ) {
            $theCat    = end( array_filter( $categories, function ( $catGroup ) use ( $q ) {
                return $catGroup['old']->id === $q->category_id;
            } ) );
            $theCat_id = $theCat['new']->term_id;
            $post      = [
                'post_title'  => $q->question,
                'post_type'   => 'bloom-assessments',
                'post_status' => 'publish',
                'slug'        => $q->order . '-' . sanitize_title( $q->question ),
                'tax_input'   => array( 'bloom-categories' => array( $theCat_id ) )
            ];
            $post_id   = wp_insert_post( $post, true );
        }, $results );
    }

    protected function addGoalsets() {
        $oldbloom = $this->getOldBloomDB();
        $sql      = <<<SQL
SELECT *
FROM `pom_gls_goalsets`;
SQL;
        $results  = $oldbloom->get_results( $sql, OBJECT );
        array_map( function ( $set ) {
            wp_insert_term( $set->date, 'bloom-goalsets' );
        }, $results );
    }

    protected function addAssessments() {
        $oldbloom  = $this->getOldBloomDB();
        $sql       = <<<SQL
SELECT * FROM `pom_gls_a_user`
LEFT JOIN pom_gls_a_quests ON pom_gls_a_user.quest_id = pom_gls_a_quests.id
ORDER BY `user_id`;
SQL;
        $results   = $oldbloom->get_results( $sql, OBJECT );
        $organized = [ ];
        array_map( function ( $q ) use ( &$organized ) {
            $organized[ $q->user_id ][ $q->timestamp ][ $q->question ] = $q->response;
        }, $results );

        foreach ( $organized as $user_id => $assessments ) {
            foreach ( $assessments as $assessment => $questions ) {
                $a       = [ ];
                $average = [ 'sum' => 0, 'count' => 0 ];
                foreach ( $questions as $question => $response ) {
                    $theQuestion = get_page_by_title( $question, OBJECT, 'bloom-assessments' );
                    $a[]         = [
                        'q'      => $theQuestion->ID,
                        'rating' => $response
                    ];
                    if ( (int) $response > 0 ) {
                        $average['sum'] += (int) $response;
                        $average['count'] ++;
                    }
                }
                $new_assessment = [
                    'assessment_date'    => $assessment,
                    'average'            => $average['count'] > 0 ? round( $average['sum'] / $average['count'], 1 ) : 0,
                    'assessment_results' => $a
                ];
                add_user_meta( $user_id, $this->parent->_token . '_assessment', $new_assessment );
            }

        }
    }

    protected function addGoals() {
        $oldbloom      = $this->getOldBloomDB();
        $sql           = <<<SQL
SELECT *
FROM `pom_gls_goals`
LEFT JOIN pom_gls_cats on pom_gls_cats.id = pom_gls_goals.cat_id;
SQL;
        $results       = $oldbloom->get_results( $sql, OBJECT );
        array_map( function ( $goal )  {
            $taxonomies['bloom-goalsets'] = array( $goal->goalset );
            if ( (int) $goal->rec_id !== - 1 ) {
                $args = [
                    'name__like' => $goal->category,
                    'hide_empty' => false
                ];
                $theCat = get_terms('bloom-categories', $args);
                $taxonomies['bloom-categories'] = array( $theCat[0]->term_id );
            }


            $args = [
                'post_title'  => $goal->goal,
                'post_author' => $goal->user_id,
                'post_status' => 'publish',
                'post_type'   => 'bloom-user-goals',
                'tax_input'   => $taxonomies
            ];
            $item = wp_insert_post( $args );
            add_post_meta( $item, 'bloom-per-week', $goal->per_week );
            $completed = array(
                'mon' => $goal->set_mon === "1",
                'tue' => $goal->set_tue === "1",
                'wed' => $goal->set_wed === "1",
                'thu' => $goal->set_thu === "1",
                'fri' => $goal->set_fri === "1",
                'sat' => $goal->set_sat === "1",
                'sun' => $goal->set_sun === "1",
            );
            add_post_meta( $item, 'completed', $completed );
            if ( (int) $goal->rec_id !== - 1 ) {
                add_post_meta( $item, 'suggested_id', $goal->rec_id );
            }
        }, $results );

    }


    /**
     * Returns a comparison of old categories to new terms
     * @return array
     */
    protected function oldCats() {
        $oldbloom = $this->getOldBloomDB();
        $sql      = <<<SQL
SELECT *
FROM `pom_gls_cats`;
SQL;
        $results  = $oldbloom->get_results( $sql, OBJECT );

        $terms   = get_terms( 'bloom-categories',
            array(
                'hide_empty' => false,
                'orderby'    => 'slug',
                'order'      => 'ASC'
            )
        );
        $matches = [ ];
        foreach ( $terms as $term ) {
            $matches[ $term->name ] = array(
                'new' => $term,
                'old' => end( array_filter( $results, function ( $cat ) use ( $term ) {
                    return strtolower( $cat->category ) === strtolower( $term->name );
                } ) )
            );
            if ( $matches[ $term->name ]['old'] === array() ) {
                die( $term->name );
            }
        }

        return $matches;
    }
}