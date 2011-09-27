<?php
/**
 * @author     Nick Pope <nick@nickpope.me.uk>
 * @copyright  Copyright © 2010, Nick Pope
 * @license    http://www.apache.org/licenses/LICENSE-2.0  Apache License v2.0
 * @package    Twitter
 */

require_once 'Regex.php';

/**
 * Twitter Validator Class
 *
 * Performs "validation" on tweets.
 *
 * Originally written by {@link http://github.com/mikenz Mike Cochrane}, this
 * is based on code by {@link http://github.com/mzsanford Matt Sanford} and
 * heavily modified by {@link http://github.com/ngnpope Nick Pope}.
 *
 * @author     Nick Pope <nick@nickpope.me.uk>
 * @copyright  Copyright © 2010, Nick Pope
 * @license    http://www.apache.org/licenses/LICENSE-2.0  Apache License v2.0
 * @package    Twitter
 */
class Twitter_Validation extends Twitter_Regex {

  /**
   *
   */
  const MAX_LENGTH = 140;

  /**
   * Provides fluent method chaining.
   *
   * @param  string  $tweet  The tweet to be validated.
   *
   * @see  __construct()
   *
   * @return  Twitter_Validation
   */
  public static function create($tweet) {
    return new self($tweet);
  }

  /**
   * Reads in a tweet to be parsed and validates it.
   *
   * @param  string  $tweet  The tweet to validate.
   */
  public function __construct($tweet) {
      parent::__construct($tweet);
  }

  /**
   *
   */
  public function validateTweet() {
    $length = mb_strlen($this->tweet);
    if (!$this->tweet || !$length) return false;
    if ($length > self::MAX_LENGTH) return false;
    if (preg_match(self::$patterns['invalid_characters'], $this->tweet)) return false;
    return true;
  }

  /**
   *
   */
  public function validateUsername() {
    $length = mb_strlen($this->tweet);
    if (!$this->tweet || !$length) return false;
    $extracted = Twitter_Extractor::create($this->tweet)->extractMentionedUsernames();
    return count($extracted) === 1 && $extracted[0] === substr($this->tweet, 1);
  }

  /**
   *
   */
  public function validateList() {
    $length = mb_strlen($this->tweet);
    if (!$this->tweet || !$length) return false;
    preg_match(self::$patterns['auto_link_usernames_or_lists'], $this->tweet, $matches);
    return isset($matches) && $matches[1] === '' && $matches[4] && !empty($matches[4]);
  }

  /**
   *
   */
  public function validateHashtag() {
    $length = mb_strlen($this->tweet);
    if (!$this->tweet || !$length) return false;
    $extracted = Twitter_Extractor::create($this->tweet)->extractHashtags();
    return count($extracted) === 1 && $extracted[0] === substr($this->tweet, 1);
  }

  /**
   *
   */
  public function validateURL($unicode = true) {
    $length = mb_strlen($this->tweet);
    if (!$this->tweet || !$length) return false;
    preg_match(self::$patterns['validate_url_unencoded'], $this->tweet, $matches);
    $match = array_shift($matches);
    if (!$matches || $match !== $this->tweet) return false;
    list($scheme, $authority, $path, $query, $fragment) = array_pad($matches, 5, '');
    # Check scheme, path, query, fragment:
    if (!self::isValidMatch($scheme, self::$patterns['validate_url_scheme'])
      || !preg_match('/^https?$/i', $scheme)
      || !self::isValidMatch($path, self::$patterns['validate_url_path'])
      || !self::isValidMatch($query, self::$patterns['validate_url_query'], true)
      || !self::isValidMatch($fragment, self::$patterns['validate_url_fragment'], true)) {
      return false;
    }
    # Check authority:
    $authority_pattern = $unicode ? 'validate_url_unicode_authority' : 'validate_url_authority';
    return self::isValidMatch($authority, self::$patterns[$authority_pattern]);
  }

  /**
   *
   */
  protected static function isValidMatch($string, $pattern, $optional = false) {
    $found = preg_match($pattern, $string, $matches);
    if (!$optional) {
      return ($string && $found && $matches[0] === $string);
    } else {
      return !($string && (!$found || $matches[0] !== $string));
    }
  }

}

################################################################################
# vim:et:ft=php:nowrap:sts=2:sw=2:ts=2