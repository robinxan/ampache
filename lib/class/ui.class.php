<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

// A collection of methods related to the user interface

class UI {

    private static $_classes;
    private static $_ticker;
    private static $_icon_cache;

    public function __construct($data) {
        return false;
    }

    /**
     * access_denied
     *
     * Throw an error when they try to do something naughty.
     */
    public static function access_denied($error = 'Access Denied') {
        // Clear any buffered crap
        ob_end_clean();
        header("HTTP/1.1 403 $error");
        require_once Config::get('prefix') . '/templates/show_denied.inc.php';
        exit;
    }

    /**
     * ajax_include
     *
     * Does some trickery with the output buffer to return the output of a
     * template.
     */
    public static function ajax_include($template) {
        ob_start();
        require Config::get('prefix') . '/templates/' . $template;
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    /**
     * check_iconv
     *
     * Checks to see whether iconv is available;
     */
    public static function check_iconv() {
        if (function_exists('iconv') && function_exists('iconv_substr')) {
            return true;
        }
        return false;
    }

    /**
     * check_ticker
     *
     * Stupid little cutesie thing to ratelimit output of long-running
     * operations.
     */
    public static function check_ticker() {
        if (!isset(self::$_ticker) || (time() > self::$_ticker + 1)) {
            self::$_ticker = time();
            return true;
        }

        return false;
    }

    /**
     * flip_class
     *
     * First initialised with an array of two class names. Subsequent calls
     * reverse the array then return the first element.
     */
    public static function flip_class($classes = null) {
        if (is_array($classes)) {
            self::$_classes = $classes;
        }
        else {
            self::$_classes = array_reverse(self::$_classes);
        }
        return self::$_classes[0];
    }

    /**
     * format_bytes
     *
     * Turns a size in bytes into the best human-readable value
     */
    public static function format_bytes($value, $precision = 2) {
        $pass = 0;
        while (strlen(floor($value)) > 3) {
            $value /= 1024;
            $pass++;
        }

        switch ($pass) {
            case 1: $unit = 'kB'; break;
            case 2: $unit = 'MB'; break;
            case 3: $unit = 'GB'; break;
            case 4: $unit = 'TB'; break;
            case 5: $unit = 'PB'; break;
            default: $unit = 'B'; break;
        }

        return round($value, $precision) . ' ' . $unit;
    }

    /**
     * unformat_bytes
     *
     * Parses a human-readable size
     */
    public static function unformat_bytes($value) {
        if (preg_match('/^([0-9]+) *([[:alpha:]]+)$/', $value, $matches)) {
            $value = $matches[1];
            $unit = strtolower(substr($matches[2], 0, 1));
        }
        else {
            return $value;
        }

        switch($unit) {
            case 'p':
                $value *= 1024;
            case 't':
                $value *= 1024;
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * get_icon
     *
     * Returns an <img> tag for the specified icon
     */
    public static function get_icon($name, $title = null, $id = null) {
        if (is_array($name)) {
            $hover_name = $name[1];
            $name = $name[0];
        }

        $title = $title ?: T_(ucfirst($name));

        $icon_url = self::_find_icon($name);
        if ($hover_name) {
            $hover_url = self::_find_icon($hover_text);
        }

        $tag = '<img src="' . $icon_url . '" ';

        if ($id) {
            $tag .= 'id="' . $id . '" ';
        }

        $tag .= 'alt="' . $title . '" ';
        $tag .= 'title="' . $title . '" ';

        if ($hover_name) {
            $tag .= 'onmouseover="this.src=\'' . $hover_url . '\'; return true;"';
            $tag .= 'onmouseout="this.src=\'' . $icon_url . '\'; return true;" ';
        }

        $tag .= '/>';
        return $tag;
    }

    /**
     * _find_icon
     *
     * Does the finding icon thing
     */
    private static function _find_icon($name) {
        if ($url = self::$_icon_cache[$name]) {
            return $url;
        }

        $filename = 'icon_' . $name . '.png';
        $path = Config::get('theme_path') . '/images/icons/';
        if (!file_exists(Config::get('prefix') . $path . $filename)) {
            $path = '/images/';
        }
        $url = Config::get('web_path') . $path . $filename;
        self::$_icon_cache[$name] = $url;
        
        return $url;
    }


    /**
     * show_header
     *
     * For now this just shows the header template
     */
    public static function show_header() {
        require_once Config::get('prefix') . '/templates/header.inc.php';
    }

    /**
     * show_footer
     *
     * Shows the footer template and possibly profiling info.
     */
    public static function show_footer() {
        require_once Config::get('prefix') . '/templates/footer.inc.php';
        if (isset($_REQUEST['profiling'])) {
            Dba::show_profile();
        }
    }

    /**
     * show_box_top
     *
     * This shows the top of the box.
     */
    public static function show_box_top($title = '', $class = '') {
        require Config::get('prefix') . '/templates/show_box_top.inc.php';
    }

    /**
     * show_box_bottom
     *
     * This shows the bottom of the box
     */
    public static function show_box_bottom() {
        require Config::get('prefix') . '/templates/show_box_bottom.inc.php';
    }

    /**
     * truncate
     *
     * Limit text to a certain length; adds an ellipsis if truncation was
     * required.
     */
    public static function truncate($text, $max = 27) {
        // If they want <3, we're having none of that
        if ($max <= 3) {
            debug_event('UI', "truncate called with $max, refusing to do stupid things to $text", 2);
            return $text;
        }

        if (self::check_iconv()) {
            $charset = Config::get('site_charset');
            if (iconv_strlen($text, $charset) > $max) {
                $text = iconv_substr($text, 0, $max - 3, $charset);
                $text .= iconv('ISO-8859-1', $charset, '...');
            }
        }
        else {
            if (strlen($text) > $max) {
                $text = substr($text, 0, $max - 3) . '...';
            }
        }

        return $text;
    }

    /**
     * update_text
     *
     * Convenience function that, if the output is going to a browser,
     * blarfs JS to do a fancy update.  Otherwise it just outputs the text.
     */
    public static function update_text($field, $value) {
        if (defined('CLI')) {
            echo $value . "\n";
            return;
        }

        echo '<script type="text/javascript">';
        echo "updateText('$field', '$value');";
        echo "</script>\n";
        ob_flush();
        flush();
    }
}
