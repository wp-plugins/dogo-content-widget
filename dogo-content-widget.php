<?php
/*
Plugin Name: DOGO Content Widget
Plugin URI: http://www.dogonews.com
Description: This plug-in displays the latest content from DOGO websites:  Expose students to current events from DOGOnews.com, the leading source of content for Common Core State Standards ELA, science and social studies.  Share latest book reviews from DOGObooks.com, where kids review and rate books.  And for some fun, share movie reviews by kids from DOGOmovies.com.
Author: DOGO Media Inc.
Version: 1.1
Author URI: http://www.dogonews.com

/* License

    DOGO Content Widget
    Copyright (C) 2012 DOGO Media Inc.

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
    
*/

add_action('wp_enqueue_scripts', 'add_dogo_rss_css');

function add_dogo_rss_css() {

    $dogo_rss_myStyleUrl = plugins_url('style.css', __FILE__); // Respects SSL, Style.css is relative to the current file
    $dogo_rss_myStyleFile = WP_PLUGIN_DIR . '/dogo-content-widget/style.css';

    if ( file_exists($dogo_rss_myStyleFile) ) {
        wp_register_style('dogoRSScss', $dogo_rss_myStyleUrl);
        wp_enqueue_style( 'dogoRSScss');
    }
}

function get_dogo_feed_list($site_name, $root_rss_url, $category_base_rss_url, $home_url, $icon_url, $logo_url, $category, $widget_style, $maxfeeds=9, $show_desc=NULL, $target='samewindow', $useenclosures='yes', $thumbwidth=130, $thumbheight=80, $showlogo='yes') {
// This is the main function of the plugin. It is used by the widget and can also be called from anywhere in your theme. See the readme file for example.

    // Get Dogo Feed(s)
    include_once(ABSPATH . WPINC . '/feed.php');

    if( !empty($category) && $category != 'latest' ){
        $dogofeed = $category_base_rss_url.$category.'.rss';
    }
    else {
        $dogofeed = $root_rss_url;
    }

    // Get a SimplePie feed object from the Dogo feed source
    $rss = fetch_feed($dogofeed);
    $rss->set_timeout(60);

    // Figure out how many total items there are.
    $maxitems = $rss->get_item_quantity((int)$maxfeeds);

    // Build an array of all the items, starting with element 0 (first element).
    $rss_items = $rss->get_items(0,$maxitems);

    $content = '';
    $content .= '<div class="dogoRecommendationWidgetContent post-table dogoWidget-'.$widget_style.'">';
    // Loop through each feed item and display each item as a hyperlink.
    foreach ( $rss_items as $item ) :
        $content .= '<div class="clearfix pas dogoRecommendation">';

        $content .= '<a class="dogoImageContainer cls29h cls303" href="'.$item->get_permalink().'"';
        if ($target == 'newwindow') { $content .= 'target="_BLANK" '; };
        $content .= 'title="'.$item->get_title().' - Posted on '.$item->get_date('M d, Y').'">';

        $thumb = null;
        if ($thumb = $item->get_item_tags(SIMPLEPIE_NAMESPACE_MEDIARSS, 'thumbnail') ) {
            $thumb = $thumb[0]['attribs']['']['url'];

        } else if ( $useenclosures == 'yes' && $enclosure = $item->get_enclosure() ) {
            $enclosure = $item->get_enclosures();
            $thumb = $enclosure[0]->get_link();
        }  else {
            preg_match('/src="([^"]*)"/', $item->get_content(), $matches);
            $src = $matches[1];

            if ($matches) {
                $thumb = $src;
            }
        }

        if ($thumb != null) {
            $content .= '<img class="img" src="'.$thumb.'" alt="'.$item->get_title().'"/>';
        }
        $content .= '</a>';

        $content .= '<div class="cls3dp cls29k">';
        $content .= '<strong>';
        $content .= '<a href="'.$item->get_permalink().'">';
        $content .= $item->get_title();
        $content .= '</a>';
        $content .= '</strong>';
        $content .= '<div class="recommendations_metadata">';
        if ($show_desc) {
            if ($show_desc != 'no') {
                $desc = str_replace(array("\n", "\r"), ' ', esc_attr(strip_tags(@html_entity_decode($item->get_description(), ENT_QUOTES, get_option('blog_charset')))));
                $desc = trim($desc);

                if ($show_desc == 'short') {
                    if (50 < strlen($desc)) {
                        $content .= substr($desc,0,50) . "...";
                    }
                    else {
                        $content .= $desc;
                    }
                }
                else {
                    $content .= $desc;
                }
            }
        }
        $content .= '</div>';
        $content .= '</div>';

        $content .= '</div>';

    endforeach;
    $content .= '</div>';

    if ($showlogo != 'no') {
        $content .= '<a class="dogo-logo" href='.$home_url.' target="_blank" title="'.$site_name.'">';
        $content .= '<img src='.$icon_url.' style="height:16px;width:16px;position:relative;top:-1px"/>';
        $content .= '<img src='.$logo_url.' />';
        $content .= '</a>';
    }
    return $content;
}

abstract class Base_DOGO_Widget extends WP_Widget {

    function widget($args, $instance) {
        extract($args, EXTR_SKIP);

        echo $before_widget;

        $title = empty($instance['title']) ? '&nbsp;' : apply_filters('widget_title', $instance['title']);
        $category = empty($instance['category']) ? '' : $instance['category'];
        $maxnumber = empty($instance['maxnumber']) ? 9 : $instance['maxnumber'];
        $thumb_height = empty($instance['thumb_height']) ? '' : $instance['thumb_height'];
        $thumb_width = empty($instance['thumb_width']) ? '' : $instance['thumb_width'];
        $target = empty($instance['target']) ? '&nbsp;' : $instance['target'];
        $widget_style = empty($instance['widget_style']) ? 'horizontal' : $instance['widget_style'];
        $show_desc = empty($instance['show_desc']) ? '&nbsp' : $instance['show_desc'];
        $useenclosures = empty($instance['useenclosures']) ? '&nbsp;' : $instance['useenclosures'];
        $showlogo = empty($instance['showlogo']) ? '' : $instance['showlogo'];

        if ( !empty( $title ) ) { echo $before_title . $title . $after_title; };
        if ( empty( $category ) ) { $category = ''; };
        if ( empty( $target ) ) { $target = 'samewindow'; };
        if ( empty( $show_desc ) ) { $show_desc = 'short'; };
        if ( empty( $useenclosures ) ) { $useenclosures = 'yes'; };
        if ( empty( $thumb_width ) ) { $thumb_width = $this->default_thumb_width; };
        if ( empty( $thumb_height ) ) { $thumb_height = $this->default_thumb_height; };
        if ( empty( $showlogo ) ) { $showlogo = 'no'; };

        { echo get_dogo_feed_list($this->site_name, $this->root_rss_url, $this->category_base_rss_url, $this->home_url, $this->icon_url, $this->logo_url, $category, $widget_style, $maxnumber, $show_desc, $target, $useenclosures, $thumb_width, $thumb_height, $showlogo); ?>
        <div style="clear:both;"></div>
        <?php }

        echo $after_widget;
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['category'] = strip_tags($new_instance['category']);
        $instance['maxnumber'] = strip_tags($new_instance['maxnumber']);
        $instance['thumb_height'] = strip_tags($new_instance['thumb_height']);
        $instance['thumb_width'] = strip_tags($new_instance['thumb_width']);
        $instance['target'] = strip_tags($new_instance['target']);
        $instance['widget_style'] = strip_tags($new_instance['widget_style']);
        $instance['show_desc'] = strip_tags($new_instance['show_desc']);
        $instance['useenclosures'] = strip_tags($new_instance['useenclosures']);
        $instance['showlogo'] = strip_tags($new_instance['showlogo']);

        return $instance;
    }

    function shortcode_handler( $atts )	{

        extract( shortcode_atts( array(
                    'category' => '',
                    'maxfeeds' => 9,
                    'widget_style' => 'horizontal',
                    'show_desc' => NULL,
                    'target' => 'samewindow',
                    'useenclosures' => 'yes',
                    'thumbwidth' => $this->default_thumb_width,
                    'thumbheight' => $this->default_thumb_height,
                    'showlogo' => 'no'
                ), $atts
            )
        );

        return get_dogo_feed_list($this->site_name, $this->root_rss_url, $this->category_base_rss_url, $this->home_url, $this->icon_url, $this->logo_url, $category, $widget_style, $maxfeeds, $show_desc, $target, $useenclosures, $thumbwidth, $thumbheight, $showlogo);
    }

    function form($instance) {
        $instance = wp_parse_args( (array) $instance, array( 'title' => '', 'category' => '', 'maxnumber' => '', 'thumb_height' => '', 'thumb_width' => '', 'target' => '', 'widget_style' => '', 'show_desc' => '', 'useenclosures' => '', 'showlogo' => 'no') );
        $title = strip_tags($instance['title']);
        $category = strip_tags($instance['category']);
        $maxnumber = empty($instance['maxnumber']) ? 9 : $instance['maxnumber'];
        $thumb_height = strip_tags($instance['thumb_height']);
        $thumb_width = strip_tags($instance['thumb_width']);
        $target = strip_tags($instance['target']);
        $widget_style = strip_tags($instance['widget_style']);
        $show_desc = strip_tags($instance['show_desc']);
        $useenclosures = strip_tags($instance['useenclosures']);
        $showlogo = strip_tags($instance['showlogo']);
        ?>
    <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <br /><input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" /></label></p>
    <p><label for="<?php echo $this->get_field_id('maxnumber'); ?>">Number of articles to display: </label><input id="<?php echo $this->get_field_id('maxnumber'); ?>" name="<?php echo $this->get_field_name('maxnumber'); ?>" type="text" size="3" value="<?php echo attribute_escape($maxnumber); ?>" /></p>

    <p><label for="<?php echo $this->get_field_id('category'); ?>">Category <br /><select id="<?php echo $this->get_field_id('category'); ?>" name="<?php echo $this->get_field_name('category'); ?>">
        <?php
        foreach ( array_keys($this->categories) as $category ) :
            echo '<option ';
            if ( $instance['category'] == $category ) { echo 'selected '; }
            echo 'value="'.$category.'">'.$this->categories[$category].'</option>';
        endforeach;
        ?>
    </select></label></p>

    <p><label for="<?php echo $this->get_field_id('widget_style'); ?>">Widget Styling <br /><select id="<?php echo $this->get_field_id('widget_style'); ?>" name="<?php echo $this->get_field_name('widget_style'); ?>">
        <?php
        echo '<option ';
        if ( $instance['widget_style'] == 'horizontal' ) { echo 'selected '; }
        echo 'value="horizontal">';
        echo 'Horizontal</option>';
        echo '<option ';
        if ( $instance['widget_style'] == 'vertical' ) { echo 'selected '; }
        echo 'value="vertical">';
        echo 'Vertical</option>'; ?>
    </select></label></p>

    <p><label for="<?php echo $this->get_field_id('show_desc'); ?>">Display description below title? <br /><select id="<?php echo $this->get_field_id('show_desc'); ?>" name="<?php echo $this->get_field_name('show_desc'); ?>">
        <?php
        echo '<option ';
        if ( $instance['show_desc'] == 'short' ) { echo 'selected '; }
        echo 'value="short">';
        echo 'Short description</option>';
        echo '<option ';
        if ( $instance['show_desc'] == 'full' ) { echo 'selected '; }
        echo 'value="full">';
        echo 'Full description</option>';
        echo '<option ';
        if ( $instance['show_desc'] == 'no' ) { echo 'selected '; }
        echo 'value="no">';
        echo 'No description</option>'; ?>
    </select></label></p>

    <p><label for="<?php echo $this->get_field_id('target'); ?>">Where to open the links: <br /><select id="<?php echo $this->get_field_id('target'); ?>" name="<?php echo $this->get_field_name('target'); ?>">
        <?php
        echo '<option ';
        if ( $instance['target'] == 'samewindow' ) { echo 'selected '; }
        echo 'value="samewindow">';
        echo 'Same Window</option>';
        echo '<option ';
        if ( $instance['target'] == 'newwindow' ) { echo 'selected '; }
        echo 'value="newwindow">';
        echo 'New Window</option>'; ?>
    </select></label></p>

    <p><label for="<?php echo $this->get_field_id('showlogo'); ?>">Show "<?php echo $this->site_name; ?>" button? <br /><select id="<?php echo $this->get_field_id('showlogo'); ?>" name="<?php echo $this->get_field_name('showlogo'); ?>">
        <?php
        echo '<option ';
        if ( $instance['showlogo'] == 'yes' ) { echo 'selected '; }
        echo 'value="yes">';
        echo 'Yes</option>';
        echo '<option ';
        if ( $instance['showlogo'] == 'no' ) { echo 'selected '; }
        echo 'value="no">';
        echo 'No</option>';
        ?>
    </select></label></p>

    <?php
    }
}

class DOGOnews_RSS_Widget extends Base_DOGO_Widget {
    var $site_name = "DOGOnews";
    var $root_rss_url = "http://www.dogonews.com/articles.rss";
    var $category_base_rss_url = "http://www.dogonews.com/category/";
    var $home_url = "http://www.dogonews.com";
    var $icon_url = "http://cdn.dogonews.com/assets/icon/dogo-16b-9de7ee962beaa95031746cd1bf77541d.png";
    var $logo_url = "http://cdn.dogonews.com/assets/dogonews_text-b37207b0559ec94f4333da97598fb540.png";
    var $default_thumb_width = 130;
    var $default_thumb_height = 80;
    var $widget_class = 'dogonews_rss_widget';
    var $categories = array(
        'latest' => 'Current Events',
        'science' => 'Science',
        'sports' => 'Sports',
        'social-studies' => 'Social Studies',
        'did-you-know' => 'Did You Know',
        'green' => 'Green',
        'general' => 'General',
        'entertainment' => 'Entertainment',
        'international' => 'International',
        'amazing' => 'Amazing',
        'fun' => 'Fun',
        'video-gallery' => 'Video'
    );

    function DOGOnews_RSS_Widget() {
        $widget_ops = array('classname' => $this->widget_class, 'description' => 'A widget to display the latest DOGOnews current events and news' );
        $this->WP_Widget('dogonews_rss_widget', 'DOGOnews - Current Events', $widget_ops);
        add_shortcode('dogonews', array($this, 'shortcode_handler'));
    }
}

class DOGObooks_RSS_Widget extends Base_DOGO_Widget {
    var $site_name = "DOGObooks";
    var $root_rss_url = "http://www.dogobooks.com/latest.rss";
    var $category_base_rss_url = "http://www.dogobooks.com/books/";
    var $home_url = "http://www.dogobooks.com";
    var $icon_url = "http://cdn.dogonews.com/assets/icon/books-747581a181429b8a4ccad5d85e3ccdfa.png";
    var $logo_url = "http://cdn.dogonews.com/assets/dogobooks_text-13e0081386f332e49fe67ccd272df116.png";
    var $default_thumb_width = 60;
    var $default_thumb_height = 90;
    var $widget_class = 'dogobooks_rss_widget';
    var $categories = array(
        'latest' => 'Latest',
        'science-fiction' => 'Science Fiction',
        'adventure' => 'Adventure',
        'biography' => 'Biography',
        'non-fiction' => 'Non-Fiction',
        'fiction' => 'Fiction',
        'mystery' => 'Mystery',
        'poetry' => 'Poetry'
    );

    function DOGObooks_RSS_Widget() {
        $widget_ops = array('classname' => $this->widget_class, 'description' => 'A widget to display the latest DOGObooks kids book reviews' );
        $this->WP_Widget('dogobooks_rss_widget', 'DOGObooks - Book Reviews', $widget_ops);
        add_shortcode('dogobooks', array($this, 'shortcode_handler'));
    }
}

class DOGOmovies_RSS_Widget extends Base_DOGO_Widget {
    var $site_name = "DOGOmovies";
    var $root_rss_url = "http://www.dogomovies.com/reviews.rss";
    var $category_base_rss_url = "http://www.dogomovies.com/category/";
    var $home_url = "http://www.dogomovies.com";
    var $icon_url = "http://cdn.dogonews.com/assets/icon/movies-60ec2105ddd8b60accba821331c13378.png";
    var $logo_url = "http://cdn.dogonews.com/assets/dogomovies_text-0cdfb3a1f297c57890ab56bba79b759e.png";
    var $default_thumb_width = 60;
    var $default_thumb_height = 90;
    var $widget_class = 'dogomovies_rss_widget';
    var $categories = array(
        'latest' => 'Latest',
        'action' => 'Action',
        'adventure' => 'Adventure',
        'animation' => 'Animation',
        'comedy' => 'Comedy',
        'drama' => 'Drama',
        'fantasy' => 'Fantasy',
        'music' => 'Music',
        'mystery' => 'Mystery',
        'romance' => 'Romance',
        'science-fiction' => 'Science Fiction',
        'thriller' => 'Thriller'
    );

    function DOGOmovies_RSS_Widget() {
        $widget_ops = array('classname' => $this->widget_class, 'description' => 'A widget to display the latest DOGOmovies kids movie reviews' );
        $this->WP_Widget('dogomovies_rss_widget', 'DOGOmovies - Movie Reviews', $widget_ops);
        add_shortcode('dogomovies', array($this, 'shortcode_handler'));
    }
}

add_action( 'widgets_init', create_function('', 'return register_widget("DOGOnews_RSS_Widget");') );
add_action( 'widgets_init', create_function('', 'return register_widget("DOGObooks_RSS_Widget");') );
add_action( 'widgets_init', create_function('', 'return register_widget("DOGOmovies_RSS_Widget");') );

add_filter( 'wp_feed_cache_transient_lifetime', create_function('$a', 'return 600;') );

?>