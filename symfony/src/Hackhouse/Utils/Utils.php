<?php

namespace Hackhouse\Utils;

use Symfony\Component\Validator\Constraints\DateTime;

class Utils {
    const INT_INFINITY = 2147483647;
    const DOCTRINE_TIMESTAMP_FMT = 'Y-m-d H:i:s';
    const XDEBUG_START_SESSION_KEY = 'XDEBUG_SESSION_START';
    const XDEBUG_START_SESSION_VALUE = 'netbeans-xdebug';

    private static $FAILED_API_LOG = '/failed_calls.log';

    const REGEX_URL_FORMAT = '~^
      (http|https)://                                 # protocol
      ([a-z0-9-]+(:[a-z0-9-]+)?@)?            #username and password
      (
        ([a-z0-9-]+\.)+[a-z]{2,6}             # a domain name
          |                                   #  or
        \d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}    # a IP address
      )
      (:[0-9]+)?                              # a port (optional)
      (/?|/\S+)                               # a /, nothing or a / with something
    $~ix';

    public static function clearDiv() {
        echo "<div style='clear: both;'></div>";
    }

    public static function cleanPhoneNumber($number){
        $number = preg_replace ('/[^\d]/', '', $number);
        $number = self::safeSimpleStr($number);
        return $number;
    }

    public static function writeIfEqual($a, $b, $out) {
        if ($a == $b)
            echo $out;
    }

    public static function getAudioFileLength($path){
         return exec("ecalength -s $path 2>&1");
    }

    public static function utcDate($format, $timestamp){
        $date = new \DateTime("now", new \DateTimeZone("UTC"));
        $date->setTimestamp($timestamp);
        return $date->format($format);
    }

    public static function utcStrtotime($string){
        $date = new \DateTime($string, new \DateTimeZone("UTC"));
        return $date->getTimestamp();
    }

    public static function utcTime(){
        $date = new \DateTime("now", new \DateTimeZone("UTC"));
        return $date->getTimestamp();
    }

    public static function prettyTime($seconds, $fullText = false){
        $script_tz = date_default_timezone_get();
        date_default_timezone_set('UTC');

        $seconds = round($seconds);
        $hours = date('H', $seconds);
        $minutes = date('i', $seconds);
        $seconds = date('s', $seconds);

        if ($fullText){
            $ret = "";

            if ((int)$hours) $ret .= (int)$hours . " hours, ";
            if ((int)$minutes) $ret .= (int)$minutes . " minutes, ";
            if ((int)$seconds)  $ret .= (int)$seconds . " seconds";

            return trim($ret, ", ");
        }
        else {
            return ((int)$hours ? $hours . ":$minutes:$seconds" : (int)$minutes . ":$seconds");
        }
        date_default_timezone_set($script_tz);
    }

    public static function logWarn($message) {
        sfContext::getInstance()->getLogger()->log($message, sfLogger::WARNING);
    }

    public static function logNotice($message) {
        sfContext::getInstance()->getLogger()->log($message, sfLogger::NOTICE);
    }

    public static function logInfo($message) {
        sfContext::getInstance()->getLogger()->log($message, sfLogger::INFO);
    }

    public static function logError($message) {
        sfContext::getInstance()->getLogger()->log($message, sfLogger::ERR);
    }

    public static function log($message, $level) {
        sfContext::getInstance()->getLogger()->log($message, $level);
    }

    public static function handleFatalException(Exception $e, $message = '', $doctrineCon = null, $logger = null, $priority = sfLogger::ERR) {
        if ($doctrineCon != null){
            $doctrineCon->rollback();
        }

        $message = $message . ": {$e->getMessage()}";
        if ($logger != null){
            $logger->log($message, $priority);
            $logger->log($e->getTraceAsString(), $priority);
        }
        else {
            sfContext::getInstance()->getLogger()->log($message, $priority);
            sfContext::getInstance()->getLogger()->log($e->getTraceAsString(), $priority);
        }
    }

    public static function logDebug($message) {
        sfContext::getInstance()->getLogger()->log($message, sfLogger::DEBUG);
    }

    public static function moduleHasTemplate($action, $templateName) {
        return file_exists(sfConfig::get('sf_apps_dir') . '/public/modules/' . $action->getModuleName() . '/templates/' . $templateName . 'Success.php');
    }

    public static function stripExtension($fileName) {
        if (strrpos($fileName, '.') === false)
            return $fileName;
        return substr($fileName, 0, strrpos($fileName, '.'));
    }

    public static function getMyIP(){
        return gethostbyname(gethostname());
    }

    public static function incrementFilename($filename) {
        //get filename without extension
        $filename = explode(".", $filename);

        if (count($filename) > 1) {
            $extension = "." . end($filename);
            array_pop($filename);
            $filename = implode(".", $filename);
        } else if (count($filename == 1)) {
            $extension = "";
            $filename = $filename[0];
        } else {
            throw new \Exception("Filename $filename is an empty string!");
        }

        $numberFormat = "/^(.*)\(([0-9]+\))$/";
        $numberMatches = array();
        if (preg_match($numberFormat, $filename, $numberMatches)) {
            return $numberMatches[1] . "(" . ($numberMatches[2] + 1) . ")$extension";
        } else {
            return $filename . "(1)$extension";
        }
    }

    public static function simpleValidate($value, $format) {
        switch ($format) {
            case 'string': return true;
            case 'int': return is_numeric($value);
        }
        return false;
    }

    public static function extractSubArrayValues($array, $key) {
        $result = array();
        foreach ($array as $el) {
            array_unshift($result, $el[$key]);
        }
        return $result;
    }

    public static function joinNonEmpty($array, $joinStr = ',') {
        $result = '';
        foreach ($array as $str) {
            if (!self::isEmptyStr($str))
                $result .= $str . $joinStr;
        }
        $result = trim($result, $joinStr);
        return $result;
    }

    public static function joinKeyVal($array, $joinStr = '=') {
        $result = array();
        foreach ($array as $key => $val) {
            $result[] = $key . $joinStr . $val;
        }
        return $result;
    }

    public static function truncateStr($text, $length = 100, $ending = '...', $exact = false, $considerHtml = true) {
        if ($considerHtml) {
            // if the plain text is shorter than the maximum length, return the whole text
            if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
                return $text;
            }
            // splits all html-tags to scanable lines
            preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
            $total_length = strlen($ending);
            $open_tags = array();
            $truncate = '';
            foreach ($lines as $line_matchings) {
                // if there is any html-tag in this line, handle it and add it (uncounted) to the output
                if (!empty($line_matchings[1])) {
                    // if it's an "empty element" with or without xhtml-conform closing slash
                    if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
                        // do nothing
                        // if tag is a closing tag
                    } else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
                        // delete tag from $open_tags list
                        $pos = array_search($tag_matchings[1], $open_tags);
                        if ($pos !== false) {
                            unset($open_tags[$pos]);
                        }
                        // if tag is an opening tag
                    } else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
                        // add tag to the beginning of $open_tags list
                        array_unshift($open_tags, strtolower($tag_matchings[1]));
                    }
                    // add html-tag to $truncate'd text
                    $truncate .= $line_matchings[1];
                }
                // calculate the length of the plain text part of the line; handle entities as one character
                $content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
                if ($total_length+$content_length> $length) {
                    // the number of characters which are left
                    $left = $length - $total_length;
                    $entities_length = 0;
                    // search for html entities
                    if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
                        // calculate the real length of all entities in the legal range
                        foreach ($entities[0] as $entity) {
                            if ($entity[1]+1-$entities_length <= $left) {
                                $left--;
                                $entities_length += strlen($entity[0]);
                            } else {
                                // no more characters left
                                break;
                            }
                        }
                    }
                    $truncate .= substr($line_matchings[2], 0, $left+$entities_length);
                    // maximum lenght is reached, so get off the loop
                    break;
                } else {
                    $truncate .= $line_matchings[2];
                    $total_length += $content_length;
                }
                // if the maximum length is reached, get off the loop
                if($total_length>= $length) {
                    break;
                }
            }
        } else {
            if (strlen($text) <= $length) {
                return $text;
            } else {
                $truncate = substr($text, 0, $length - strlen($ending));
            }
        }
        // if the words shouldn't be cut in the middle...
        if (!$exact) {
            // ...search the last occurance of a space...
            $spacepos = strrpos($truncate, ' ');
            if (isset($spacepos)) {
                // ...and cut the text in this position
                $truncate = substr($truncate, 0, $spacepos);
            }
        }
        // add the defined ending to the text
        $truncate .= $ending;
        if($considerHtml) {
            // close all unclosed html-tags
            foreach ($open_tags as $tag) {
                $truncate .= '</' . $tag . '>';
            }
        }
        return $truncate;
    }


    public static function boolToInt($bool) {
        return ($bool ? 1 : 0);
    }

    public static function safeSimpleStr($str, $stripTags = true, $trim = true, $tolower = false) {
        if ($trim)
            $str = trim($str);
        if ($tolower)
            $str = strtolower($str);

        if ($stripTags)
            $str = strip_tags($str);
        else
            $str = htmlspecialchars($str);

        return $str;
    }

    public static function lcfirst($string) {
        $string{0} = strtolower($string{0});
        return $string;
    }

    public static function ucfirst($string) {
        $string{0} = strtoupper($string{0});
        return $string;
    }

    public static function isAssoc($arr) {
        for (reset($arr); is_int(key($arr)); next($arr));
        return !is_null(key($arr));
    }

    /**
        * @static
        * @param array $options The options passed in
        * @param array $keys An array of keys for the resulting options array.  An associative here will be processed as specifying default values.
        * @param array $aliases Aliases for the resulting options array
     */
    public static function optionsArray(array $options, $keys = array(), $aliases = array()){
        $ret = array();
        if (self::isAssoc($keys)){
            foreach ($keys as $key => $default){
                $remoteKey = isset($aliases[$key]) ? $aliases[$key] : $key;
                $ret[$key] = isset($options[$remoteKey]) ? $options[$remoteKey] : $default;
            }
        }
        else {
            foreach ($keys as $key){
                $remoteKey = isset($aliases[$key]) ? $aliases[$key] : $key;
                $ret[$key] = isset($options[$remoteKey]) ? $options[$remoteKey] : null;
            }
        }

        return $ret;
    }

    public static function coerceToArray($val) {
        if (is_array($val))
            return $val;
        else if ($val != null && $val !== '' && !is_array($val))
            return array($val);
        return null;
    }

    public static function coerceToTimestamp($val){
        if ($val instanceof \DateTime){
            return $val->getTimestamp();
        }
        else if (!is_numeric($val)){
            return self::utcStrtotime($val);
        }

        return $val;
    }

    public static function toBoolean($val) {
        if ($val === null || $val === 0 || $val === 'false' || $val === '' || $val === '0' || $val === false)
            return false;
        else
            return true;
    }

    public static function toBooleanString($val) {
        return (self::toBoolean($val) ? 'true' : 'false');
    }

    public static function formatPermalink($v) {
        $v = str_replace(" ", "-", $v);
        $v = preg_replace("/[^a-zA-Z0-9_-]/", "", $v);
        $v = strtolower($v);
        $v = trim($v);
        return $v;
    }

    public static function boolToStr($bool) {
        return ($bool ? 'true' : 'false');
    }

    public static function generateRandomString($length = 8, $numericOnly = false) {

        // start with a blank password
        $password = "";

        // define possible characters
        if ($numericOnly) {
            $possible = "0123456789";
        } else {
            $possible = "0123456789abcdfghjkmnpqrstvwxyz";
        }

        // set up a counter
        $i = 0;

        // add random characters to $password until $length is reached
        while ($i < $length) {

            // pick a random character from the possible ones
            $char = substr($possible, mt_rand(0, strlen($possible) - 1), 1);

            // we don't want this character if it's already in the password
            if (!strstr($password, $char)) {
                $password .= $char;
                $i++;
            }
        }

        // done!
        return $password;
    }

    public static function strToHtmlAscii($string) {
        $result = "";

        if (self::isEmptyStr($string))
            return $result;

        for ($i = 0; $i < strlen($string); $i++) {
            $result .= "&#" . ord(substr($string, $i, 1)) . ";";
        }

        return $result;
    }

    public static function isEmptyStr($str) {
        return strlen(trim($str)) == 0;
    }

    public static function defaultString($str, $default = "") {
        if ($str == null || self::isEmptyStr($str))
            return $default;
        return $str;
    }

    public static function validURL($url) {
        return preg_match(self::REGEX_URL_FORMAT, $url);
    }

    public static function validEmail($email) {
        $isValid = true;
        $atIndex = strrpos($email, "@");
        if (is_bool($atIndex) && !$atIndex) {
            $isValid = false;
        } else {
            $domain = substr($email, $atIndex + 1);
            $local = substr($email, 0, $atIndex);
            $localLen = strlen($local);
            $domainLen = strlen($domain);
            if ($localLen < 1 || $localLen > 64) {
                // local part length exceeded
                $isValid = false;
            } else if ($domainLen < 1 || $domainLen > 255) {
                // domain part length exceeded
                $isValid = false;
            } else if ($local[0] == '.' || $local[$localLen - 1] == '.') {
                // local part starts or ends with '.'
                $isValid = false;
            } else if (preg_match('/\\.\\./', $local)) {
                // local part has two consecutive dots
                $isValid = false;
            } else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
                // character not valid in domain part
                $isValid = false;
            } else if (preg_match('/\\.\\./', $domain)) {
                // domain part has two consecutive dots
                $isValid = false;
            } else if
            (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                            str_replace("\\\\", "", $local))) {
                // character not valid in local part unless
                // local part is quoted
                if (!preg_match('/^"(\\\\"|[^"])+"$/',
                                str_replace("\\\\", "", $local))) {
                    $isValid = false;
                }
            }
            if ($isValid && !(checkdnsrr($domain, "MX") || checkdnsrr($domain, "A"))) {
                // domain not found in DNS
                $isValid = false;
            }
        }
        return $isValid;
    }

    public static function validUsername($username) {
        return preg_match('/^[A-Za-z0-9]+$/', $username);
    }

    public static function validPermalink($permalink) {
        return preg_match("/^[A-Za-z0-9-_]+$/", $permalink);
    }

    public static function strToNull($str) {
        if ($str == null || $str === '')
            return null;
        else
            return $str;
    }

    public static function urlify($str) {
        if (!self::startsWith($str, 'http://', false))
            return 'http://' . $str;
        else
            return $str;
    }

    public static function startsWith($haystack, $needle, $case=true) {
        if ($case)
            return(substr($haystack, 0, strlen($needle)) === $needle);

        return(strtolower(substr($haystack, 0, strlen($needle))) === strtolower($needle));
    }

    public static function endsWith($haystack, $needle, $case=true) {
        if ($case)
            return (substr($haystack, strlen($haystack) - strlen($needle)) === $needle);


        return (strtolower(substr($haystack, strlen($haystack) - strlen($needle))) === strtolower($needle));
    }

    public static function generateSlug($str) {
        $str = strtolower(trim($str));
        $str = preg_replace('/[^a-z0-9-]/', '-', $str);
        $str = preg_replace('/-+/', "-", $str);
        return $str;
    }

    public static function generateQueryString(sfWebRequest $request, $possibleKeys) {
        $result = "?";
        foreach ($possibleKeys as $key) {
            if ($request->hasParameter($key)) {
                $result .= $key . '=' . $request->getParameter($key) . '&';
            }
        }

        return trim($result, '&');
    }

    public static function ajaxFormErrorResponse($form) {
        $response = array(
            'success' => false,
            'first' => '',
            'errors' => array()
        );

        $errors = $form->getErrorSchema()->getErrors();
        $first = true;
        foreach ($errors as $key => $error) {
            if ($first) {
                $response['first'] = $error->__toString();
                $first = false;
            }
            $response['errors'][$key] = $error->__toString();
        }

        return json_encode($response);
    }

    public static function javascriptString($str) {
        return "'" . str_replace("'", "\\'", $str) . "'";
    }

    public static function friendlyTimestamp($timestamp) {
        $timestampDay = date('Y-m-d', strtotime($timestamp));

        $dayWord = null;
        if ($timestampDay == date('Y-m-d')) {
            $dayWord = 'Today';
        }
        //less than 48 hours ago
        else if (time() - strtotime($timestampDay) < 172800) {
            $dayWord = 'Yesterday';
        }

        if ($dayWord)
            return $dayWord . ", " . date("g:i a", strtotime($timestamp));
        else
            return date("n/d/Y g:i a", strtotime($timestamp));
    }

    public static function toMysqlDate($date) {
        return date('Y-m-d', strtotime($date));
    }

    public static function getFirst($structure) {
        if (is_array($structure) && count($structure) > 0) {
            return $structure[0];
        }
        else
            return $structure;
    }

    public static function smartDate($date) {
        $sendTime = strtotime($date);
        $timeElapsed = time() - $sendTime;

        $dayInSec = 60 * 60 * 24;
        //one day
        if ($timeElapsed < $dayInSec) {
            return date("g:i a", $sendTime);
        } else if ($timeElapsed < ($dayInSec * 365)) {
            return date("M d", $sendTime);
        }
        else
            return date("m/d/Y", $sendTime);
    }

    public static function allValuesEqual($array) {
        if (!is_array($array)) {
            return true;
        } else if (count($array) == 0) {
            return true;
        } else {
            $first = $array[0];
            foreach ($array as $val) {
                if ($val !== $first)
                    return false;
            }
            return true;
        }
    }

    public static function allNull() {
        $args = func_get_args();
        foreach ($args as $arg) {
            if ($arg !== null)
                return false;
        }
        return true;
    }

    public static function allNullOrZero() {
        $args = func_get_args();
        foreach ($args as $arg) {
            if ($arg !== null && intval($arg) !== 0)
                return false;
        }
        return true;
    }

    public static function formatCents($value, $withDollar = false, $withColor = false, $colorThreshold = 0) {
        $ret = number_format($value /= 100, 2);

        if ($withDollar) $ret = "$$ret";
        if ($withColor){
            if ($value < $colorThreshold){
                $color = "red";
            }
            else {
                $color = "black";
            }
            $ret = "<font color='$color'>$ret</font>";
        }
        return $ret;
    }

    public static function addParamsToUrl($link, $params) {
        $link = $link . (strstr($link, '?') ? '&' : "?") . self::joinNonEmpty(self::joinKeyVal($params, '='), '&');
        return $link;
    }

    public static function makeRequestSecure($action, $request) {
        if (!$request->isSecure() && sfConfig::get('phaxio_hasSSL')) {
            $action->redirect('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        }
    }

    public static function getMonthDropdownChoices() {
        $monthChoices = array();
        for ($i = 1; $i <= 12; $i++) {
            $monthChoices[$i . ''] = date('F', strtotime("$i/1/2010"));
        }
        return $monthChoices;
    }

    /*
      Copyright (c) 2008, reusablecode.blogspot.com; some rights reserved.

      This work is licensed under the Creative Commons Attribution License. To view
      a copy of this license, visit http://creativecommons.org/licenses/by/3.0/ or
      send a letter to Creative Commons, 559 Nathan Abbott Way, Stanford, California
      94305, USA.
     */

    // Luhn (mod 10) algorithm

    public static function luhn($input) {
        $sum = 0;
        $odd = strlen($input) % 2;

        // Remove any non-numeric characters.
        if (!is_numeric($input)) {
            eregi_replace("\D", "", $input);
        }

        // Calculate sum of digits.
        for ($i = 0; $i < strlen($input); $i++) {
            $sum += $odd ? $input[$i] : (($input[$i] * 2 > 9) ? $input[$i] * 2 - 9 : $input[$i] * 2);
            $odd = !$odd;
        }

        // Check validity.
        return ($sum % 10 == 0) ? true : false;
    }

    public static function copyDoctrineFields(Doctrine_Record $a, Doctrine_Record $b, array $fields) {
        foreach ($fields as $field) {
            $b->set($field, $a->get($field));
        }
    }

    public static $usStates = array('AL' => "Alabama",
        'AK' => "Alaska",
        'AZ' => "Arizona",
        'AR' => "Arkansas",
        'CA' => "California",
        'CO' => "Colorado",
        'CT' => "Connecticut",
        'DE' => "Delaware",
        'DC' => "District Of Columbia",
        'FL' => "Florida",
        'GA' => "Georgia",
        'HI' => "Hawaii",
        'ID' => "Idaho",
        'IL' => "Illinois",
        'IN' => "Indiana",
        'IA' => "Iowa",
        'KS' => "Kansas",
        'KY' => "Kentucky",
        'LA' => "Louisiana",
        'ME' => "Maine",
        'MD' => "Maryland",
        'MA' => "Massachusetts",
        'MI' => "Michigan",
        'MN' => "Minnesota",
        'MS' => "Mississippi",
        'MO' => "Missouri",
        'MT' => "Montana",
        'NE' => "Nebraska",
        'NV' => "Nevada",
        'NH' => "New Hampshire",
        'NJ' => "New Jersey",
        'NM' => "New Mexico",
        'NY' => "New York",
        'NC' => "North Carolina",
        'ND' => "North Dakota",
        'OH' => "Ohio",
        'OK' => "Oklahoma",
        'OR' => "Oregon",
        'PA' => "Pennsylvania",
        'RI' => "Rhode Island",
        'SC' => "South Carolina",
        'SD' => "South Dakota",
        'TN' => "Tennessee",
        'TX' => "Texas",
        'UT' => "Utah",
        'VT' => "Vermont",
        'VA' => "Virginia",
        'WA' => "Washington",
        'WV' => "West Virginia",
        'WI' => "Wisconsin",
        'WY' => "Wyoming");

    public static $usStatesAbbrev = array('AL' => "AL",
        'AK' => "AK",
        'AZ' => "AZ",
        'AR' => "AR",
        'CA' => "CA",
        'CO' => "CO",
        'CT' => "CT",
        'DE' => "DE",
        'DC' => "DC",
        'FL' => "FL",
        'GA' => "GA",
        'HI' => "HI",
        'ID' => "ID",
        'IL' => "IL",
        'IN' => "IN",
        'IA' => "IA",
        'KS' => "KS",
        'KY' => "KY",
        'LA' => "LA",
        'ME' => "ME",
        'MD' => "MD",
        'MA' => "MA",
        'MI' => "MI",
        'MN' => "MN",
        'MS' => "MS",
        'MO' => "MO",
        'MT' => "MT",
        'NE' => "NE",
        'NV' => "NV",
        'NH' => "NH",
        'NJ' => "NJ",
        'NM' => "NM",
        'NY' => "NY",
        'NC' => "NC",
        'ND' => "ND",
        'OH' => "OH",
        'OK' => "OK",
        'OR' => "OR",
        'PA' => "PA",
        'RI' => "RI",
        'SC' => "SC",
        'SD' => "SD",
        'TN' => "TN",
        'TX' => "TX",
        'UT' => "UT",
        'VT' => "VT",
        'VA' => "VA",
        'WA' => "WA",
        'WV' => "WV",
        'WI' => "WI",
        'WY' => "WY");

    public static $countries = array('United States','Afghanistan','Åland Islands','Albania','Algeria','American Samoa','Andorra','Angola','Anguilla','Antarctica',
        'Antigua And Barbuda','Argentina','Armenia','Aruba','Australia','Austria','Azerbaijan','Bahamas',
        'Bahrain','Bangladesh','Barbados','Belarus','Belgium','Belize','Benin','Bermuda','Bhutan','Bolivia',
        'Bosnia And Herzegovina','Botswana','Bouvet Island','Brazil','British Indian Ocean Territory',
        'Brunei Darussalam','Bulgaria','Burkina Faso','Burundi','Cambodia','Cameroon',
        'Canada','Cape Verde','Cayman Islands','Central African Republic','Chad','Chile',
        'China','Christmas Island','Cocos (Keeling) Islands','Colombia','Comoros','Congo',
        'Congo, The Democratic Republic Of The','Cook Islands','Costa Rica','Cote Divoire',
        'Croatia','Cuba','Cyprus','Czech Republic','Denmark','Djibouti','Dominica','Dominican Republic',
        'Ecuador','Egypt','El Salvador','Equatorial Guinea','Eritrea','Estonia','Ethiopia','Falkland Islands (Malvinas)',
        'Faroe Islands','Fiji','Finland','France','French Guiana','French Polynesia','French Southern Territories',
        'Gabon','Gambia','Georgia','Germany','Ghana','Gibraltar','Greece','Greenland','Grenada','Guadeloupe',
        'Guam','Guatemala','Guernsey','Guinea','Guinea-bissau','Guyana','Haiti','Heard Island And Mcdonald Islands',
        'Holy See (Vatican City State)','Honduras','Hong Kong','Hungary','Iceland',
        'India','Indonesia','Iran, Islamic Republic Of','Iraq','Ireland','Isle Of Man','Israel',
        'Italy','Jamaica','Japan','Jersey','Jordan','Kazakhstan','Kenya','Kiribati',
        'Korea, Democratic Peoples Republic Of','Korea, Republic Of','Kuwait','Kyrgyzstan',
        'Lao Peoples Democratic Republic','Latvia','Lebanon','Lesotho','Liberia',
        'Libyan Arab Jamahiriya','Liechtenstein','Lithuania','Luxembourg','Macao',
        'Macedonia, The Former Yugoslav Republic Of','Madagascar','Malawi','Malaysia','Maldives',
        'Mali','Malta','Marshall Islands','Martinique','Mauritania','Mauritius','Mayotte','Mexico',
        'Micronesia, Federated States Of','Moldova, Republic Of','Monaco','Mongolia','Montenegro','Montserrat',
        'Morocco','Mozambique','Myanmar','Namibia','Nauru','Nepal','Netherlands','Netherlands Antilles',
        'New Caledonia','New Zealand','Nicaragua','Niger','Nigeria','Niue','Norfolk Island','Northern Mariana Islands',
        'Norway','Oman','Pakistan','Palau','Palestinian Territory, Occupied','Panama','Papua New Guinea','Paraguay',
        'Peru','Philippines','Pitcairn','Poland','Portugal','Puerto Rico','Qatar','Reunion','Romania',
        'Russian Federation','Rwanda','Saint Helena',
        'Saint Kitts And Nevis','Saint Lucia','Saint Pierre And Miquelon',
        'Saint Vincent And The Grenadines','Samoa','San Marino','Sao Tome And Principe',
        'Saudi Arabia','Senegal','Serbia','Seychelles','Sierra Leone','Singapore','Slovakia','Slovenia',
        'Solomon Islands','Somalia','South Africa','South Georgia And The South Sandwich Islands','Spain',
        'Sri Lanka','Sudan','Suriname','Svalbard And Jan Mayen','Swaziland','Sweden','Switzerland','Syrian Arab Republic',
        'Taiwan, Province Of China','Tajikistan','Tanzania, United Republic Of','Thailand','Timor-leste','Togo',
        'Tokelau','Tonga','Trinidad And Tobago','Tunisia','Turkey','Turkmenistan','Turks And Caicos Islands','Tuvalu',
        'Uganda','Ukraine','United Arab Emirates','United Kingdom','Uruguay','Uzbekistan','Vanuatu','Venezuela',
        'Viet Nam','Virgin Islands, British','Wallis And Futuna','Western Sahara','Yemen','Zambia','Zimbabwe');


    public static function prettyPrintJson($json, $options = array()){
         $tokens = preg_split('|([\{\}\]\[,])|', $json, -1, PREG_SPLIT_DELIM_CAPTURE);
         $result = "";
         $indent = 0;

         $format= "html";
         $ind = "\t";

         if(isset($options['format'])) {
             $format = $options['format'];
         }

         switch ($format):
             case 'html':
                 $line_break = "<br />";
                 $ind = "&nbsp;&nbsp;&nbsp;&nbsp;";
                 break;
             default:
             case 'txt':
                 $line_break = "\n";
                 $ind = "\t";
                 break;
         endswitch;

         //override the defined indent setting with the supplied option
         if(isset($options['indent'])) {
             $ind = $options['indent'];
         }

         foreach($tokens as $token) {
             if($token == "") continue;

             $prefix = str_repeat($ind, $indent);
             if($token == "{" || $token == "[") {
                 $indent++;
                 if($result != "" && $result[strlen($result)-1] == $line_break) {
                     $result .= $prefix;
                 }
                 $result .= "$token$line_break";
             } else if($token == "}" || $token == "]") {
                 $indent--;
                 $prefix = str_repeat($ind, $indent);
                 $result .= "$line_break$prefix$token";
             } else if($token == ",") {
                 $result .= "$token$line_break" ;
             } else {
                 $result .= $prefix.$token;
             }
         }
         return $result;
    }

    public static function curlGet($host, $params = array(), $timeout = 30, $headers = null){
        if (isset($_REQUEST[self::XDEBUG_START_SESSION_KEY])){
            $params[self::XDEBUG_START_SESSION_KEY] = self::XDEBUG_START_SESSION_VALUE;
        }

        $handle = curl_init($host);
        curl_setopt($handle, CURLOPT_POST, false);
        curl_setopt($handle, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        return curl_exec($handle);
    }

    public static function curlPost($host, $params = array(), $timeout = 30, $headers = null){
        if ($_REQUEST[self::XDEBUG_START_SESSION_KEY]){
            $params[self::XDEBUG_START_SESSION_KEY] = self::XDEBUG_START_SESSION_VALUE;
        }

        $handle = curl_init($host);
        curl_setopt($handle, CURLOPT_POST, true);

        curl_setopt($handle, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        self::curl_setopt_custom_postfields($handle, $params, $headers);
        return curl_exec($handle);
    }

    public static function curl_setopt_custom_postfields($ch, $postfields, $headers = null) {
        $algos = hash_algos();
        $hashAlgo = null;
        foreach (array('sha1', 'md5') as $preferred) {
            if (in_array($preferred, $algos)) {
                $hashAlgo = $preferred;
                break;
            }
        }
        if ($hashAlgo === null) {
            list($hashAlgo) = $algos;
        }
        $boundary =
                '----------------------------' .
                substr(hash($hashAlgo, 'cURL-php-multiple-value-same-key-support' . microtime()), 0, 12);

        $body = array();
        $crlf = "\r\n";
        $fields = array();
        foreach ($postfields as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    $fields[] = array($key, $v);
                }
            } else {
                $fields[] = array($key, $value);
            }
        }
        foreach ($fields as $field) {
            list($key, $value) = $field;
            if (strpos($value, '@') === 0) {
                preg_match('/^@(.*?)$/', $value, $matches);
                list($dummy, $filename) = $matches;
                $body[] = '--' . $boundary;
                $body[] = 'Content-Disposition: form-data; name="' . $key . '"; filename="' . basename($filename) . '"';
                $body[] = 'Content-Type: application/octet-stream';
                $body[] = '';
                $body[] = file_get_contents($filename);
            } else {
                $body[] = '--' . $boundary;
                $body[] = 'Content-Disposition: form-data; name="' . $key . '"';
                $body[] = '';
                $body[] = $value;
            }
        }
        $body[] = '--' . $boundary . '--';
        $body[] = '';
        $contentType = 'multipart/form-data; boundary=' . $boundary;
        $content = join($crlf, $body);
        $contentLength = strlen($content);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Length: ' . $contentLength,
            'Expect: 100-continue',
            'Content-Type: ' . $contentType,
        ));

        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
    }

}