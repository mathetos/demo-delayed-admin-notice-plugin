<?php

/**
 *  Admin Notice to Encourage a Review or Donation
 *  
 *  @author Matt Cromwell
 *  @version 1.0
 *   
 */

add_action( 'admin_notices', 'your_prefix_review_notice' );

function your_prefix_review_notice() {
	
	// Define your Plugin name, review url, and donation url
	$plugin_name = 'Test Plugin';
    $review_url = 'https://wordpress.org/support/view/plugin-reviews/hello-dolly';
    $donate_url = 'https://www.example.com/donate-here';
	
    //Get current user
    global $current_user, $pagenow ;
    $user_id = $current_user->ID;

	// Get today's timestamp
    $today = mktime( 0, 0, 0, date("m")  , date("d"), date("Y") );
    $actdate = get_option( 'your_prefix_activation_date', false );

    $installed = ( !empty($actdate) ? $actdate : '999999999999999' );

    //if ( $installed <= $today ) {

        //Make sure we're on the plugins page.
        if ( $pagenow == 'plugins.php' ) {

            // If the user hasn't already dismissed our alert,
            // Output the activation banner
            $nag_admin_dismiss_url = 'plugins.php?your_prefix_review_dismiss=0';
            $user_meta             = get_user_meta( $user_id, 'your_prefix_review_dismiss' );

            if ( empty($user_meta) ) {

                ?>
                <div class="notice notice-success">

                    <style>
                        p.review {
                            position: relative;
                            margin-left: 35px;
                        }
                        p.review span.dashicons-heart {
                            color: white;
                            background: #66BB6A;
                            position: absolute;
                            left: -50px;
                            padding: 9px;
                            top: -8px;
                        }

                        p.review strong {
                            color: #66BB6A;
                        }
                        p.review a.dismiss {
                            float: right;
                            text-decoration: none;
                            color: #66BB6A;
                        }
                    </style>
                    <?php
                    // For testing purposes
                    //echo '<p>Today = ' . $today . '</p>';
                    //echo '<p>Installed = ' . $installed . '</p>';
                    ?>

                    <p class="review"><span class="dashicons dashicons-heart"></span><?php echo wp_kses( sprintf( __( 'Are you enjoying <strong>' . $plugin_name . '</strong>? Would you consider either a <a href="%1$s" target="_blank">small donation</a> or a <a href="%2$s" target="_blank">kind review to help continue development of this plugin?', 'your-textdomain' ), esc_url( $donate_url ), esc_url( $review_url ) ), array( 'strong' => array(), 'a' => array( 'href' => array(), 'target' => array() ) ) ); ?><a href="<?php echo admin_url( $nag_admin_dismiss_url ); ?>" class="dismiss"><span class="dashicons dashicons-dismiss"></span></a>

                </div>

            <?php }
        }
    //}
}

// Function to force the Review Admin Notice to stay dismissed correctly
add_action('admin_init', 'your_prefix_ignore_review_notice');

function your_prefix_ignore_review_notice() {
    if ( isset( $_GET[ 'your_prefix_review_dismiss' ] ) && '0' == $_GET[ 'your_prefix_review_dismiss' ] ) {

        //Get the global user
        global $current_user;
        $user_id = $current_user->ID;

        add_user_meta( $user_id, 'your_prefix_review_dismiss', 'true', true );
    }
}