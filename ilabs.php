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
 * This plugin is used to access experiments in iLabs.
 *
 * @package    repository_ilabs
 * @copyright  2013 Luis de la Torre
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class ilabs
 *
 * @package    repository_ilabs
 * @copyright  2013 Luis de la Torre
 * @author     Luis de la Torre
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ilabs {
    /**
     * @var string
     */
    public $ilabs_url = "https://ilabs-luis-2018053000-dot-stanford-ilabs.appspot.com";

    /**
     * @var string
     */
    private $list_all = "/listingExternal";

    /**
     * @var int
     */
    private $thumbs_per_page = 12;

    /**
     * Load the json string served by the iLabs repository
     *
     * @param string $url
     * @param mixed $choice
     * @return stdClass $xml
     */
    public function load_json_response($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($result);
        return $json;
    }

    /**
     * Keywords for searching iLabs experiments
     *
     * @param string $keywords
     * @return mixed|string $keywords
     */
    public function format_keywords($keywords) {
        $keywords = trim($keywords);
        if (($keywords == '') || ($keywords == 'Search') || (strtoupper($keywords) == 'ALL') || ($keywords == '*')) {
            $keywords = '';
        } else {
            // Making possible conjunctive boolean searches (a&b&...).
            $keywords = preg_replace('/\s+/', '+', $keywords);
        }

        return $keywords;
    } // End of function format_keywords

    /**
     * Searches experiments using the specified keywords
     *
     * @param string $keywords
     * @param int $page
     * @return array $filelist
     */
    public function search_experiments($keywords, $page) {
        // Get records from iLabs that fulfill the keywords.
        $experiments = $this->load_json_response($this->ilabs_url . $this->list_all .
            '?exp_search=' . $keywords);

        $filelist = array();
        if (count((array)$experiments) > $page * $this->thumbs_per_page) {
            foreach ($experiments as $experiment) {
                $item = array();
                $item['author'] = $experiment->authors;
                $item['date'] = strtotime($experiment->date);
                $item['thumbnail'] = $this->ilabs_url . $experiment->picture;
                $item['license'] = '';
                $item['title'] = $experiment->name . '.html';
                $item['source'] = $this->ilabs_url . $experiment->url;
                $item['shorttitle'] = $experiment->name . ': ' . $experiment->abstract;
                $item['size'] = '';
                $filelist[] = $item;
            }
        }

        return $filelist;
    } // End of function search_experiments

    public function logout() {
        return $this->print_login();
    }

} // End of class ilabs.