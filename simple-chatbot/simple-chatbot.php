<?php
/*
Plugin Name: Chatbot
Description: Simple Chatbot with decision-tree
Version: 1.0
Author: Iohann Tachy
*/

register_activation_hook(__FILE__, 'simple_chatbot_activate');
function simple_chatbot_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_nodes';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        parent_id mediumint(9) DEFAULT 0,
        node_type varchar(20) NOT NULL,
        node_text text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

add_action('admin_menu', 'simple_chatbot_menu');
function simple_chatbot_menu() {
    add_menu_page(
        'Chatbot Configuration',
        'Chatbot',
        'manage_options',
        'simple-chatbot',
        'chatbot_admin_page',
        'dashicons-format-chat'
    );
}

function chatbot_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_nodes';

    if (isset($_POST['submit_node'])) {
        $parent_id = intval($_POST['parent_id']);
        $node_text = wp_kses_post($_POST['node_text']);
        
        $wpdb->insert($table_name, [
            'parent_id' => $parent_id,
            'node_type' => $_POST['node_type'],
            'node_text' => $node_text
        ]);
        
        echo '<div class="notice notice-success"><p>Node added sucessfully!</p></div>';
    }
    
    if (isset($_POST['update_node'])) {
        $node_id = intval($_POST['node_id']);
        $node_text = wp_kses_post($_POST['node_text']);
        $wpdb->update($table_name, ['node_text' => $node_text], ['id' => $node_id]);
        
        echo '<div class="notice notice-success"><p>Node updated sucessfully!</p></div>';
    }
    
    if (isset($_POST['delete_node'])) {
        $node_id = intval($_POST['node_id']);
        $wpdb->delete($table_name, ['id' => $node_id]);
        $wpdb->delete($table_name, ['parent_id' => $node_id]);
        
        echo '<div class="notice notice-success"><p>Node deleted sucessfully!</p></div>';
    }
    
    if (isset($_POST['move_node_submit'])) {
        $node_id = intval($_POST['node_id']);
        $new_parent_id = intval($_POST['new_parent_id']);
        $wpdb->update($table_name, ['parent_id' => $new_parent_id], ['id' => $node_id]);
        
        echo '<div class="notice notice-success"><p>Node moved sucessfully!</p></div>';
    }
    
    if (isset($_POST['duplicate_node_submit'])) {
        $source_node_id = intval($_POST['node_id']);
        $target_parent_id = intval($_POST['target_parent_id']);
        duplicate_node_recursive($source_node_id, $target_parent_id);
        
        echo '<div class="notice notice-success"><p>Node duplicated sucessfully!</p></div>';
    }

    $nodes = $wpdb->get_results("SELECT * FROM $table_name ORDER BY parent_id ASC, id ASC");
    
    $node_tree = [];
    foreach ($nodes as $node) {
        $node_tree[$node->parent_id][] = $node;
    }
    
    $node_paths = build_node_paths($nodes);
    
    echo '<div class="wrap"><h1>Chatbot Configuration</h1>';
    echo '<div style="display:flex;gap:2rem">';
    
    echo '<div style="flex:1">';
    echo '<h2>Add Question/Answer</h2>';
    echo '<form method="post">';
    echo '<p><label>Node:<br>';
    echo '<select name="parent_id" style="width:100%">';
    echo '<option value="0">-- Root Node --</option>';
    
    foreach ($node_paths as $node_id => $path) {
        $node = find_node_by_id($nodes, $node_id);
        if ($node && $node->node_type === 'question') {
            echo '<option value="' . $node_id . '">' . esc_html($path) . '</option>';
        }
    }
    echo '</select></label></p>';
    echo '<p><label>Type:<br>';
    echo '<select name="node_type" id="node_type" style="width:100%">';
    echo '<option value="question">Question</option>';
    echo '<option value="answer">Answer</option>';
    echo '</select></label></p>';
    echo '<p><label>Text:<br>';
    echo '<textarea name="node_text" style="width:100%;height:100px" required></textarea></label></p>';
    echo '<p><input type="submit" name="submit_node" class="button button-primary" value="Save"></p>';
    echo '</form>';
    echo '</div>';
    
    echo '<div style="flex:2">';
    echo '<h2>Tree</h2>';
    render_node_tree($node_tree, $node_paths, $nodes);
    echo '</div></div>';
    
    echo '<hr><h2>Display Settings</h2>';
    echo '<form method="post" action="options.php">';
    settings_fields('chatbot_appearance');
    do_settings_sections('chatbot_appearance');
    echo '<table class="form-table">';
    echo '<tr><th>Button Color</th><td><input type="color" name="chatbot_btn_color" value="' . esc_attr(get_option('chatbot_btn_color', '#21759b')) . '"></td></tr>';
    echo '<tr><th>Chat Background</th><td><input type="color" name="chatbot_bg_color" value="' . esc_attr(get_option('chatbot_bg_color', '#f5f5f5')) . '"></td></tr>';
    echo '<tr><th>Font Size</th><td><input type="number" name="chatbot_font_size" min="10" max="24" value="' . esc_attr(get_option('chatbot_font_size', '14')) . '"> px</td></tr>';
    echo '<tr><th>Home Title</th><td><input type="text" name="chatbot_home_title" value="' . esc_attr(get_option('chatbot_home_title', 'Hello, User!')) . '"></td></tr>';
    echo '<tr><th>Home Subtitle</th><td><input type="text" name="chatbot_home_subtitle" value="' . esc_attr(get_option('chatbot_home_subtitle', 'User Support')) . '"></td></tr>';
    echo '<tr><th>Start Text</th><td><input type="text" name="chatbot_home_button" value="' . esc_attr(get_option('chatbot_home_button', 'START CHAT')) . '"></td></tr>';
    echo '<tr><th>Restart Text</th><td><input type="text" name="chatbot_restart_text" value="' . esc_attr(get_option('chatbot_restart_text', 'Restart')) . '"></td></tr>';
    echo '<tr><th>Close Text</th><td><input type="text" name="chatbot_close_text" value="' . esc_attr(get_option('chatbot_close_text', 'Close Chat')) . '"></td></tr>';
    echo '</table>';
    submit_button('Save display settings');
    echo '</form></div>';
}

function find_node_by_id($nodes, $id) {
    foreach ($nodes as $node) {
        if ($node->id == $id) {
            return $node;
        }
    }
    return null;
}

function duplicate_node_recursive($source_id, $target_parent_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_nodes';
    
    $source_node = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d", 
        $source_id
    ));
    
    if (!$source_node) return;
    
    $wpdb->insert($table_name, [
        'parent_id' => $target_parent_id,
        'node_type' => $source_node->node_type,
        'node_text' => $source_node->node_text
    ]);
    
    $new_parent_id = $wpdb->insert_id;
    
    $children = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE parent_id = %d",
        $source_id
    ));
    
    foreach ($children as $child) {
        duplicate_node_recursive($child->id, $new_parent_id);
    }
}

function build_node_paths($nodes) {
    $node_paths = array();
    $node_lookup = array();
    
    foreach ($nodes as $node) {
        $node_lookup[$node->id] = $node;
    }
    
    $get_path = function($node_id) use (&$get_path, $node_lookup) {
        $node = $node_lookup[$node_id] ?? null;
        if (!$node) return '';
        if ($node->parent_id == 0) return $node->node_text;
        $parent_path = $get_path($node->parent_id);
        return $parent_path . ' → ' . $node->node_text;
    };
    
    foreach ($nodes as $node) {
        $node_paths[$node->id] = $get_path($node->id);
    }
    
    return $node_paths;
}

function render_node_tree(&$node_tree, $node_paths, $all_nodes, $parent_id = 0, $depth = 0) {
    if (empty($node_tree[$parent_id])) return;
    
    echo '<ul style="list-style:none;margin-left:' . ($depth * 20) . 'px">';
    foreach ($node_tree[$parent_id] as $node) {
        echo '<li style="background:' . ($node->node_type === 'question' ? '#e0f0ff' : '#fff') . ';padding:8px;margin:4px 0;border:1px solid #ddd">';
        echo '<div style="display:flex;justify-content:space-between">';
        echo '<div><strong>' . ucfirst($node->node_type) . ':</strong> ' . esc_html($node->node_text) . '</div>';
        echo '<div style="display:flex;gap:4px">';
        
        echo '<form method="post" style="margin:0">';
        echo '<input type="hidden" name="node_id" value="' . $node->id . '">';
        echo '<input type="hidden" name="node_text" value="' . esc_attr($node->node_text) . '">';
        echo '<input type="submit" name="edit_node" class="button button-small" value="Edit" style="background:#ffba00;border-color:#ffba00">';
        echo '</form>';
        
        echo '<form method="post" style="margin:0">';
        echo '<input type="hidden" name="node_id" value="' . $node->id . '">';
        echo '<input type="submit" name="move_node" class="button button-small" value="Move" style="background:#2196F3;border-color:#2196F3">';
        echo '</form>';
        
        echo '<form method="post" style="margin:0">';
        echo '<input type="hidden" name="node_id" value="' . $node->id . '">';
        echo '<input type="submit" name="duplicate_node" class="button button-small" value="Duplicate" style="background:#4CAF50;border-color:#4CAF50">';
        echo '</form>';
        
        echo '<form method="post" style="margin:0">';
        echo '<input type="hidden" name="node_id" value="' . $node->id . '">';
        echo '<input type="submit" name="delete_node" class="button button-small" value="Delete" onclick="return confirm(\'Delete this node and its children?\')">';
        echo '</form>';
        
        echo '</div></div>';
        
        if (isset($_POST['edit_node']) && intval($_POST['node_id']) == $node->id) {
            echo '<form method="post" style="margin-top:10px;background:#fff8e5;padding:10px;border-radius:4px">';
            echo '<input type="hidden" name="node_id" value="' . $node->id . '">';
            echo '<textarea name="node_text" style="width:100%;height:80px">' . esc_textarea($node->node_text) . '</textarea>';
            echo '<p style="margin:5px 0 0"><input type="submit" name="update_node" class="button button-small" value="Update"></p>';
            echo '</form>';
        }
        
        if (isset($_POST['move_node']) && intval($_POST['node_id']) == $node->id) {
            echo '<form method="post" style="margin-top:10px;background:#e3f2fd;padding:10px;border-radius:4px">';
            echo '<input type="hidden" name="node_id" value="' . $node->id . '">';
            echo '<p><label>Move to:<br>';
            echo '<select name="new_parent_id" style="width:100%">';
            echo '<option value="0">-- Root Node --</option>';
            foreach ($node_paths as $node_id => $path) {
                if ($node_id == $node->id) continue;
                
                $target_node = find_node_by_id($all_nodes, $node_id);
                
                if ($target_node && $target_node->node_type === 'question') {
                    echo '<option value="' . $node_id . '">' . esc_html($path) . '</option>';
                }
            }
            echo '</select></label></p>';
            echo '<p style="margin:5px 0 0"><input type="submit" name="move_node_submit" class="button button-small" value="Confirm Move"></p>';
            echo '</form>';
        }
        
        if (isset($_POST['duplicate_node']) && intval($_POST['node_id']) == $node->id) {
            echo '<form method="post" style="margin-top:10px;background:#f3e5f5;padding:10px;border-radius:4px">';
            echo '<input type="hidden" name="node_id" value="' . $node->id . '">';
            echo '<p><label>Duplicate to:<br>';
            echo '<select name="target_parent_id" style="width:100%">';
            echo '<option value="0">-- Root Node --</option>';
            foreach ($node_paths as $node_id => $path) {
                if ($node_id == $node->id) continue;
                
                $target_node = find_node_by_id($all_nodes, $node_id);
                
                if ($target_node && $target_node->node_type === 'question') {
                    echo '<option value="' . $node_id . '">' . esc_html($path) . '</option>';
                }
            }
            echo '</select></label></p>';
            echo '<p style="margin:5px 0 0"><input type="submit" name="duplicate_node_submit" class="button button-small" value="Confirm Duplicate"></p>';
            echo '</form>';
        }
        
        render_node_tree($node_tree, $node_paths, $all_nodes, $node->id, $depth + 1);
        echo '</li>';
    }
    echo '</ul>';
}

add_action('admin_init', 'chatbot_appearance_settings');
function chatbot_appearance_settings() {
    register_setting('chatbot_appearance', 'chatbot_btn_color');
    register_setting('chatbot_appearance', 'chatbot_bg_color');
    register_setting('chatbot_appearance', 'chatbot_font_size');
    register_setting('chatbot_appearance', 'chatbot_btn_text');
    register_setting('chatbot_appearance', 'chatbot_home_title');
    register_setting('chatbot_appearance', 'chatbot_home_subtitle');
    register_setting('chatbot_appearance', 'chatbot_home_button');
    register_setting('chatbot_appearance', 'chatbot_restart_text');
    register_setting('chatbot_appearance', 'chatbot_close_text');
}

add_action('wp_enqueue_scripts', 'simple_chatbot_scripts');
function simple_chatbot_scripts() {
    wp_enqueue_style('simple-chatbot-css', plugin_dir_url(__FILE__) . 'style.css');
    wp_enqueue_script('simple-chatbot-js', plugin_dir_url(__FILE__) . 'script.js', ['jquery'], null, true);
    
    wp_localize_script('simple-chatbot-js', 'chatbotData', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'btn_text' => get_option('chatbot_btn_text', 'Need Help?'),
        'home_title' => get_option('chatbot_home_title', 'Hello, User'),
        'home_subtitle' => get_option('chatbot_home_subtitle', 'User Support'),
        'home_button' => get_option('chatbot_home_button', 'START SUPPORT'),
        'restart_text' => get_option('chatbot_restart_text', 'Restart Conversation'),
        'close_text' => get_option('chatbot_close_text', 'Close Chat')
    ]);
}

add_action('wp_ajax_chatbot_get_nodes', 'chatbot_get_nodes');
add_action('wp_ajax_nopriv_chatbot_get_nodes', 'chatbot_get_nodes');
function chatbot_get_nodes() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'chatbot_nodes';
    $parent_id = intval($_POST['parent_id']);
    
    $nodes = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE parent_id = %d ORDER BY id ASC",
        $parent_id
    ));
    
    wp_send_json_success($nodes);
}

add_action('wp_footer', 'simple_chatbot_interface');
function simple_chatbot_interface() {
    echo '<div id="chatbot-container">';
    echo '<div id="chatbot-header">';
    echo '<span>'.esc_html(get_option('chatbot_btn_text', 'Need Help?')).'</span>';
    echo '<button id="chatbot-close">×</button>';
    echo '</div>';
    echo '<div id="chatbot-conversation"></div>';
    echo '<div id="chatbot-options"></div>';
    echo '</div>';
    echo '<button id="chatbot-toggle">?</button>';
}

add_action('wp_head', 'chatbot_dynamic_css');
function chatbot_dynamic_css() {
    $btn_color = get_option('chatbot_btn_color', '#21759b');
    $bg_color = get_option('chatbot_bg_color', '#f5f5f5');
    $font_size = get_option('chatbot_font_size', '14');
    echo '<style>
        #chatbot-container {
            --btn-color: ' . $btn_color . ';
            --bg-color: ' . $bg_color . ';
            --font-size: ' . $font_size . 'px;
        }
    </style>';
}
