<?php
// This file is part of Ranking block for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Theme moodlemoot block settings file
 *
 * @package    theme_moodlemoot
 * @copyright  2019 Willian Mano {@link http://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This line protects the file from being accessed by a URL directly.
defined('MOODLE_INTERNAL') || die();

// This is used for performance, we don't need to know about these settings on every page in Moodle, only when
// we are looking at the admin settings pages.
if ($ADMIN->fulltree) {

    // Boost provides a nice setting page which splits settings onto separate tabs. We want to use it here.
    $settings = new theme_boost_admin_settingspage_tabs('themesettingmoodlemoot', get_string('configtitle', 'theme_moodlemoot'));

    /*
    * ----------------------
    * General settings tab
    * ----------------------
    */
    $page = new admin_settingpage('theme_moodlemoot_general', get_string('generalsettings', 'theme_moodlemoot'));

    // Course format option.
    $name = 'theme_moodlemoot/currentedition';
    $title = get_string('currentedition', 'theme_moodlemoot');
    $description = get_string('currenteditiondesc', 'theme_moodlemoot');
    $options = theme_moodlemoot\util\extras::get_courseslist_select();
    $setting = new admin_setting_configselect($name, $title, $description, $default, $options);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Google analytics block.
    $name = 'theme_moodlemoot/googleanalytics';
    $title = get_string('googleanalytics', 'theme_moodlemoot');
    $description = get_string('googleanalyticsdesc', 'theme_moodlemoot');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);

    /*
    * --------------------
    * Footer settings tab
    * --------------------
    */
    $page = new admin_settingpage('theme_moodlemoot_footer', get_string('footersettings', 'theme_moodlemoot'));

    // Facebook url setting.
    $name = 'theme_moodlemoot/facebook';
    $title = get_string('facebook', 'theme_moodlemoot');
    $description = get_string('facebookdesc', 'theme_moodlemoot');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Instagram url setting.
    $name = 'theme_moodlemoot/instagram';
    $title = get_string('instagram', 'theme_moodlemoot');
    $description = get_string('instagramdesc', 'theme_moodlemoot');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Twitter url setting.
    $name = 'theme_moodlemoot/twitter';
    $title = get_string('twitter', 'theme_moodlemoot');
    $description = get_string('twitterdesc', 'theme_moodlemoot');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Youtube url setting.
    $name = 'theme_moodlemoot/youtube';
    $title = get_string('youtube', 'theme_moodlemoot');
    $description = get_string('youtubedesc', 'theme_moodlemoot');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Telegram url setting.
    $name = 'theme_moodlemoot/telegram';
    $title = get_string('telegram', 'theme_moodlemoot');
    $description = get_string('telegramdesc', 'theme_moodlemoot');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Mail.
    $name = 'theme_moodlemoot/mail';
    $title = get_string('mail', 'theme_moodlemoot');
    $description = get_string('maildesc', 'theme_moodlemoot');
    $default = 'willianmano@conecti.me';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Faq url setting.
    $name = 'theme_moodlemoot/faq';
    $title = get_string('faq', 'theme_moodlemoot');
    $description = get_string('faqdesc', 'theme_moodlemoot');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);
}
