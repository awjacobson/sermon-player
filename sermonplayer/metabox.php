<?php

class SermonPlayerMetabox {
    public function register() {
        add_meta_box( 
            'sermon-meta-box',
            __( 'Sermon Meta Box' ),
            array($this, 'get_content'),
            'sermon',
            'normal',
            'default'
        );
    }

    public function get_content($post) {
    //    $value = get_post_meta($post->ID, '_wporg_meta_key', true);
        $value = "Aaron";
        ?>
        <label for="wporg_field">Speaker</label>
        <select name="wporg_field" id="wporg_field" class="postbox">
            <option value="">Select speaker...</option>
            <option value="something" <?php selected($value, 'something'); ?>>Brian Credille</option>
            <option value="else" <?php selected($value, 'else'); ?>>Else</option>
        </select>
        <?php
    }
}

?>