<?php

class SermonPlayerMetabox {

    public function register() {
        add_meta_box( 
            'sermon-meta-box',
            __( 'Sermon Details' ),
            array($this, 'get_content'),
            'sermon',
            'normal',
            'default'
        );
    }

    public function get_content($post) {
        $meta = get_post_meta($post->ID);
        ?>
        <pre><?php var_dump($meta); ?></pre>
        <?php
        // Add a nonce field for security
        wp_nonce_field($this->get_nonce_action($post->ID), $this->get_nonce_id(), true, true);
        ?>
        <table>
            <tbody>
                <tr>
                    <td><label for="sermon_reference">Reference</label></td>
                    <td><input type="text" class="form-control" id="sermon_reference" name="sermon_reference"></td>
                </tr>
                <tr>
                    <td><label for="txtDate">Date</label></td>
                    <td><input type="date" class="form-control" id="txtDate" name="txtDate"></td>
                </tr>
                <tr>
                    <td><label for="">Service</label></td>
                    <td>
                        <select name="wporg_field" id="wporg_field">
                            <option value="">Select service...</option>
                            <option value="Sunday Morning Worship" <?php selected($value, 'Sunday Morning Worship'); ?>>Sunday Morning Worship</option>
                            <option value="Sunday Evening Worship" <?php selected($value, 'Sunday Evening Worship'); ?>>Sunday Evening Worship</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label for="sermon_speaker">Speaker</label></td>
                    <td>
                        <select name="sermon_speaker" id="sermon_speaker">
                            <option value="">Select speaker...</option>
                            <option value="Brian Credille" <?php selected($value, 'Brian Credille'); ?>>Brian Credille</option>
                            <option value="Andy Vecellio" <?php selected($value, 'Andy Vecellio'); ?>>Andy Vecellio</option>
                            <option value="Mark Ritchey" <?php selected($value, 'Mark Ritchey'); ?>>Mark Ritchey</option>
                            <option value="Bill Campbell" <?php selected($value, 'Bill Campbell'); ?>>Bill Campbell</option>
                            <option value="Rev. Don Maples" <?php selected($value, 'Rev. Don Maples'); ?>>Rev. Don Maples</option>
                            <option value="Rev. John Glover" <?php selected($value, 'Rev. John Glover'); ?>>Rev. John Glover</option>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    /**
     * Gets the nonce action name.
     *
     * @access   private
     * @param    int     $post_id   The ID of the post being saved.
     * @return   string             A string representing the nonce action.
     */
    private function get_nonce_action($post_id) {
        return 'saving-sermon_'.$post_id;
    }

    /**
     * Gets the nonce ID.
     *
     * @access   private
     * @return   string   A string representing the nonce ID.
     */
    private function get_nonce_id() {
        return 'saving-sermon-nonce';
    }

    /**
     * Verifies that the post type that's being saved is actually a post
     * (versus a page or another post type) and that it is the correct post
     * type.
     *
     * @access   private
     * @return   bool     Return true if the current post type is a post;
     *                    false, otherwise.
     */
    private function is_valid_post_type($post_id) {
        $post_type = get_post_type($post_id);

        // If this isn't the correct post, don't update it.
        if ( 'sermon' != $post_type ) return;
    }

    /**
     * Determines whether or not the current user has the ability to save meta
     * data associated with this post.
     *
     * @access   private
     * @param    int     $post_id      The ID of the post being saved.
     * @param    string  $nonce_action The name of the action associated with the nonce.
     * @param    string  $nonce_id     The ID of the nonce field.
     * @return   bool                  Return true if the user has the ability to save this post; false, otherwise.$_COOKIE
     */
    private function user_can_save($post_id, $nonce_action, $nonce_id) {
        $is_autosave = wp_is_post_autosave($post_id);
        $is_revision = wp_is_post_revision($post_id);
        $is_valid_nonce = check_admin_referer($nonce_action, $nonce_id);

        // Returnn true if the user is able to save; otherwise, false.
        return ! ($is_autosave || $is_revision) && $is_valid_nonce;
    }

    /**
     * Save post metadata when a post is saved.
     *
     * @param    int    $post_id    The ID of the post that's currently being edited.
     */
    public function save_post($post_id) {
        $nonce_action = $this->get_nonce_action($post_id);
        $nonce_id = $this->get_nonce_id();

        // If we're not working with a 'post' post type, it is the wrong post type,
        // or the user doesn't have permission to save, then we exit the function.
        if ( ! $this->is_valid_post_type($post_id) || ! $this->user_can_save($post_id, $nonce_action, $nonce_id) ) {
            return;
        }

        if ( isset( $_POST['sermon_speaker'] ) ) {
            update_post_meta( $post_id, 'sermon_speaker', sanitize_text_field( $_POST['sermon_speaker'] ) );
        }

        if ( isset( $_POST['sermon_reference'] ) ) {
            update_post_meta( $post_id, 'sermon_reference', sanitize_text_field( $_POST['sermon_reference'] ) );
        }
    }
}

?>