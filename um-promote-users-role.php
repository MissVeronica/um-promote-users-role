<?php
/**
 * Plugin Name:     Ultimate Member - Promote Users Role
 * Description:     Extension to Ultimate Member for User Role Promotions in the frontend Profile Page with Roles dropdown made by Users with WP 'promote_users' capability like a site Administrator.
 * Version:         2.0.0
 * Requires PHP:    7.4
 * Author:          Miss Veronica
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:      https://github.com/MissVeronica
 * Text Domain:     ultimate-member
 * Domain Path:     /languages
 * UM version:      2.6.7
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

class UM_Promote_Users_Role {

    private $form_ids = array();

    function __construct( ) {

        if ( UM()->options()->get( 'promote_users_role_enable' ) ) {

            $forms = UM()->options()->get( 'promote_users_role_form_ids' );
            if ( ! empty( $forms )) {

                $this->form_ids = array_map( 'trim', array_map( 'sanitize_text_field', explode( ",", $forms )));

                add_filter( 'um_submit_form_data', array( $this, 'um_submit_form_data_promote_users_role' ), 999, 3 );
            }
        }

        add_action( 'um_settings_structure', array( $this, 'um_settings_structure_promote_users_role' ), 10, 1 );
    }

    public function um_submit_form_data_promote_users_role( $post_form, $post_form_mode, $all_cf_metakeys ) {

        if ( current_user_can( 'promote_users' ) && $post_form_mode == 'profile' && in_array( $post_form['form_id'], $this->form_ids )) {

            if ( isset( $_POST['role'] ) && ! empty( $_POST['role'] ) ) {

                if ( is_array( $_POST['role'] ) ) {

                    $role = current( $_POST['role'] );
                    $role = trim( sanitize_key( $role ));

                } else {

                    $role = trim( sanitize_key( $_POST['role'] ));
                }

                if ( empty( $role ) || ! in_array( $role, UM()->roles()->get_editable_user_roles(), true ) ) {
                    wp_die( esc_html__( 'This is not possible for security reasons.', 'ultimate-member' ) );
                }

                if ( isset( $_POST['user_id'] ) && ! empty( $_POST['user_id'] ) && is_numeric( $_POST['user_id'] )) {

                    $user_id = intval( $_POST['user_id'] );
                    $priority_role = UM()->roles()->get_priority_user_role( $user_id );

                    if ( $priority_role !== $role ) {

                        if ( empty( $priority_role ) || ! in_array( $priority_role, UM()->roles()->get_editable_user_roles(), true ) ) {
                            wp_die( esc_html__( 'This is not possible for security reasons.', 'ultimate-member' ) );
                        }

                        $new_role = UM()->roles()->set_role( $user_id, $role );

                        if ( ! in_array( $new_role, UM()->roles()->get_editable_user_roles(), true ) ) {

                            UM()->roles()->remove_role( $user_id, $new_role );
                            wp_die( esc_html__( 'Setting new role failed.', 'ultimate-member' ) );
                        }

                        UM()->roles()->remove_role( $user_id, $priority_role );
                    }
                }
            }
        }

        return $post_form;
    }

    public function um_settings_structure_promote_users_role( $settings_structure ) {

        $settings_structure['misc']['fields'][] = array( 'id'           => 'promote_users_role_enable',
                                                         'type'         => 'checkbox',
                                                         'label'        => __( "Promote Users Role - Tick to enable", 'ultimate-member' ),
                                                         'tooltip'      => __( "Enable or disable the Admin frontend Role promotions.", 'ultimate-member' ),
                                                        );

        $settings_structure['misc']['fields'][] = array( 'id'           => 'promote_users_role_form_ids',
                                                         'type'         => 'text',
                                                         'label'        => __( "Promote Users Role - Profile Form IDs", 'ultimate-member' ),
                                                         'tooltip'      => __( "Comma separated Profile Form IDs where frontend Role promotions are allowed.", 'ultimate-member' ),
                                                         'size'         => 'medium',
                                                         'conditional'  => array( 'promote_users_role_enable', '=', 1 ),
                                                         );

        return $settings_structure;
    }

}

new UM_Promote_Users_Role();
