<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
//
// iLabs has been developed by Luis de la Torre: ldelatorre@dia.uned.es
// at the Universidad Nacional de Educacion a Distancia, Madrid, Spain.

/**
 * Lib file for the ilabs repository plugin.
 *
 * @package    repository_ilabs
 * @copyright  2013 Luis de la Torre
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/repository/lib.php');
require_once(__DIR__ . '/ilabs.php');

/**
 * This is a class used to browse experiments from the iLabs collection.
 *
 * @package    repository_ilabs
 * @copyright  2013 Luis de la Torre
 * @author     Luis de la Torre
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_ilabs extends repository {
    /**
     * @var array
     */
    private $keywords;

    /**
     * repository_ilabs constructor.
     *
     * @param int $repositoryid
     * @param bool|int|stdClass $context
     * @param array $options
     * @throws
     */
    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()) {
        global $SESSION;
        parent::__construct($repositoryid, $context, $options);
        $this->keywords = optional_param('ilabs_keyword', '', PARAM_RAW);
        if (empty($this->keywords)) {
            $this->keywords = optional_param('s', '', PARAM_RAW);
        }
        $keyword = 'ilabs_' . $this->id . '_keyword';
        if (empty($this->keywords) && optional_param('page', '', PARAM_RAW)) {
            // This is the request of another page for the last search, retrieve the cached keywords.
            if (isset($SESSION->{$keyword})) {
                $this->keywords = $SESSION->{$keyword};
            }
        } else if (!empty($this->keywords)) {
            // Save the search keywords in the session so we can retrieve it later.
            $SESSION->{$keyword} = $this->keywords;
        }
    }

    /**
     * Get the list of experiments in the iLabs repository.
     *
     * @param string $path
     * @param string $page
     * @return array list
     */
    public function get_listing($path = '', $page = '') {
        global $CFG;
        $client = new ilabs;
        $list = array();
        $list['page'] = (int)$page;
        if ($list['page'] < 1) {
            $list['page'] = 1;
        }
        $list['manage'] = $client->ilabs_url . '#login-dialog';
        $list['help'] = $client->ilabs_url;
        $list['list'] = $client->search_experiments($client->format_keywords($this->keywords), $list['page'] - 1);
        $list['nologin'] = true;
        $list['norefresh'] = false;
        if (!empty($list['list'])) {
            $list['pages'] = -1; // Means we don't know exactly how many pages there are but we can always jump to the next page.
        } else if ($list['page'] > 1) {
            $list['pages'] = $list['page']; // No images available on this page, this is the last page.
        } else {
            $list['pages'] = 0; // No paging.
        }
        return $list;
    }

    /**
     * If this plugin supports global search, this function returns true.
     * Search function will be called when global searching is working
     *
     * @return bool
     */
    public function global_search() {
        return false;
    }

    /**
     * Searches for experiments in the iLabs repository.
     *
     * @param string $searchtext
     * @param string $page
     * @return array
     */
    public function search($searchtext, $page = '') {
        global $CFG;

        $client = new ilabs;
        $list = array();
        $list['page'] = (int)$page;
        if ($list['page'] < 1) {
            $list['page'] = 1;
        }
        if ($searchtext == '' && !empty($this->keywords)) {
            $searchtext = $this->keywords;
        }
        $keywords = $client->format_keywords($searchtext);
        $list['list'] = $client->search_experiments($client->format_keywords($keywords), $list['page'] - 1, $this->options['mimetypes']);
        $list['manage'] = 'https://stanford-ilabs.appspot.com/#login-dialog';
        $list['help'] = $CFG->wwwroot . '/repository/ilabs/help/help.htm';
        $list['nologin'] = true;
        $list['norefresh'] = false;
        if (!empty($list['list'])) {
            $list['pages'] = -1; // Means we don't know exactly how many pages there are but we can always jump to the next page.
        } else if ($list['page'] > 1) {
            $list['pages'] = $list['page']; // No images available on this page, this is the last page.
        } else {
            $list['pages'] = 0; // No paging.
        }
        return $list;
    }

    public function logout() {
        return $this->print_login();
    }

    public function supported_returntypes() {
        return (FILE_INTERNAL | FILE_EXTERNAL);
    }

    /**
     * The iLabs plugin supports .html files and url strings
     *
     * @return array
     */
    public function supported_filetypes() {
        return array('text/html', 'text/plain');
    }

    /**
     * Return the source information
     *
     * @param stdClass $url
     * @return string|null
     */
    public function get_file_source_info($url) {
        return $url;
    }

    /**
     * Is this repository accessing private data?
     *
     * @return bool
     */
    public function contains_private_data() {
        return false;
    }
}