<?php
/**
 * Security Tools - Hide Admins Feature
 *
 * Hides selected administrator accounts from the Users list.
 *
 * @package    Security_Tools
 * @subpackage Features
 * @author     Carlos Rodríguez
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Class Security_Tools_Feature_Hide_Admins
 *
 * Implements administrator hiding functionality.
 *
 * @since 1.2
 */
class Security_Tools_Feature_Hide_Admins {

    /**
     * Constructor - Register hooks
     *
     * @since 1.2
     */
    public function __construct() {
        add_filter( 'views_users', array( $this, 'modify_user_views' ) );
        add_filter( 'pre_get_users', array( $this, 'filter_users_query' ) );
        add_filter( 'users_list_table_query_args', array( $this, 'modify_query_args' ) );
    }

    /**
     * Get hidden admin IDs
     *
     * @since 1.2
     * @return array
     */
    private function get_hidden_admins() {
        return Security_Tools_Utils::get_array_option( Security_Tools_Utils::OPTION_HIDDEN_ADMINS );
    }

    /**
     * Check whether current screen is Users (site or network admin)
     *
     * @since 2.6
     * @param object|null $screen Current screen object.
     * @return bool
     */
    private function is_users_screen( $screen ) {
        if ( ! $screen ) {
            return false;
        }

        if ( isset( $screen->id ) && in_array( $screen->id, array( 'users', 'users-network' ), true ) ) {
            return true;
        }

        return isset( $screen->base ) && 'users' === $screen->base;
    }

    /**
     * Modify the user count display in views
     *
     * Adjusts the administrator count to exclude hidden admins.
     *
     * @since 1.2
     * @param array $views User list views
     * @return array Modified views
     */
    public function modify_user_views( $views ) {
        $hidden = $this->get_hidden_admins();

        if ( empty( $hidden ) ) {
            return $views;
        }

        $hidden_count = count( $hidden );

        foreach ( $views as $key => $view ) {
            $views[ $key ] = preg_replace_callback(
                '/\((\d+)\)/',
                function( $matches ) use ( $hidden_count, $key ) {
                    $total = intval( $matches[1] );

                    // Adjust count for administrator and all views
                    if ( $key === 'administrator' || $key === 'all' ) {
                        return '(' . max( 0, $total - $hidden_count ) . ')';
                    }

                    return $matches[0];
                },
                $view
            );
        }

        return $views;
    }

    /**
     * Filter hidden admins from user queries
     *
     * @since 1.2
     * @param WP_User_Query $query User query object
     * @return WP_User_Query Modified query
     */
    public function filter_users_query( $query ) {
        if ( ! is_admin() || ! ( $query instanceof WP_User_Query ) ) {
            return $query;
        }

        $screen = get_current_screen();
        if ( ! $this->is_users_screen( $screen ) ) {
            return $query;
        }

        $hidden = $this->get_hidden_admins();

        if ( ! empty( $hidden ) ) {
            $existing_exclude = $query->get( 'exclude' );
            $existing_exclude = is_array( $existing_exclude ) ? $existing_exclude : array();

            $query->set( 'exclude', array_values( array_unique( array_merge( $existing_exclude, $hidden ) ) ) );
        }

        return $query;
    }

    /**
     * Modify users list table query args
     *
     * @since 1.2
     * @param array $args Query arguments
     * @return array Modified arguments
     */
    public function modify_query_args( $args ) {
        $screen = get_current_screen();

        if ( ! $this->is_users_screen( $screen ) ) {
            return $args;
        }

        $hidden = $this->get_hidden_admins();

        if ( ! empty( $hidden ) ) {
            if ( ! empty( $args['exclude'] ) ) {
                $args['exclude'] = array_merge( $args['exclude'], $hidden );
            } else {
                $args['exclude'] = $hidden;
            }
        }

        return $args;
    }
}
