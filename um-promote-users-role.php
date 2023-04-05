<?php
/**
 * Plugin Name:     Ultimate Member - Promote Users Role
 * Description:     Extension to Ultimate Member for User Role Promotions in the frontend Profile Page with Roles dropdown made by Users with WP 'promote_users' capability like a site Administrator.
 * Version:         1.1.0
 * Requires PHP:    7.4
 * Author:          Miss Veronica
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:      https://github.com/MissVeronica
 * Text Domain:     ultimate-member
 * Domain Path:     /languages
 * UM version:      2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

class UM_Promote_Users_Role {

    public $form_ids = array();

    function __construct( ) {

        if ( UM()->options()->get( 'promote_users_role_enable' ) ) {

            $forms = UM()->options()->get( 'promote_users_role_form_ids' );
            if ( ! empty( $forms )) {

                $this->form_ids = array_map( 'trim', array_map( 'sanitize_text_field', explode( ",", $forms )));

                add_filter( 'um_user_edit_profile_fields', array( $this, 'um_user_edit_profile_fields_promote_users_role' ), 999, 2 );
                add_filter( 'um_submit_form_data',         array( $this, 'um_submit_form_data_promote_users_role' ), 999, 2 );
            }
        }

        add_action( 'um_settings_structure', array( $this, 'um_settings_structure_promote_users_role' ), 10, 1 );
    }

    public function um_user_edit_profile_fields_promote_users_role( $fields, $args ) {

        if ( current_user_can( 'promote_users' ) && $args['mode'] == 'profile' && in_array( $args['form_id'], $this->form_ids ) ) {

            if ( isset( $_POST['role'] ) && ! empty( $_POST['role'] ) ) {

                if ( isset( $fields['role_select']['editable'] )) {

                    // Restore role field for admin editing
                    $fields['role_select']['editable'] = true;

                    $custom_field_roles = UM()->form()->custom_field_roles( $fields );

                    if ( is_array( $_POST['role'] ) ) {

                        $role = current( $_POST['role'] );
                        $role = sanitize_key( $role );

                    } else {

                        $role = sanitize_key( $_POST['role'] );
                    }

                    global $wp_roles;

                    $exclude_roles = array_diff( array_keys( $wp_roles->roles ), UM()->roles()->get_editable_user_roles() );

                    if ( ! empty( $role ) &&
                        ( ! in_array( $role, $custom_field_roles, true ) || in_array( $role, $exclude_roles, true ) ) ) {

                        wp_die( esc_html__( 'This is not possible for security reasons.', 'ultimate-member' ) );
                    }

                    $fields['submitted']['role'] = $role;
                }
            }
        }

        return $fields;
    }

    public function um_submit_form_data_promote_users_role( $post_form, $post_form_mode ) {

        if ( current_user_can( 'promote_users' ) && $post_form_mode == 'profile' && in_array( $post_form['form_id'], $this->form_ids )) {

            if ( isset( $_POST['role'] ) && ! empty( $_POST['role'] ) ) {

                $post_form['submitted']['role'] = sanitize_key( $_POST['role'] );
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

