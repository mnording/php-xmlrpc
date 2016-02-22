<?php
/**
 * Copyright 2016 Klarna AB.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Klarna\XMLRPC;

if (!defined('ENT_HTML401')) {
    define('ENT_HTML401', 0);
}

/**
 * This API provides a way to integrate with Klarna's services over the
 * XMLRPC protocol.
 *
 * All strings inputted need to be encoded with ISO-8859-1.<br>
 * In addition you need to decode HTML entities, if they exist.<br>
 */
class Klarna
{
    /**
     * Klarna PHP API version identifier.
     *
     * @var string
     */
    protected $VERSION = 'php:api:5.0.0';

    /**
     * Klarna protocol identifier.
     *
     * @var string
     */
    protected $PROTO = '4.1';

    /**
     * Constants used with LIVE mode for the communications with Klarna.
     *
     * @var int
     */
    const LIVE = 0;

    /**
     * URL/Address to the live Klarna Online server.
     * Port used is 443 for SSL and 80 without.
     *
     * @var string
     */
    private static $_live_addr = 'payment.klarna.com';

    /**
     * Constants used with BETA mode for the communications with Klarna.
     *
     * @var int
     */
    const BETA = 1;

    /**
     * URL/Address to the beta test Klarna Online server.
     * Port used is 443 for SSL and 80 without.
     *
     * @var string
     */
    private static $_beta_addr = 'payment.testdrive.klarna.com';

    /**
     * Indicates whether the communications is over SSL or not.
     *
     * @var bool
     */
    protected $ssl = false;

    /**
     * An object of xmlrpc_client, used to communicate with Klarna.
     *
     * @link http://phpxmlrpc.sourceforge.net/
     *
     * @var xmlrpc_client
     */
    protected $xmlrpc;

    /**
     * Which server the Klarna API is using, LIVE or BETA (TESTING).
     *
     * @see Klarna::LIVE
     * @see Klarna::BETA
     *
     * @var int
     */
    protected $mode;

    /**
     * Associative array holding url information.
     *
     * @var array
     */
    private $_url;

    /**
     * The estore's identifier received from Klarna.
     *
     * @var int
     */
    private $_eid;

    /**
     * The estore's shared secret received from Klarna.
     *
     * <b>Note</b>:<br>
     * DO NOT SHARE THIS WITH ANYONE!
     *
     * @var string
     */
    private $_secret;

    /**
     * Country constant.
     *
     * @see Country
     *
     * @var int
     */
    private $_country;

    /**
     * Currency constant.
     *
     * @see Currency
     *
     * @var int
     */
    private $_currency;

    /**
     * Language constant.
     *
     * @see Language
     *
     * @var int
     */
    private $_language;

    /**
     * An array of articles for the current order.
     *
     * @var array
     */
    protected $goodsList;

    /**
     * An array of article numbers and quantity.
     *
     * @var array
     */
    protected $artNos;

    /**
     * An Address object containing the billing address.
     *
     * @var Address
     */
    protected $billing;

    /**
     * An Address object containing the shipping address.
     *
     * @var Address
     */
    protected $shipping;

    /**
     * Estore's user(name) or identifier.
     * Only used in {@link Klarna::addTransaction()}.
     *
     * @var string
     */
    protected $estoreUser = '';

    /**
     * External order numbers from other systems.
     *
     * @var string
     */
    protected $orderid = array('', '');

    /**
     * Reference (person) parameter.
     *
     * @var string
     */
    protected $reference = '';

    /**
     * Reference code parameter.
     *
     * @var string
     */
    protected $reference_code = '';

    /**
     * An array of named extra info.
     *
     * @var array
     */
    protected $extraInfo = array();

    /**
     * An array of named bank info.
     *
     * @var array
     */
    protected $bankInfo = array();

    /**
     * An array of named income expense info.
     *
     * @var array
     */
    protected $incomeInfo = array();

    /**
     * An array of named shipment info.
     *
     * @var array
     */
    protected $shipInfo = array();

    /**
     * An array of named travel info.
     *
     * @ignore Do not show this in PHPDoc.
     *
     * @var array
     */
    protected $travelInfo = array();

    /**
     * An array of named activate info.
     *
     * @ignore
     *
     * @var array
     */
    protected $activateInfo = array();

    /**
     * An array of named session id's.<br>
     * E.g. "dev_id_1" => ...<br>.
     *
     * @var array
     */
    protected $sid = array();

    /**
     * A comment sent in the XMLRPC communications.
     * This is resetted using clear().
     *
     * @var string
     */
    protected $comment = '';

    /**
     * Flag to indicate if the API should output verbose
     * debugging information.
     *
     * @var bool
     */
    public static $debug = false;

    /**
     * Turns on the internal XMLRPC debugging.
     *
     * @var bool
     */
    public static $xmlrpcDebug = false;

    /**
     * If this is set to true, XMLRPC invocation is disabled.
     *
     * @var bool
     */
    public static $disableXMLRPC = false;

    /**
     * If the estore is using a proxy which populates the clients IP to
     * x_forwarded_for
     * then and only then should this be set to true.
     *
     * <b>Note</b>:<br>
     * USE WITH CARE!
     *
     * @var bool
     */
    public static $x_forwarded_for = false;

    /**
     * Array of HTML entities, used to create numeric htmlentities.
     *
     * @ignore Do not show this in PHPDoc.
     *
     * @var array
     */
    protected static $htmlentities = false;

    /**
     * Populated with possible proxy information.
     * A comma separated list of IP addresses.
     *
     * @var string
     */
    private $_x_fwd;

    /**
     * PClass list.
     *
     * @ignore Do not show this in PHPDoc.
     *
     * @var PClass[]
     */
    protected $pclasses;

    /**
     * \ArrayAccess instance.
     *
     * @ignore Do not show this in PHPDoc.
     *
     * @var \ArrayAccess
     */
    protected $config;

    /**
     * Client IP.
     *
     * @var string
     */
    protected $clientIP;

    /**
     * Empty constructor, because sometimes it's needed.
     */
    public function __construct()
    {
    }

    /**
     * Checks if the config has fields described in argument.<br>
     * Missing field(s) is in the exception message.
     *
     * To check that the config has eid and secret:<br>
     * <code>
     * try {
     *     $this->hasFields('eid', 'secret');
     * }
     * catch(\Exception $e) {
     *     echo "Missing fields: " . $e->getMessage();
     * }
     * </code>
     *
     * @throws Exception
     */
    protected function hasFields()
    {
        $missingFields = array();
        $args = func_get_args();
        foreach ($args as $field) {
            if (!isset($this->config[$field])) {
                $missingFields[] = $field;
            }
        }
        if (count($missingFields) > 0) {
            throw new Exception\ConfigFieldMissingException(
                implode(', ', $missingFields)
            );
        }
    }

    /**
     * Initializes the Klarna object accordingly to the set config object.
     *
     * @throws Exception\KlarnaException
     */
    protected function init()
    {
        $this->hasFields('eid', 'secret', 'mode');

        if (!is_int($this->config['eid'])) {
            $this->config['eid'] = intval($this->config['eid']);
        }

        if ($this->config['eid'] <= 0) {
            throw new Exception\ConfigFieldMissingException('eid');
        }

        if (!is_string($this->config['secret'])) {
            $this->config['secret'] = strval($this->config['secret']);
        }

        if (strlen($this->config['secret']) == 0) {
            throw new Exception\ConfigFieldMissingException('secret');
        }

        //Set the shop id and secret.
        $this->_eid = $this->config['eid'];
        $this->_secret = $this->config['secret'];

        //Set the country specific attributes.
        try {
            $this->hasFields('country', 'language', 'currency');

            //If hasFields doesn't throw exception we can set them all.
            $this->setCountry($this->config['country']);
            $this->setLanguage($this->config['language']);
            $this->setCurrency($this->config['currency']);
        } catch (\Exception $e) {
            //fields missing for country, language or currency
            $this->_country = $this->_language = $this->_currency = null;
        }

        //Set addr and port according to mode.
        $this->mode = (int) $this->config['mode'];

        $this->_url = array();

        // If a custom url has been added to the config, use that as xmlrpc
        // recipient.
        if (isset($this->config['url'])) {
            $this->_url = parse_url($this->config['url']);
            if ($this->_url === false) {
                $message = "Configuration value 'url' could not be parsed. ".
                    "(Was: '{$this->config['url']}')";
                self::printDebug(__METHOD__, $message);
                throw new InvalidArgumentException($message);
            }
        } else {
            $this->_url['scheme'] = 'https';

            if ($this->mode === self::LIVE) {
                $this->_url['host'] = self::$_live_addr;
            } else {
                $this->_url['host'] = self::$_beta_addr;
            }

            if (isset($this->config['ssl'])
                && (bool) $this->config['ssl'] === false
            ) {
                $this->_url['scheme'] = 'http';
            }
        }

        // If no port has been specified, deduce from url scheme
        if (!array_key_exists('port', $this->_url)) {
            if ($this->_url['scheme'] === 'https') {
                $this->_url['port'] = 443;
            } else {
                $this->_url['port'] = 80;
            }
        }

        try {
            $this->hasFields('xmlrpcDebug');
            self::$xmlrpcDebug = $this->config['xmlrpcDebug'];
        } catch (\Exception $e) {
            //No 'xmlrpcDebug' field ignore it...
        }

        try {
            $this->hasFields('debug');
            self::$debug = $this->config['debug'];
        } catch (\Exception $e) {
            //No 'debug' field ignore it...
        }

        // Default path to '/' if not set.
        if (!array_key_exists('path', $this->_url)) {
            $this->_url['path'] = '/';
        }

        $this->xmlrpc = new \xmlrpc_client(
            $this->_url['path'],
            $this->_url['host'],
            $this->_url['port'],
            $this->_url['scheme']
        );

        $this->xmlrpc->setSSLVerifyHost(2);

        $this->xmlrpc->request_charset_encoding = 'ISO-8859-1';
    }

    /**
     * Method of ease for setting common config fields.
     *
     * <b>Note</b>:<br>
     * This disables the config file storage.<br>
     *
     * @param int    $eid      Merchant ID/EID
     * @param string $secret   Secret key/Shared key
     * @param int    $country  {@link Country}
     * @param int    $language {@link Language}
     * @param int    $currency {@link Currency}
     * @param int    $mode     {@link Klarna::LIVE} or {@link Klarna::BETA}
     * @param bool   $ssl      Whether HTTPS (HTTP over SSL) or HTTP is used.
     *
     * @see Klarna::setConfig()
     * @see Config
     *
     * @throws Exception\KlarnaException
     */
    public function config(
        $eid,
        $secret,
        $country,
        $language,
        $currency,
        $mode = self::LIVE,
        $ssl = true
    ) {
        try {
            Config::$store = false;
            $this->config = new Config(null);

            $this->config['eid'] = $eid;
            $this->config['secret'] = $secret;
            $this->config['country'] = $country;
            $this->config['language'] = $language;
            $this->config['currency'] = $currency;
            $this->config['mode'] = $mode;
            $this->config['ssl'] = $ssl;

            $this->init();
        } catch (\Exception $e) {
            $this->config = null;
            throw new Exception\KlarnaException(
                $e->getMessage(),
                $e->getCode()
            );
        }
    }

    /**
     * Sets and initializes this Klarna object using the supplied config object.
     *
     * @param Config $config Config object.
     *
     * @see Config
     *
     * @throws Exception\KlarnaException
     */
    public function setConfig(&$config)
    {
        $this->_checkConfig($config);

        $this->config = $config;
        $this->init();
    }

    /**
     * Get the complete locale (country, language, currency) to use for the
     * values passed, or the configured value if passing null.
     *
     * @param mixed $country  country  constant or code
     * @param mixed $language language constant or code
     * @param mixed $currency currency constant or code
     *
     * @throws Exception\KlarnaException
     *
     * @return array
     */
    public function getLocale(
        $country = null,
        $language = null,
        $currency = null
    ) {
        $locale = array(
            'country' => null,
            'language' => null,
            'currency' => null,
        );

        if ($country === null) {
            // Use the configured country / language / currency
            $locale['country'] = $this->_country;
            if ($this->_language !== null) {
                $locale['language'] = $this->_language;
            }

            if ($this->_currency !== null) {
                $locale['currency'] = $this->_currency;
            }
        } else {
            // Use the given country / language / currency
            if (!is_numeric($country)) {
                $country = Country::fromCode($country);
            }
            $locale['country'] = intval($country);

            if ($language !== null) {
                if (!is_numeric($language)) {
                    $language = Language::fromCode($language);
                }
                $locale['language'] = intval($language);
            }

            if ($currency !== null) {
                if (!is_numeric($currency)) {
                    $currency = Currency::fromCode($currency);
                }
                $locale['currency'] = intval($currency);
            }
        }

        // Complete partial structure with defaults
        if ($locale['currency'] === null) {
            $locale['currency'] = $this->getCurrencyForCountry(
                $locale['country']
            );
        }

        if ($locale['language'] === null) {
            $locale['language'] = $this->getLanguageForCountry(
                $locale['country']
            );
        }

        $this->_checkCountry($locale['country']);
        $this->_checkCurrency($locale['currency']);
        $this->_checkLanguage($locale['language']);

        return $locale;
    }

    /**
     * Sets the country used.
     *
     * <b>Note</b>:<br>
     * If you input 'dk', 'fi', 'de', 'nl', 'no' or 'se', <br>
     * then currency and language will be set to mirror that country.<br>
     *
     * @param string|int $country {@link Country}
     *
     * @see Country
     *
     * @throws Exception\KlarnaException
     */
    public function setCountry($country)
    {
        if (!is_numeric($country)
            && (strlen($country) == 2 || strlen($country) == 3)
        ) {
            $country = Country::fromCode($country);
        }
        $this->_checkCountry($country);
        $this->_country = $country;
    }

    /**
     * Returns the country code for the set country constant.
     *
     * @param int $country {@link Country Country} constant.
     *
     * @return string Two letter code, e.g. "se", "no", etc.
     */
    public function getCountryCode($country = null)
    {
        if ($country === null) {
            $country = $this->_country;
        }

        $code = Country::getCode($country);

        return (string) $code;
    }

    /**
     * Returns the {@link Country country} constant from the country code.
     *
     * @param string $code Two letter code, e.g. "se", "no", etc.
     *
     * @throws Exception\KlarnaException
     *
     * @return int {@link Country Country} constant.
     */
    public static function getCountryForCode($code)
    {
        $country = Country::fromCode($code);
        if ($country === null) {
            throw new Exception\UnknownCountryException($code);
        }

        return $country;
    }

    /**
     * Returns the country constant.
     *
     * @return int {@link Country}
     */
    public function getCountry()
    {
        return $this->_country;
    }

    /**
     * Sets the language used.
     *
     * <b>Note</b>:<br>
     * You can use the two letter language code instead of the constant.<br>
     * E.g. 'da' instead of using {@link Language::DA}.<br>
     *
     * @param string|int $language {@link Language}
     *
     * @see Language
     *
     * @throws Exception\KlarnaException
     */
    public function setLanguage($language)
    {
        if (!is_numeric($language) && strlen($language) == 2) {
            $this->setLanguage(self::getLanguageForCode($language));
        } else {
            $this->_checkLanguage($language);
            $this->_language = $language;
        }
    }

    /**
     * Returns the language code for the set language constant.
     *
     * @param int $language {@link Language Language} constant.
     *
     * @return string Two letter code, e.g. "da", "de", etc.
     */
    public function getLanguageCode($language = null)
    {
        if ($language === null) {
            $language = $this->_language;
        }
        $code = Language::getCode($language);

        return (string) $code;
    }

    /**
     * Returns the {@link Language language} constant from the language code.
     *
     * @param string $code Two letter code, e.g. "da", "de", etc.
     *
     * @throws Exception\KlarnaException
     *
     * @return int {@link Language Language} constant.
     */
    public static function getLanguageForCode($code)
    {
        $language = Language::fromCode($code);

        if ($language === null) {
            throw new Exception\UnknownLanguageException($code);
        }

        return $language;
    }

    /**
     * Returns the language constant.
     *
     * @return int {@link Language}
     */
    public function getLanguage()
    {
        return $this->_language;
    }

    /**
     * Sets the currency used.
     *
     * <b>Note</b>:<br>
     * You can use the three letter shortening of the currency.<br>
     * E.g. "dkk", "eur", "nok" or "sek" instead of the constant.<br>
     *
     * @param string|int $currency {@link Currency}
     *
     * @see Currency
     *
     * @throws Exception\KlarnaException
     */
    public function setCurrency($currency)
    {
        if (!is_numeric($currency) && strlen($currency) == 3) {
            $this->setCurrency(self::getCurrencyForCode($currency));
        } else {
            $this->_checkCurrency($currency);
            $this->_currency = $currency;
        }
    }

    /**
     * Returns the {@link Currency currency} constant from the currency
     * code.
     *
     * @param string $code Two letter code, e.g. "dkk", "eur", etc.
     *
     * @throws Exception\KlarnaException
     *
     * @return int {@link Currency Currency} constant.
     */
    public static function getCurrencyForCode($code)
    {
        $currency = Currency::fromCode($code);
        if ($currency === null) {
            throw new Exception\UnknownCurrencyException($code);
        }

        return $currency;
    }

    /**
     * Returns the the currency code for the set currency constant.
     *
     * @param int $currency {@link Currency Currency} constant.
     *
     * @return string Three letter currency code.
     */
    public function getCurrencyCode($currency = null)
    {
        if ($currency === null) {
            $currency = $this->_currency;
        }

        $code = Currency::getCode($currency);

        return (string) $code;
    }

    /**
     * Returns the set currency constant.
     *
     * @return int {@link Currency}
     */
    public function getCurrency()
    {
        return $this->_currency;
    }

    /**
     * Returns the {@link Language language} constant for the specified
     * or set country.
     *
     * @param int $country {@link Country Country} constant.
     *
     * @deprecated Do not use.
     *
     * @return int|false if no match otherwise Language constant.
     */
    public function getLanguageForCountry($country = null)
    {
        if ($country === null) {
            $country = $this->_country;
        }
        // Since getLanguage defaults to EN, check so we actually have a match
        $language = Country::getLanguage($country);
        if (Country::checkLanguage($country, $language)) {
            return $language;
        }

        return false;
    }

    /**
     * Returns the {@link Currency currency} constant for the specified
     * or set country.
     *
     * @param int $country {@link Country country} constant.
     *
     * @deprecated Do not use.
     *
     * @return int|false {@link Currency currency} constant.
     */
    public function getCurrencyForCountry($country = null)
    {
        if ($country === null) {
            $country = $this->_country;
        }

        return Country::getCurrency($country);
    }

    /**
     * Sets the session id's for various device identification,
     * behaviour identification software.
     *
     * <b>Available named session id's</b>:<br>
     * string - dev_id_1<br>
     * string - dev_id_2<br>
     * string - dev_id_3<br>
     * string - beh_id_1<br>
     * string - beh_id_2<br>
     * string - beh_id_3<br>
     *
     * @param string $name Session ID identifier, e.g. 'dev_id_1'.
     * @param string $sid  Session ID.
     *
     * @throws Exception\KlarnaException
     */
    public function setSessionID($name, $sid)
    {
        $this->_checkArgument($name, 'name');
        $this->_checkArgument($sid, 'sid');

        $this->sid[$name] = $sid;
    }

    /**
     * Sets the shipment information for the upcoming transaction.<br>.
     *
     * Using this method is optional.
     *
     * <b>Available named values are</b>:<br>
     * int    - delay_adjust<br>
     * string - shipping_company<br>
     * string - shipping_product<br>
     * string - tracking_no<br>
     * array  - warehouse_addr<br>
     *
     * "warehouse_addr" is sent using {@link Address::toArray()}.
     *
     * Make sure you send in the values as the right data type.<br>
     * Use strval, intval or similar methods to ensure the right type is sent.
     *
     * @param string $name  key
     * @param mixed  $value value
     *
     * @throws Exception\KlarnaException
     */
    public function setShipmentInfo($name, $value)
    {
        $this->_checkArgument($name, 'name');

        $this->shipInfo[$name] = $value;
    }

    /**
     * Sets the Activation information for the upcoming transaction.<br>.
     *
     * Using this method is optional.
     *
     * <b>Available named values are</b>:<br>
     * int    - flags<br>
     * int    - bclass<br>
     * string - orderid1<br>
     * string - orderid2<br>
     * string - ocr<br>
     * string - reference<br>
     * string - reference_code<br>
     * string - cust_no<br>
     *
     * Make sure you send in the values as the right data type.<br>
     * Use strval, intval or similar methods to ensure the right type is sent.
     *
     * @param string $name  key
     * @param mixed  $value value
     *
     * @see setShipmentInfo
     */
    public function setActivateInfo($name, $value)
    {
        $this->activateInfo[$name] = $value;
    }

    /**
     * Sets the extra information for the upcoming transaction.<br>.
     *
     * Using this method is optional.
     *
     * <b>Available named values are</b>:<br>
     * string - cust_no<br>
     * string - estore_user<br>
     * string - ready_date<br>
     * string - rand_string<br>
     * int    - bclass<br>
     * string - pin<br>
     *
     * Make sure you send in the values as the right data type.<br>
     * Use strval, intval or similar methods to ensure the right type is sent.
     *
     * @param string $name  key
     * @param mixed  $value value
     *
     * @throws Exception\KlarnaException
     */
    public function setExtraInfo($name, $value)
    {
        $this->_checkArgument($name, 'name');

        $this->extraInfo[$name] = $value;
    }

    /**
     * Sets the income expense information for the upcoming transaction.<br>.
     *
     * Using this method is optional.
     *
     * Make sure you send in the values as the right data type.<br>
     * Use strval, intval or similar methods to ensure the right type is sent.
     *
     * @param string $name  key
     * @param mixed  $value value
     *
     * @throws Exception\KlarnaException
     */
    public function setIncomeInfo($name, $value)
    {
        $this->_checkArgument($name, 'name');

        $this->incomeInfo[$name] = $value;
    }

    /**
     * Sets the bank information for the upcoming transaction.<br>.
     *
     * Using this method is optional.
     *
     * Make sure you send in the values as the right data type.<br>
     * Use strval, intval or similar methods to ensure the right type is sent.
     *
     * @param string $name  key
     * @param mixed  $value value
     *
     * @throws Exception\KlarnaException
     */
    public function setBankInfo($name, $value)
    {
        $this->_checkArgument($name, 'name');

        $this->bankInfo[$name] = $value;
    }

    /**
     * Sets the travel information for the upcoming transaction.<br>.
     *
     * Using this method is optional.
     *
     * Make sure you send in the values as the right data type.<br>
     * Use strval, intval or similar methods to ensure the right type is sent.
     *
     * @param string $name  key
     * @param mixed  $value value
     *
     * @throws Exception\KlarnaException
     */
    public function setTravelInfo($name, $value)
    {
        $this->_checkArgument($name, 'name');

        $this->travelInfo[$name] = $value;
    }

    /**
     * Set client IP.
     *
     * @param string $clientIP Client IP address
     */
    public function setClientIP($clientIP)
    {
        $this->clientIP = $clientIP;
    }

    /**
     * Returns the clients IP address.
     *
     * @return string
     */
    public function getClientIP()
    {
        if (isset($this->clientIP)) {
            return $this->clientIP;
        }

        $tmp_ip = '';
        $x_fwd = null;

        //Proxy handling.
        if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
            $tmp_ip = $_SERVER['REMOTE_ADDR'];
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $x_fwd = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if (self::$x_forwarded_for && ($x_fwd !== null)) {
            $forwarded = explode(',', $x_fwd);

            return trim($forwarded[0]);
        }

        return $tmp_ip;
    }

    /**
     * Sets the specified address for the current order.
     *
     * <b>Address type can be</b>:<br>
     * {@link Flags::IS_SHIPPING}<br>
     * {@link Flags::IS_BILLING}<br>
     *
     * @param int     $type Address type.
     * @param Address $addr Specified address.
     *
     * @throws Exception\KlarnaException
     */
    public function setAddress($type, $addr)
    {
        if (!($addr instanceof Address)) {
            throw new Exception\InvalidAddressException();
        }

        if ($addr->isCompany === null) {
            $addr->isCompany = false;
        }

        if ($type === Flags::IS_SHIPPING) {
            $this->shipping = $addr;
            self::printDebug('shipping address array', $this->shipping);

            return;
        }

        if ($type === Flags::IS_BILLING) {
            $this->billing = $addr;
            self::printDebug('billing address array', $this->billing);

            return;
        }
        throw new Exception\UnknownAddressTypeException($type);
    }

    /**
     * Sets order id's from other systems for the upcoming transaction.<br>
     * User is only sent with {@link Klarna::addTransaction()}.<br>.
     *
     * @param string $orderid1 order id 1
     * @param string $orderid2 order id 2
     * @param string $user     username
     *
     * @see Klarna::setExtraInfo()
     *
     * @throws Exception\KlarnaException
     */
    public function setEstoreInfo($orderid1 = '', $orderid2 = '', $user = '')
    {
        if (!is_string($orderid1)) {
            $orderid1 = strval($orderid1);
        }

        if (!is_string($orderid2)) {
            $orderid2 = strval($orderid2);
        }

        if (!is_string($user)) {
            $user = strval($user);
        }

        if (strlen($user) > 0) {
            $this->setExtraInfo('estore_user', $user);
        }

        $this->orderid[0] = $orderid1;
        $this->orderid[1] = $orderid2;
    }

    /**
     * Sets the reference (person) and reference code, for the upcoming
     * transaction.
     *
     * If this is omitted, it can grab first name, last name from the address
     * and use that as a reference person.
     *
     * @param string $ref  Reference person / message to customer on invoice.
     * @param string $code Reference code / message to customer on invoice.
     */
    public function setReference($ref, $code)
    {
        $this->_checkRef($ref, $code);
        $this->reference = $ref;
        $this->reference_code = $code;
    }

    /**
     * Returns the reference (person).
     *
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Returns an associative array used to send the address to Klarna.
     * TODO: Kill it all.
     *
     * @param Address $addr Address object to assemble.
     *
     * @throws Exception\KlarnaException
     *
     * @return array The address for the specified method.
     */
    protected function assembleAddr($addr)
    {
        if (!($addr instanceof Address)) {
            throw new Exception\InvalidAddressException();
        }

        return $addr->toArray();
    }

    /**
     * Sets the comment field, which can be shown in the invoice.
     *
     * @param string $data comment to set
     */
    public function setComment($data)
    {
        $this->comment = $data;
    }

    /**
     * Adds an additional comment to the comment field. Appends with a newline.
     *
     * @param string $data comment to add
     *
     * @see Klarna::setComment()
     */
    public function addComment($data)
    {
        $this->comment .= "\n".$data;
    }

    /**
     * Returns the PNO/SSN encoding constant for currently set country.
     *
     * <b>Note</b>:<br>
     * Country, language and currency needs to match!
     *
     * @throws Exception\KlarnaException
     *
     * @return int {@link Encoding} constant.
     */
    public function getPNOEncoding()
    {
        $this->_checkLocale();

        $country = Country::getCode($this->_country);

        return Encoding::get($country);
    }

    /**
     * Purpose: The get_addresses function is used to retrieve a customer's
     * address(es). Using this, the customer is not required to enter any
     * information, only confirm the one presented to him/her.<br>.
     *
     * The get_addresses function can also be used for companies.<br>
     * If the customer enters a company number, it will return all the
     * addresses where the company is registered at.<br>
     *
     * The get_addresses function is ONLY allowed to be used for Swedish
     * persons with the following conditions:
     * <ul>
     *     <li>
     *          It can be only used if invoice or part payment is
     *          the default payment method
     *     </li>
     *     <li>
     *          It has to disappear if the customer chooses another
     *          payment method
     *     </li>
     *     <li>
     *          The button is not allowed to be called "get address", but
     *          "continue" or<br>
     *          it can be picked up automatically when all the numbers have
     *          been typed.
     *     </li>
     * </ul>
     *
     * <b>Type can be one of these</b>:<br>
     * {@link Flags::GA_ALL},<br>
     * {@link Flags::GA_LAST},<br>
     * {@link Flags::GA_GIVEN}.<br>
     *
     * @example docs/examples/getAddresses.php How to get a customers address.
     *
     * @param string $pno      Social security number, personal number, ...
     * @param int    $encoding {@link Encoding PNO Encoding} constant.
     * @param int    $type     Specifies returned information.
     *
     * @throws Exception\KlarnaException
     *
     * @return array An array of {@link Address} objects.
     */
    public function getAddresses(
        $pno,
        $encoding = null,
        $type = Flags::GA_GIVEN
    ) {
        if ($this->_country !== Country::SE) {
            throw new Exception\UnsupportedMarketException('Sweden');
        }

        //Get the PNO/SSN encoding constant.
        if ($encoding === null) {
            $encoding = $this->getPNOEncoding();
        }

        $this->_checkPNO($pno, $encoding);

        $digestSecret = self::digest(
            self::colon(
                $this->_eid,
                $pno,
                $this->_secret
            )
        );

        $paramList = array(
            $pno,
            $this->_eid,
            $digestSecret,
            $encoding,
            $type,
            $this->getClientIP(),
        );

        self::printDebug('get_addresses array', $paramList);

        $result = $this->xmlrpc_call('get_addresses', $paramList);

        self::printDebug('get_addresses result array', $result);

        $addrs = array();
        foreach ($result as $tmpAddr) {
            try {
                $addr = new Address();
                if ($type === Flags::GA_GIVEN) {
                    $addr->isCompany = (count($tmpAddr) == 5) ? true : false;
                    if ($addr->isCompany) {
                        $addr->setCompanyName($tmpAddr[0]);
                        $addr->setStreet($tmpAddr[1]);
                        $addr->setZipCode($tmpAddr[2]);
                        $addr->setCity($tmpAddr[3]);
                        $addr->setCountry($tmpAddr[4]);
                    } else {
                        $addr->setFirstName($tmpAddr[0]);
                        $addr->setLastName($tmpAddr[1]);
                        $addr->setStreet($tmpAddr[2]);
                        $addr->setZipCode($tmpAddr[3]);
                        $addr->setCity($tmpAddr[4]);
                        $addr->setCountry($tmpAddr[5]);
                    }
                } elseif ($type === Flags::GA_LAST) {
                    // Here we cannot decide if it is a company or not?
                    // Assume private person.
                    $addr->setLastName($tmpAddr[0]);
                    $addr->setStreet($tmpAddr[1]);
                    $addr->setZipCode($tmpAddr[2]);
                    $addr->setCity($tmpAddr[3]);
                    $addr->setCountry($tmpAddr[4]);
                } elseif ($type === Flags::GA_ALL) {
                    if (strlen($tmpAddr[0]) > 0) {
                        $addr->setFirstName($tmpAddr[0]);
                        $addr->setLastName($tmpAddr[1]);
                    } else {
                        $addr->isCompany = true;
                        $addr->setCompanyName($tmpAddr[1]);
                    }
                    $addr->setStreet($tmpAddr[2]);
                    $addr->setZipCode($tmpAddr[3]);
                    $addr->setCity($tmpAddr[4]);
                    $addr->setCountry($tmpAddr[5]);
                } else {
                    continue;
                }
                $addrs[] = $addr;
            } catch (\Exception $e) {
                //Silently fail
            }
        }

        return $addrs;
    }

    /**
     * Adds an article to the current goods list for the current order.
     *
     * <b>Note</b>:<br>
     * It is recommended that you use {@link Flags::INC_VAT}.<br>
     *
     * <b>Flags can be</b>:<br>
     * {@link Flags::INC_VAT}<br>
     * {@link Flags::IS_SHIPMENT}<br>
     * {@link Flags::IS_HANDLING}<br>
     * {@link Flags::PRINT_1000}<br>
     * {@link Flags::PRINT_100}<br>
     * {@link Flags::PRINT_10}<br>
     * {@link Flags::NO_FLAG}<br>
     *
     * Some flags can be added to each other for multiple options.
     *
     * @param int    $qty      Quantity.
     * @param string $artNo    Article number.
     * @param string $title    Article title.
     * @param int    $price    Article price.
     * @param float  $vat      VAT in percent, e.g. 25% is inputted as 25.
     * @param float  $discount Possible discount on article.
     * @param int    $flags    Options which specify the article
     *                         ({@link Flags::IS_HANDLING}) and it's price
     *                         ({@link Flags::INC_VAT})
     *
     * @see Klarna::addTransaction()
     * @see Klarna::reserveAmount()
     * @see Klarna::activateReservation()
     *
     * @throws Exception\KlarnaException
     */
    public function addArticle(
        $qty,
        $artNo,
        $title,
        $price,
        $vat,
        $discount = 0,
        $flags = Flags::INC_VAT
    ) {
        $this->_checkQty($qty);

        // Either artno or title has to be set
        if ((($artNo === null) || ($artNo == ''))
            && (($title === null) || ($title == ''))
        ) {
            throw new Exception\ArgumentNotSetException('Title and ArtNo', 50026);
        }

        $this->_checkPrice($price);
        $this->_checkVAT($vat);
        $this->_checkDiscount($discount);
        $this->_checkInt($flags, 'flags');

        //Create goodsList array if not set.
        if (!$this->goodsList || !is_array($this->goodsList)) {
            $this->goodsList = array();
        }

        //Populate a temp array with the article details.
        $tmpArr = array(
            'artno' => $artNo,
            'title' => $title,
            'price' => $price,
            'vat' => $vat,
            'discount' => $discount,
            'flags' => $flags,
        );

        //Add the temp array and quantity field to the internal goods list.
        $this->goodsList[] = array(
                'goods' => $tmpArr,
                'qty' => $qty,
        );

        if (count($this->goodsList) > 0) {
            self::printDebug(
                'article added',
                $this->goodsList[count($this->goodsList) - 1]
            );
        }
    }

    /**
     * Assembles and sends the current order to Klarna.<br>
     * This clears all relevant data if $clear is set to true.<br>.
     *
     * <b>This method returns an array with</b>:<br>
     * Invoice number<br>
     * Order status flag<br>
     *
     * If the flag {@link Flags::RETURN_OCR} is used:<br>
     * Invoice number<br>
     * OCR number <br>
     * Order status flag<br>
     *
     * <b>Order status can be</b>:<br>
     * {@link Flags::ACCEPTED}<br>
     * {@link Flags::PENDING}<br>
     * {@link Flags::DENIED}<br>
     *
     * Gender is only required for Germany and Netherlands.<br>
     *
     * <b>Flags can be</b>:<br>
     * {@link Flags::NO_FLAG}<br>
     * {@link Flags::TEST_MODE}<br>
     * {@link Flags::AUTO_ACTIVATE}<br>
     * {@link Flags::SENSITIVE_ORDER}<br>
     * {@link Flags::RETURN_OCR}<br>
     * {@link Flags::M_PHONE_TRANSACTION}<br>
     * {@link Flags::M_SEND_PHONE_PIN}<br>
     *
     * Some flags can be added to each other for multiple options.
     *
     * <b>Note</b>:<br>
     * Normal shipment type is assumed unless otherwise specified,
     * ou can do this by calling:<br>
     * {@link Klarna::setShipmentInfo() setShipmentInfo('delay_adjust', ...)}
     * with either:<br>
     * {@link Flags::NORMAL_SHIPMENT NORMAL_SHIPMENT} or
     * {@link Flags::EXPRESS_SHIPMENT EXPRESS_SHIPMENT}<br>
     *
     * @param string $pno      Personal number, SSN, date of birth, etc.
     * @param int    $gender   {@link Flags::FEMALE} or
     *                         {@link Flags::MALE},
     *                         null or "" for unspecified.
     * @param int    $flags    Options which affect the behaviour.
     * @param int    $pclass   PClass id used for this invoice.
     * @param int    $encoding {@link Encoding Encoding} constant for the
     *                         PNO parameter.
     * @param bool   $clear    Whether customer info should be cleared after
     *                         this call or not.
     *
     * @throws Exception\KlarnaException
     *
     * @return array An array with invoice number and order status. [string, int]
     */
    public function addTransaction(
        $pno,
        $gender,
        $flags = Flags::NO_FLAG,
        $pclass = PClass::INVOICE,
        $encoding = null,
        $clear = true
    ) {
        $this->_checkLocale(50023);

        //Get the PNO/SSN encoding constant.
        if ($encoding === null) {
            $encoding = $this->getPNOEncoding();
        }

        if (!($flags & Flags::PRE_PAY)) {
            $this->_checkPNO($pno, $encoding);
        }

        if ($gender === 'm') {
            $gender = Flags::MALE;
        } elseif ($gender === 'f') {
            $gender = Flags::FEMALE;
        }

        if ($gender !== null && strlen($gender) > 0) {
            $this->_checkInt($gender, 'gender');
        }

        $this->_checkInt($flags, 'flags');
        $this->_checkInt($pclass, 'pclass');

        //Check so required information is set.
        $this->_checkGoodslist();

        //We need at least one address set
        if (!($this->billing instanceof Address)
            && !($this->shipping instanceof Address)
        ) {
            throw new Exception\MissingAddressException();
        }

        //If only one address is set, copy to the other address.
        if (!($this->shipping instanceof Address)
            && ($this->billing instanceof Address)
        ) {
            $this->shipping = $this->billing;
        } elseif (!($this->billing instanceof Address)
            && ($this->shipping instanceof Address)
        ) {
            $this->billing = $this->shipping;
        }

        //Assume normal shipment unless otherwise specified.
        if (!isset($this->shipInfo['delay_adjust'])) {
            $this->setShipmentInfo('delay_adjust', Flags::NORMAL_SHIPMENT);
        }

        //function add_transaction_digest
        $string = '';
        foreach ($this->goodsList as $goods) {
            $string .= $goods['goods']['title'].':';
        }
        $digestSecret = self::digest($string.$this->_secret);
        //end function add_transaction_digest

        $billing = $this->assembleAddr($this->billing);
        $shipping = $this->assembleAddr($this->shipping);

        //Shipping country must match specified country!
        if (strlen($shipping['country']) > 0
            && ($shipping['country'] !== $this->_country)
        ) {
            throw new Exception\ShippingCountryException();
        }

        $paramList = array(
            $pno,
            $gender,
            $this->reference,
            $this->reference_code,
            $this->orderid[0],
            $this->orderid[1],
            $shipping,
            $billing,
            $this->getClientIP(),
            $flags,
            $this->_currency,
            $this->_country,
            $this->_language,
            $this->_eid,
            $digestSecret,
            $encoding,
            $pclass,
            $this->goodsList,
            $this->comment,
            $this->shipInfo,
            $this->travelInfo,
            $this->incomeInfo,
            $this->bankInfo,
            $this->sid,
            $this->extraInfo,
        );

        self::printDebug('add_invoice', $paramList);

        $result = $this->xmlrpc_call('add_invoice', $paramList);

        if ($clear === true) {
            //Make sure any stored values that need to be unique between
            //purchases are cleared.
            $this->clear();
        }

        self::printDebug('add_invoice result', $result);

        return $result;
    }

    /**
     * Activates previously created invoice
     * (from {@link Klarna::addTransaction()}).
     *
     * <b>Note</b>:<br>
     * If you want to change the shipment type, you can specify it using:
     * {@link Klarna::setShipmentInfo() setShipmentInfo('delay_adjust', ...)}
     * with either: {@link Flags::NORMAL_SHIPMENT NORMAL_SHIPMENT} or
     * {@link Flags::EXPRESS_SHIPMENT EXPRESS_SHIPMENT}
     *
     * @param string $invNo  Invoice number.
     * @param int    $pclass PClass id used for this invoice.
     * @param bool   $clear  Whether customer info should be cleared after this
     *                       call.
     *
     * @see Klarna::setShipmentInfo()
     *
     * @throws Exception\KlarnaException
     *
     * @return string An URL to the PDF invoice.
     */
    public function activateInvoice(
        $invNo,
        $pclass = PClass::INVOICE,
        $clear = true
    ) {
        $this->_checkInvNo($invNo);

        $digestSecret = self::digest(
            self::colon($this->_eid, $invNo, $this->_secret)
        );

        $paramList = array(
            $this->_eid,
            $invNo,
            $digestSecret,
            $pclass,
            $this->shipInfo,
        );

        self::printDebug('activate_invoice', $paramList);

        $result = $this->xmlrpc_call('activate_invoice', $paramList);

        if ($clear === true) {
            $this->clear();
        }

        self::printDebug('activate_invoice result', $result);

        return $result;
    }

    /**
     * Removes a passive invoices which has previously been created with
     * {@link Klarna::addTransaction()}.
     * True is returned if the invoice was successfully removed, otherwise an
     * exception is thrown.<br>.
     *
     * @param string $invNo Invoice number.
     *
     * @throws Exception\KlarnaException
     *
     * @return bool
     */
    public function deleteInvoice($invNo)
    {
        $this->_checkInvNo($invNo);

        $digestSecret = self::digest(
            self::colon($this->_eid, $invNo, $this->_secret)
        );

        $paramList = array(
            $this->_eid,
            $invNo,
            $digestSecret,
        );

        self::printDebug('delete_invoice', $paramList);

        $result = $this->xmlrpc_call('delete_invoice', $paramList);

        return ($result == 'ok') ? true : false;
    }

    /**
     * Summarizes the prices of the held goods list.
     *
     * @return int total amount
     */
    public function summarizeGoodsList()
    {
        $amount = 0;
        if (!is_array($this->goodsList)) {
            return $amount;
        }
        foreach ($this->goodsList as $goods) {
            $price = $goods['goods']['price'];

            // Add VAT if price is Excluding VAT
            if (($goods['goods']['flags'] & Flags::INC_VAT) === 0) {
                $vat = $goods['goods']['vat'] / 100.0;
                $price *= (1.0 + $vat);
            }

            // Reduce discounts
            if ($goods['goods']['discount'] > 0) {
                $discount = $goods['goods']['discount'] / 100.0;
                $price *= (1.0 - $discount);
            }

            $amount += $price * (int) $goods['qty'];
        }

        return $amount;
    }

    /**
     * Reserves a purchase amount for a specific customer. <br>
     * The reservation is valid, by default, for 7 days.<br>.
     *
     * <b>This method returns an array with</b>:<br>
     * A reservation number (rno)<br>
     * Order status flag<br>
     *
     * <b>Order status can be</b>:<br>
     * {@link Flags::ACCEPTED}<br>
     * {@link Flags::PENDING}<br>
     * {@link Flags::DENIED}<br>
     *
     * <b>Please note</b>:<br>
     * Activation must be done with activate_reservation, i.e. you cannot
     * activate through Klarna Online.
     *
     * Gender is only required for Germany and Netherlands.<br>
     *
     * <b>Flags can be set to</b>:<br>
     * {@link Flags::NO_FLAG}<br>
     * {@link Flags::TEST_MODE}<br>
     * {@link Flags::RSRV_SENSITIVE_ORDER}<br>
     * {@link Flags::RSRV_PHONE_TRANSACTION}<br>
     * {@link Flags::RSRV_SEND_PHONE_PIN}<br>
     *
     * Some flags can be added to each other for multiple options.
     *
     * <b>Note</b>:<br>
     * Normal shipment type is assumed unless otherwise specified, you can do
     * this by calling:<br>
     * {@link Klarna::setShipmentInfo() setShipmentInfo('delay_adjust', ...)}
     * with either: {@link Flags::NORMAL_SHIPMENT NORMAL_SHIPMENT} or
     * {@link Flags::EXPRESS_SHIPMENT EXPRESS_SHIPMENT}<br>
     *
     * @example docs/examples/reserveAmount.php How to create a reservation.
     *
     * @param string $pno      Personal number, SSN, date of birth, etc.
     * @param int    $gender   {@link Flags::FEMALE} or
     *                         {@link Flags::MALE}, null for unspecified.
     * @param int    $amount   Amount to be reserved, including VAT.
     * @param int    $flags    Options which affect the behaviour.
     * @param int    $pclass   {@link PClass::getId() PClass ID}.
     * @param int    $encoding {@link Encoding PNO Encoding} constant.
     * @param bool   $clear    Whether customer info should be cleared after
     *                         this call.
     *
     * @throws Exception\KlarnaException
     *
     * @return array An array with reservation number and order
     *               status. [string, int]
     */
    public function reserveAmount(
        $pno,
        $gender,
        $amount,
        $flags = 0,
        $pclass = PClass::INVOICE,
        $encoding = null,
        $clear = true
    ) {
        $this->_checkLocale();

        //Get the PNO/SSN encoding constant.
        if ($encoding === null) {
            $encoding = $this->getPNOEncoding();
        }

        $this->_checkPNO($pno, $encoding);

        if ($gender === 'm') {
            $gender = Flags::MALE;
        } elseif ($gender === 'f') {
            $gender = Flags::FEMALE;
        }
        if ($gender !== null && strlen($gender) > 0) {
            $this->_checkInt($gender, 'gender');
        }

        $this->_checkInt($flags, 'flags');
        $this->_checkInt($pclass, 'pclass');

        //Check so required information is set.
        $this->_checkGoodslist();

        //Calculate automatically the amount from goodsList.
        if ($amount === -1) {
            $amount = (int) round($this->summarizeGoodsList());
        } else {
            $this->_checkAmount($amount);
        }

        if ($amount < 0) {
            throw new Exception\InvalidPriceException($amount);
        }

        //No addresses used for phone transactions
        if ($flags & Flags::RSRV_PHONE_TRANSACTION) {
            $billing = $shipping = '';
        } else {
            $billing = $this->assembleAddr($this->billing);
            $shipping = $this->assembleAddr($this->shipping);

            if (strlen($shipping['country']) > 0
                && ($shipping['country'] !== $this->_country)
            ) {
                throw new Exception\ShippingCountryException();
            }
        }

        //Assume normal shipment unless otherwise specified.
        if (!isset($this->shipInfo['delay_adjust'])) {
            $this->setShipmentInfo('delay_adjust', Flags::NORMAL_SHIPMENT);
        }

        $digestSecret = self::digest(
            "{$this->_eid}:{$pno}:{$amount}:{$this->_secret}"
        );

        $paramList = array(
            $pno,
            $gender,
            $amount,
            $this->reference,
            $this->reference_code,
            $this->orderid[0],
            $this->orderid[1],
            $shipping,
            $billing,
            $this->getClientIP(),
            $flags,
            $this->_currency,
            $this->_country,
            $this->_language,
            $this->_eid,
            $digestSecret,
            $encoding,
            $pclass,
            $this->goodsList,
            $this->comment,
            $this->shipInfo,
            $this->travelInfo,
            $this->incomeInfo,
            $this->bankInfo,
            $this->sid,
            $this->extraInfo,
        );

        self::printDebug('reserve_amount', $paramList);

        $result = $this->xmlrpc_call('reserve_amount', $paramList);

        if ($clear === true) {
            //Make sure any stored values that need to be unique between
            //purchases are cleared.
            $this->clear();
        }

        self::printDebug('reserve_amount result', $result);

        return $result;
    }

    /**
     * Extends the reservations expiration date.
     *
     * @example docs/examples/extendExpiryDate.php How to extend a reservations expiry date.
     *
     * @param string $rno Reservation number.
     *
     * @throws Exception\KlarnaException
     *
     * @return DateTime The new expiration date.
     */
    public function extendExpiryDate($rno)
    {
        $this->_checkRNO($rno);

        $digestSecret = self::digest(
            self::colon($this->_eid, $rno, $this->_secret)
        );

        $paramList = array(
            $rno,
            $this->_eid,
            $digestSecret,
        );

        self::printDebug('extend_expiry_date', $paramList);

        $result = $this->xmlrpc_call('extend_expiry_date', $paramList);

        // Default to server location as API does not include timezone info
        $tz = new DateTimeZone('Europe/Stockholm');

        // $result = '20150525T103631';
        $date = DateTime::createFromFormat('Ymd\THis', $result, $tz);
        if ($date === false) {
            throw new Exception\KlarnaException(
                "Could not parse result '{$result}' into date format 'Ymd\\This'"
            );
        }

        return $date;
    }

    /**
     * Cancels a reservation.
     *
     * @example docs/examples/cancelReservation.php How to cancel a reservation.
     *
     * @param string $rno Reservation number.
     *
     * @throws Exception\KlarnaException
     *
     * @return bool True, if the cancellation was successful.
     */
    public function cancelReservation($rno)
    {
        $this->_checkRNO($rno);

        $digestSecret = self::digest(
            self::colon($this->_eid, $rno, $this->_secret)
        );
        $paramList = array(
            $rno,
            $this->_eid,
            $digestSecret,
        );

        self::printDebug('cancel_reservation', $paramList);

        $result = $this->xmlrpc_call('cancel_reservation', $paramList);

        return $result == 'ok';
    }

    /**
     * Changes specified reservation to a new amount.
     *
     * <b>Flags can be either of these</b>:<br>
     * {@link Flags::NEW_AMOUNT}<br>
     * {@link Flags::ADD_AMOUNT}<br>
     *
     * @param string $rno    Reservation number.
     * @param int    $amount Amount including VAT.
     * @param int    $flags  Options which affect the behaviour.
     *
     * @throws Exception\KlarnaException
     *
     * @return bool True, if the change was successful.
     */
    public function changeReservation(
        $rno,
        $amount,
        $flags = Flags::NEW_AMOUNT
    ) {
        $this->_checkRNO($rno);
        $this->_checkAmount($amount);
        $this->_checkInt($flags, 'flags');

        $digestSecret = self::digest(
            self::colon($this->_eid, $rno, $amount, $this->_secret)
        );
        $paramList = array(
            $rno,
            $amount,
            $this->_eid,
            $digestSecret,
            $flags,
        );

        self::printDebug('change_reservation', $paramList);

        $result = $this->xmlrpc_call('change_reservation', $paramList);

        return ($result  == 'ok') ? true : false;
    }

    /**
     * Update the reservation matching the given reservation number.
     *
     * @example docs/examples/update.php How to update a reservation.
     *
     * @param string $rno   Reservation number
     * @param bool   $clear clear set data after updating. Defaulted to true.
     *
     * @throws Exception\KlarnaException if no RNO is given, or if an error is received
     *                                   from Klarna Online.
     *
     * @return true if the update was successful
     */
    public function update($rno, $clear = true)
    {
        $rno = strval($rno);

        // All info that is sent in is part of the digest secret, in this order:
        // [
        //      proto_vsn, client_vsn, eid, rno, careof, street, zip, city,
        //      country, fname, lname, careof, street, zip, city, country,
        //      fname, lname, artno, qty, orderid1, orderid2
        // ].
        // The address part appears twice, that is one per address that
        // changes. If no value is sent in for an optional field, there
        // is no entry for this field in the digest secret. Shared secret
        // is added at the end of the digest secret.
        $digestArray = array(
            str_replace('.', ':', $this->PROTO),
            $this->VERSION,
            $this->_eid,
            $rno,
        );
        $digestArray = array_merge(
            $digestArray,
            $this->_addressDigestPart($this->shipping)
        );
        $digestArray = array_merge(
            $digestArray,
            $this->_addressDigestPart($this->billing)
        );
        if (is_array($this->goodsList) && $this->goodsList !== array()) {
            foreach ($this->goodsList as $goods) {
                if (strlen($goods['goods']['artno']) > 0) {
                    $digestArray[] = $goods['goods']['artno'];
                } else {
                    $digestArray[] = $goods['goods']['title'];
                }
                $digestArray[] = $goods['qty'];
            }
        }
        foreach ($this->orderid as $orderid) {
            $digestArray[] = $orderid;
        }
        $digestArray[] = $this->_secret;

        $digestSecret = $this->digest(
            call_user_func_array(
                array('self', 'colon'),
                $digestArray
            )
        );

        $shipping = array();
        $billing = array();
        if ($this->shipping !== null && $this->shipping instanceof Address) {
            $shipping = $this->shipping->toArray();
        }
        if ($this->billing !== null && $this->billing instanceof Address) {
            $billing = $this->billing->toArray();
        }
        $paramList = array(
            $this->_eid,
            $digestSecret,
            $rno,
            array(
                'goods_list' => $this->goodsList,
                'dlv_addr' => $shipping,
                'bill_addr' => $billing,
                'orderid1' => $this->orderid[0],
                'orderid2' => $this->orderid[1],
            ),
        );

        self::printDebug('update array', $paramList);

        $result = $this->xmlrpc_call('update', $paramList);

        self::printDebug('update result', $result);

        return $result === 'ok';
    }

    /**
     * Help function to sort the address for update digest.
     *
     * @param Address|null $address Address object or null
     *
     * @return array
     */
    private function _addressDigestPart(Address $address = null)
    {
        if ($address === null) {
            return array();
        }

        $keyOrder = array(
            'careof', 'street', 'zip', 'city', 'country', 'fname', 'lname',
        );

        $holder = $address->toArray();
        $digest = array();

        foreach ($keyOrder as $key) {
            if ($holder[$key] != '') {
                $digest[] = $holder[$key];
            }
        }

        return $digest;
    }

    /**
     * Activate the reservation matching the given reservation number.
     * Optional information should be set in ActivateInfo.
     *
     * To perform a partial activation, use the addArtNo function to specify
     * which items in the reservation to include in the activation.
     *
     * @example docs/examples/activate.php How to activate a reservation.
     *
     * @param string $rno   Reservation number
     * @param string $ocr   optional OCR number to attach to the reservation when
     *                      activating. Overrides OCR specified in activateInfo.
     * @param string $flags optional flags to affect behaviour. If specified it
     *                      will overwrite any flag set in activateInfo.
     * @param bool   $clear clear set data after activating. Defaulted to true.
     *
     * @throws Exception\KlarnaException when the RNO is not specified, or if an error
     *                                   is received from Klarna Online.
     *
     * @return A string array with risk status and reservation number.
     */
    public function activate(
        $rno,
        $ocr = null,
        $flags = null,
        $clear = true
    ) {
        $this->_checkRNO($rno);

        // Overwrite any OCR set on activateInfo if supplied here since this
        // method call is more specific.
        if ($ocr !== null) {
            $this->setActivateInfo('ocr', $ocr);
        }

        // If flags is specified set the flag supplied here to activateInfo.
        if ($flags !== null) {
            $this->setActivateInfo('flags', $flags);
        }

        //Assume normal shipment unless otherwise specified.
        if (!array_key_exists('delay_adjust', $this->shipInfo)) {
            $this->setShipmentInfo('delay_adjust', Flags::NORMAL_SHIPMENT);
        }

        // Append shipment info to activateInfo
        $this->activateInfo['shipment_info'] = $this->shipInfo;

        // Unlike other calls, if NO_FLAG is specified it should not be sent in
        // at all.
        if (array_key_exists('flags', $this->activateInfo)
            && $this->activateInfo['flags'] === Flags::NO_FLAG
        ) {
            unset($this->activateInfo['flags']);
        }

        // Build digest. Any field in activateInfo that is set is included in
        // the digest.
        $digestArray = array(
            str_replace('.', ':', $this->PROTO),
            $this->VERSION,
            $this->_eid,
            $rno,
        );

        $optionalDigestKeys = array(
            'bclass',
            'cust_no',
            'flags',
            'ocr',
            'orderid1',
            'orderid2',
            'reference',
            'reference_code',
        );

        foreach ($optionalDigestKeys as $key) {
            if (array_key_exists($key, $this->activateInfo)) {
                $digestArray[] = $this->activateInfo[$key];
            }
        }

        if (array_key_exists('delay_adjust', $this->activateInfo['shipment_info'])) {
            $digestArray[] = $this->activateInfo['shipment_info']['delay_adjust'];
        }

        // If there are any artnos added with addArtNo, add them to the digest
        // and to the activateInfo
        if (is_array($this->artNos)) {
            foreach ($this->artNos as $artNo) {
                $digestArray[] = $artNo['artno'];
                $digestArray[] = $artNo['qty'];
            }
            $this->setActivateInfo('artnos', $this->artNos);
        }

        $digestArray[] = $this->_secret;
        $digestSecret = self::digest(
            call_user_func_array(
                array('self', 'colon'),
                $digestArray
            )
        );

        // Create the parameter list.
        $paramList = array(
            $this->_eid,
            $digestSecret,
            $rno,
            $this->activateInfo,
        );

        self::printDebug('activate array', $paramList);

        $result = $this->xmlrpc_call('activate', $paramList);

        self::printDebug('activate result', $result);

        // Clear the state if specified.
        if ($clear) {
            $this->clear();
        }

        return $result;
    }

    /**
     * Activates a previously created reservation.
     *
     * <b>This method returns an array with</b>:<br>
     * Risk status ("no_risk", "ok")<br>
     * Invoice number<br>
     *
     * Gender is only required for Germany and Netherlands.<br>
     *
     * Use of the OCR parameter is optional.
     * An OCR number can be retrieved by using: {@link Klarna::reserveOCR()}.
     *
     * <b>Flags can be set to</b>:<br>
     * {@link Flags::NO_FLAG}<br>
     * {@link Flags::TEST_MODE}<br>
     * {@link Flags::RSRV_SEND_BY_MAIL}<br>
     * {@link Flags::RSRV_SEND_BY_EMAIL}<br>
     * {@link Flags::RSRV_PRESERVE_RESERVATION}<br>
     * {@link Flags::RSRV_SENSITIVE_ORDER}<br>
     *
     * Some flags can be added to each other for multiple options.
     *
     * <b>Note</b>:<br>
     * Normal shipment type is assumed unless otherwise specified, you can
     * do this by calling:
     * {@link Klarna::setShipmentInfo() setShipmentInfo('delay_adjust', ...)}
     * with either: {@link Flags::NORMAL_SHIPMENT NORMAL_SHIPMENT} or
     * {@link Flags::EXPRESS_SHIPMENT EXPRESS_SHIPMENT}<br>
     *
     * @param string $pno      Personal number, SSN, date of birth, etc.
     * @param string $rno      Reservation number.
     * @param int    $gender   {@link Flags::FEMALE} or
     *                         {@link Flags::MALE}, null for unspecified.
     * @param string $ocr      A OCR number.
     * @param int    $flags    Options which affect the behaviour.
     * @param int    $pclass   {@link PClass::getId() PClass ID}.
     * @param int    $encoding {@link Encoding PNO Encoding} constant.
     * @param bool   $clear    Whether customer info should be cleared after
     *                         this call.
     *
     * @see Klarna::reserveAmount()
     *
     * @throws Exception\KlarnaException
     *
     * @return array An array with risk status and invoice number [string, string].
     */
    public function activateReservation(
        $pno,
        $rno,
        $gender,
        $ocr = '',
        $flags = Flags::NO_FLAG,
        $pclass = PClass::INVOICE,
        $encoding = null,
        $clear = true
    ) {
        $this->_checkLocale();

        //Get the PNO/SSN encoding constant.
        if ($encoding === null) {
            $encoding = $this->getPNOEncoding();
        }

        // Only check PNO if it is not explicitly null.
        if ($pno !== null) {
            $this->_checkPNO($pno, $encoding);
        }

        $this->_checkRNO($rno);

        if ($gender !== null && strlen($gender) > 0) {
            $this->_checkInt($gender, 'gender');
        }

        $this->_checkOCR($ocr);
        $this->_checkRef($this->reference, $this->reference_code);

        $this->_checkGoodslist();

        //No addresses used for phone transactions
        $billing = $shipping = '';
        if (!($flags & Flags::RSRV_PHONE_TRANSACTION)) {
            $billing = $this->assembleAddr($this->billing);
            $shipping = $this->assembleAddr($this->shipping);

            if (strlen($shipping['country']) > 0
                && ($shipping['country'] !== $this->_country)
            ) {
                throw new Exception\ShippingCountryException();
            }
        }

        //activate digest
        $string = $this->_eid.':'.$pno.':';
        foreach ($this->goodsList as $goods) {
            $string .= $goods['goods']['artno'].':'.$goods['qty'].':';
        }
        $digestSecret = self::digest($string.$this->_secret);
        //end digest

        //Assume normal shipment unless otherwise specified.
        if (!isset($this->shipInfo['delay_adjust'])) {
            $this->setShipmentInfo('delay_adjust', Flags::NORMAL_SHIPMENT);
        }

        $paramList = array(
            $rno,
            $ocr,
            $pno,
            $gender,
            $this->reference,
            $this->reference_code,
            $this->orderid[0],
            $this->orderid[1],
            $shipping,
            $billing,
            '0.0.0.0',
            $flags,
            $this->_currency,
            $this->_country,
            $this->_language,
            $this->_eid,
            $digestSecret,
            $encoding,
            $pclass,
            $this->goodsList,
            $this->comment,
            $this->shipInfo,
            $this->travelInfo,
            $this->incomeInfo,
            $this->bankInfo,
            $this->extraInfo,
        );

        self::printDebug('activate_reservation', $paramList);

        $result = $this->xmlrpc_call('activate_reservation', $paramList);

        if ($clear === true) {
            $this->clear();
        }

        self::printDebug('activate_reservation result', $result);

        return $result;
    }

    /**
     * Splits a reservation due to for example outstanding articles.
     *
     * <b>For flags usage see</b>:<br>
     * {@link Klarna::reserveAmount()}<br>
     *
     * @example docs/examples/splitReservation.php How to split a reservation.
     *
     * @param string $rno    Reservation number.
     * @param int    $amount The amount to be subtracted from the reservation.
     * @param int    $flags  Options which affect the behaviour.
     *
     * @throws Exception\KlarnaException
     *
     * @return string A new reservation number.
     */
    public function splitReservation(
        $rno,
        $amount,
        $flags = Flags::NO_FLAG
    ) {
        //Check so required information is set.
        $this->_checkRNO($rno);
        $this->_checkAmount($amount);

        if ($amount <= 0) {
            throw new Exception\InvalidPriceException($amount);
        }

        $digestSecret = self::digest(
            self::colon($this->_eid, $rno, $amount, $this->_secret)
        );
        $paramList = array(
            $rno,
            $amount,
            $this->orderid[0],
            $this->orderid[1],
            $flags,
            $this->_eid,
            $digestSecret,
        );

        self::printDebug('split_reservation array', $paramList);

        $result = $this->xmlrpc_call('split_reservation', $paramList);

        self::printDebug('split_reservation result', $result);

        return $result;
    }

    /**
     * Reserves a specified number of OCR numbers.<br>
     * For the specified country or the {@link Klarna::setCountry() set country}.<br>.
     *
     * @example docs/examples/reserveOCR.php How to reserve OCRs.
     *
     * @param int $no      The number of OCR numbers to reserve.
     * @param int $country {@link Country} constant.
     *
     * @throws Exception\KlarnaException
     *
     * @return array An array of OCR numbers.
     */
    public function reserveOCR($no, $country = null)
    {
        $this->_checkNo($no);
        if ($country === null) {
            if (!$this->_country) {
                throw new Exception\MissingCountryException();
            }
            $country = $this->_country;
        } else {
            $this->_checkCountry($country);
        }

        $digestSecret = self::digest(
            self::colon($this->_eid, $no, $this->_secret)
        );
        $paramList = array(
            $no,
            $this->_eid,
            $digestSecret,
            $country,
        );

        self::printDebug('reserve_ocr_nums array', $paramList);

        return $this->xmlrpc_call('reserve_ocr_nums', $paramList);
    }

    /**
     * Checks if the specified SSN/PNO has an part payment account with Klarna.
     *
     * @example docs/examples/hasAccount.php How to check for a part payment account.
     *
     * @param string $pno      Social security number, Personal number, ...
     * @param int    $encoding {@link Encoding PNO Encoding} constant.
     *
     * @throws Exception\KlarnaException
     *
     * @return bool True, if customer has an account.
     */
    public function hasAccount($pno, $encoding = null)
    {
        //Get the PNO/SSN encoding constant.
        if ($encoding === null) {
            $encoding = $this->getPNOEncoding();
        }

        $this->_checkPNO($pno, $encoding);

        $digest = self::digest(
            self::colon($this->_eid, $pno, $this->_secret)
        );

        $paramList = array(
            $this->_eid,
            $pno,
            $digest,
            $encoding,
        );

        self::printDebug('has_account', $paramList);

        $result = $this->xmlrpc_call('has_account', $paramList);

        return $result === 'true';
    }

    /**
     * Adds an article number and quantity to be used in
     * {@link Klarna::activatePart()}, {@link Klarna::creditPart()}
     * and {@link Klarna::invoicePartAmount()}.
     *
     * @param int    $qty   Quantity of specified article.
     * @param string $artNo Article number.
     *
     * @throws Exception\KlarnaException
     */
    public function addArtNo($qty, $artNo)
    {
        $this->_checkQty($qty);
        $this->_checkArtNo($artNo);

        if (!is_array($this->artNos)) {
            $this->artNos = array();
        }

        $this->artNos[] = array('artno' => $artNo, 'qty' => $qty);
    }

    /**
     * Partially activates a passive invoice.
     *
     * Returned array contains index "url" and "invno".<br>
     * The value of "url" is a URL pointing to a temporary PDF-version of the
     * activated invoice.<br>
     * The value of "invno" is either 0 if the entire invoice was activated or
     * the number on the new passive invoice.<br>
     *
     * <b>Note</b>:<br>
     * You need to call {@link Klarna::addArtNo()} first, to specify which
     * articles and how many you want to partially activate.<br>
     * If you want to change the shipment type, you can specify it using:
     * {@link Klarna::setShipmentInfo() setShipmentInfo('delay_adjust', ...)}
     * with either: {@link Flags::NORMAL_SHIPMENT NORMAL_SHIPMENT}
     * or {@link Flags::EXPRESS_SHIPMENT EXPRESS_SHIPMENT}
     *
     * @param string $invNo  Invoice numbers.
     * @param int    $pclass PClass id used for this invoice.
     * @param bool   $clear  Whether customer info should be cleared after
     *                       this call.
     *
     * @see Klarna::addArtNo()
     * @see Klarna::activateInvoice()
     *
     * @throws Exception\KlarnaException
     *
     * @return array An array with invoice URL and invoice number.
     *               ['url' => val, 'invno' => val]
     */
    public function activatePart(
        $invNo,
        $pclass = PClass::INVOICE,
        $clear = true
    ) {
        $this->_checkInvNo($invNo);
        $this->_checkArtNos($this->artNos);

        self::printDebug('activate_part artNos array', $this->artNos);

        //function activate_part_digest
        $string = $this->_eid.':'.$invNo.':';
        foreach ($this->artNos as $artNo) {
            $string .= $artNo['artno'].':'.$artNo['qty'].':';
        }
        $digestSecret = self::digest($string.$this->_secret);
        //end activate_part_digest

        $paramList = array(
            $this->_eid,
            $invNo,
            $this->artNos,
            $digestSecret,
            $pclass,
            $this->shipInfo,
        );

        self::printDebug('activate_part array', $paramList);

        $result = $this->xmlrpc_call('activate_part', $paramList);

        if ($clear === true) {
            $this->clear();
        }

        self::printDebug('activate_part result', $result);

        return $result;
    }

    /**
     * Retrieves the total amount for an active invoice.
     *
     * @param string $invNo Invoice number.
     *
     * @throws Exception\KlarnaException
     *
     * @return float The total amount.
     */
    public function invoiceAmount($invNo)
    {
        $this->_checkInvNo($invNo);

        $digestSecret = self::digest(
            self::colon($this->_eid, $invNo, $this->_secret)
        );

        $paramList = array(
            $this->_eid,
            $invNo,
            $digestSecret,
        );

        self::printDebug('invoice_amount array', $paramList);

        $result = $this->xmlrpc_call('invoice_amount', $paramList);

        //Result is in cents, fix it.
        return $result / 100;
    }

    /**
     * Changes the order number of a purchase that was set when the order was
     * made online.
     *
     * @param string $invNo   Invoice number.
     * @param string $orderid Estores order number.
     *
     * @throws Exception\KlarnaException
     *
     * @return string Invoice number.
     */
    public function updateOrderNo($invNo, $orderid)
    {
        $this->_checkInvNo($invNo);
        $this->_checkEstoreOrderNo($orderid);

        $digestSecret = self::digest(
            self::colon($invNo, $orderid, $this->_secret)
        );

        $paramList = array(
            $this->_eid,
            $digestSecret,
            $invNo,
            $orderid,
        );

        self::printDebug('update_orderno array', $paramList);

        $result = $this->xmlrpc_call('update_orderno', $paramList);

        return $result;
    }

    /**
     * Sends an activated invoice to the customer via e-mail. <br>
     * The email is sent in plain text format and contains a link to a
     * PDF-invoice.<br>.
     *
     * <b>Please note!</b><br>
     * Regular postal service is used if the customer has not entered his/her
     * e-mail address when making the purchase (charges may apply).<br>
     *
     * @example docs/examples/emailInvoice.php How to email an invoice.
     *
     * @param string $invNo Invoice number.
     *
     * @throws Exception\KlarnaException
     *
     * @return string Invoice number.
     */
    public function emailInvoice($invNo)
    {
        $this->_checkInvNo($invNo);

        $digestSecret = self::digest(
            self::colon($this->_eid, $invNo, $this->_secret)
        );
        $paramList = array(
            $this->_eid,
            $invNo,
            $digestSecret,
        );

        self::printDebug('email_invoice array', $paramList);

        return $this->xmlrpc_call('email_invoice', $paramList);
    }

    /**
     * Requests a postal send-out of an activated invoice to a customer by
     * Klarna (charges may apply).
     *
     * @example docs/examples/sendInvoice.php How to send an invoice.
     *
     * @param string $invNo Invoice number.
     *
     * @throws Exception\KlarnaException
     *
     * @return string Invoice number.
     */
    public function sendInvoice($invNo)
    {
        $this->_checkInvNo($invNo);

        $digestSecret = self::digest(
            self::colon($this->_eid, $invNo, $this->_secret)
        );
        $paramList = array(
            $this->_eid,
            $invNo,
            $digestSecret,
        );

        self::printDebug('send_invoice array', $paramList);

        return $this->xmlrpc_call('send_invoice', $paramList);
    }

    /**
     * Gives discounts on invoices.<br>
     * If you are using standard integration and the purchase is not yet
     * activated (you have not yet delivered the goods), <br>
     * just change the article list in our online interface Klarna Online.<br>.
     *
     * <b>Flags can be</b>:<br>
     * {@link Flags::INC_VAT}<br>
     * {@link Flags::NO_FLAG}, <b>NOT RECOMMENDED!</b><br>
     *
     * @param string $invNo       Invoice number.
     * @param int    $amount      The amount given as a discount.
     * @param float  $vat         VAT in percent, e.g. 22.2 for 22.2%.
     * @param int    $flags       If amount is
     *                            {@link Flags::INC_VAT including} or
     *                            {@link Flags::NO_FLAG excluding} VAT.
     * @param string $description Optional custom text to present as discount
     *                            in the invoice.
     *
     * @example docs/examples/returnAmount.php How to perform a return.
     *
     * @throws Exception\KlarnaException
     *
     * @return string Invoice number.
     */
    public function returnAmount(
        $invNo,
        $amount,
        $vat,
        $flags = Flags::INC_VAT,
        $description = ''
    ) {
        $this->_checkInvNo($invNo);
        $this->_checkAmount($amount);
        $this->_checkVAT($vat);
        $this->_checkInt($flags, 'flags');

        if ($description == null) {
            $description = '';
        }

        $digestSecret = self::digest(
            self::colon($this->_eid, $invNo, $this->_secret)
        );
        $paramList = array(
            $this->_eid,
            $invNo,
            $amount,
            $vat,
            $digestSecret,
            $flags,
            $description,
        );

        self::printDebug('return_amount', $paramList);

        return $this->xmlrpc_call('return_amount', $paramList);
    }

    /**
     * Performs a complete refund on an invoice, part payment and mobile
     * purchase.
     *
     * @example docs/examples/creditInvoice.php How to credit an invoice.
     *
     * @param string $invNo  Invoice number.
     * @param string $credNo Credit number.
     *
     * @throws Exception\KlarnaException
     *
     * @return string Invoice number.
     */
    public function creditInvoice($invNo, $credNo = '')
    {
        $this->_checkInvNo($invNo);
        $this->_checkCredNo($credNo);

        $digestSecret = self::digest(
            self::colon($this->_eid, $invNo, $this->_secret)
        );
        $paramList = array(
            $this->_eid,
            $invNo,
            $credNo,
            $digestSecret,
        );

        self::printDebug('credit_invoice', $paramList);

        return $this->xmlrpc_call('credit_invoice', $paramList);
    }

    /**
     * Performs a partial refund on an invoice, part payment or mobile purchase.
     *
     * <b>Note</b>:<br>
     * You need to call {@link Klarna::addArtNo()} first.<br>
     *
     * @example docs/examples/creditPart.php How to partially credit an invoice.
     *
     * @param string $invNo  Invoice number.
     * @param string $credNo Credit number.
     *
     * @see Klarna::addArtNo()
     *
     * @throws Exception\KlarnaException
     *
     * @return string Invoice number.
     */
    public function creditPart($invNo, $credNo = '')
    {
        $this->_checkInvNo($invNo);
        $this->_checkCredNo($credNo);

        if ($this->goodsList === null || empty($this->goodsList)) {
            $this->_checkArtNos($this->artNos);
        }

        //function activate_part_digest
        $string = $this->_eid.':'.$invNo.':';

        if ($this->artNos !== null && !empty($this->artNos)) {
            foreach ($this->artNos as $artNo) {
                $string .= $artNo['artno'].':'.$artNo['qty'].':';
            }
        }

        $digestSecret = self::digest($string.$this->_secret);
        //end activate_part_digest

        $paramList = array(
            $this->_eid,
            $invNo,
            $this->artNos,
            $credNo,
            $digestSecret,
        );

        if ($this->goodsList !== null && !empty($this->goodsList)) {
            $paramList[] = 0;
            $paramList[] = $this->goodsList;
        }

        $this->artNos = array();

        self::printDebug('credit_part', $paramList);

        return $this->xmlrpc_call('credit_part', $paramList);
    }

    /**
     * Changes the quantity of a specific item in a passive invoice.
     *
     * @param string $invNo Invoice number.
     * @param string $artNo Article number.
     * @param int    $qty   Quantity of specified article.
     *
     * @throws Exception\KlarnaException
     *
     * @return string Invoice number.
     */
    public function updateGoodsQty($invNo, $artNo, $qty)
    {
        $this->_checkInvNo($invNo);
        $this->_checkQty($qty);
        $this->_checkArtNo($artNo);

        $digestSecret = self::digest(
            self::colon($invNo, $artNo, $qty, $this->_secret)
        );

        $paramList = array(
            $this->_eid,
            $digestSecret,
            $invNo,
            $artNo,
            $qty,
        );

        self::printDebug('update_goods_qty', $paramList);

        return $this->xmlrpc_call('update_goods_qty', $paramList);
    }

    /**
     * Changes the amount of a fee (e.g. the invoice fee) in a passive invoice.
     *
     * <b>Type can be</b>:<br>
     * {@link Flags::IS_SHIPMENT}<br>
     * {@link Flags::IS_HANDLING}<br>
     *
     * @param string $invNo     Invoice number.
     * @param int    $type      Charge type.
     * @param int    $newAmount The new amount for the charge.
     *
     * @throws Exception\KlarnaException
     *
     * @return string Invoice number.
     */
    public function updateChargeAmount($invNo, $type, $newAmount)
    {
        $this->_checkInvNo($invNo);
        $this->_checkInt($type, 'type');
        $this->_checkAmount($newAmount);

        if ($type === Flags::IS_SHIPMENT) {
            $type = 1;
        } elseif ($type === Flags::IS_HANDLING) {
            $type = 2;
        }

        $digestSecret = self::digest(
            self::colon($invNo, $type, $newAmount, $this->_secret)
        );

        $paramList = array(
            $this->_eid,
            $digestSecret,
            $invNo,
            $type,
            $newAmount,
        );

        self::printDebug('update_charge_amount', $paramList);

        return $this->xmlrpc_call('update_charge_amount', $paramList);
    }

    /**
     * The invoice_address function is used to retrieve the address of a
     * purchase.
     *
     * @param string $invNo Invoice number.
     *
     * @throws Exception\KlarnaException
     *
     * @return Address
     */
    public function invoiceAddress($invNo)
    {
        $this->_checkInvNo($invNo);

        $digestSecret = self::digest(
            self::colon($this->_eid, $invNo, $this->_secret)
        );
        $paramList = array(
            $this->_eid,
            $invNo,
            $digestSecret,
        );

        self::printDebug('invoice_address', $paramList);

        $result = $this->xmlrpc_call('invoice_address', $paramList);

        $addr = new Address();
        if (strlen($result[0]) > 0) {
            $addr->isCompany = false;
            $addr->setFirstName($result[0]);
            $addr->setLastName($result[1]);
        } else {
            $addr->isCompany = true;
            $addr->setCompanyName($result[1]);
        }
        $addr->setStreet($result[2]);
        $addr->setZipCode($result[3]);
        $addr->setCity($result[4]);
        $addr->setCountry($result[5]);

        return $addr;
    }

    /**
     * Retrieves the amount of a specific goods from a purchase.
     *
     * <b>Note</b>:<br>
     * You need to call {@link Klarna::addArtNo()} first.<br>
     *
     * @param string $invNo Invoice number.
     *
     * @see Klarna::addArtNo()
     *
     * @throws Exception\KlarnaException
     *
     * @return float The amount of the goods.
     */
    public function invoicePartAmount($invNo)
    {
        $this->_checkInvNo($invNo);
        $this->_checkArtNos($this->artNos);

        //function activate_part_digest
        $string = $this->_eid.':'.$invNo.':';
        foreach ($this->artNos as $artNo) {
            $string .= $artNo['artno'].':'.$artNo['qty'].':';
        }
        $digestSecret = self::digest($string.$this->_secret);
        //end activate_part_digest

        $paramList = array(
            $this->_eid,
            $invNo,
            $this->artNos,
            $digestSecret,
        );
        $this->artNos = array();

        self::printDebug('invoice_part_amount', $paramList);

        $result = $this->xmlrpc_call('invoice_part_amount', $paramList);

        return $result / 100;
    }

    /**
     * Returns the current order status for a specific reservation or invoice.
     * Use this when {@link Klarna::addTransaction()} or
     * {@link Klarna::reserveAmount()} returns a {@link Flags::PENDING}
     * status.
     *
     * <b>Order status can be</b>:<br>
     * {@link Flags::ACCEPTED}<br>
     * {@link Flags::PENDING}<br>
     * {@link Flags::DENIED}<br>
     *
     * @example docs/examples/checkOrderStatus.php How to check a order status.
     *
     * @param string $id   Reservation number or invoice number.
     * @param int    $type 0 if $id is an invoice or reservation, 1 for order id
     *
     * @throws Exception\KlarnaException
     *
     * @return string The order status.
     */
    public function checkOrderStatus($id, $type = 0)
    {
        $this->_checkArgument($id, 'id');

        $this->_checkInt($type, 'type');
        if ($type !== 0 && $type !== 1) {
            throw new Exception\InvalidTypeException(
                'type',
                '0 or 1'
            );
        }

        $digestSecret = self::digest(
            self::colon($this->_eid, $id, $this->_secret)
        );
        $paramList = array(
            $this->_eid,
            $digestSecret,
            $id,
            $type,
        );

        self::printDebug('check_order_status', $paramList);

        return $this->xmlrpc_call('check_order_status', $paramList);
    }

    /**
     * Retrieves a list of all the customer numbers associated with the
     * specified pno.
     *
     * @param string $pno      Social security number, Personal number, ...
     * @param int    $encoding {@link Encoding PNO Encoding} constant.
     *
     * @throws Exception\KlarnaException
     *
     * @return array An array containing all customer numbers associated
     *               with that pno.
     */
    public function getCustomerNo($pno, $encoding = null)
    {
        //Get the PNO/SSN encoding constant.
        if ($encoding === null) {
            $encoding = $this->getPNOEncoding();
        }
        $this->_checkPNO($pno, $encoding);

        $digestSecret = self::digest(
            self::colon($this->_eid, $pno, $this->_secret)
        );
        $paramList = array(
            $pno,
            $this->_eid,
            $digestSecret,
            $encoding,
        );

        self::printDebug('get_customer_no', $paramList);

        return $this->xmlrpc_call('get_customer_no', $paramList);
    }

    /**
     * Associates a pno with a customer number when you want to make future
     * purchases without a pno.
     *
     * @param string $pno      Social security number, Personal number, ...
     * @param string $custNo   The customer number.
     * @param int    $encoding {@link Encoding PNO Encoding} constant.
     *
     * @throws Exception\KlarnaException
     *
     * @return bool True, if the customer number was associated with the pno.
     */
    public function setCustomerNo($pno, $custNo, $encoding = null)
    {
        //Get the PNO/SSN encoding constant.
        if ($encoding === null) {
            $encoding = $this->getPNOEncoding();
        }
        $this->_checkPNO($pno, $encoding);

        $this->_checkArgument($custNo, 'custNo');

        $digestSecret = self::digest(
            self::colon($this->_eid, $pno, $custNo, $this->_secret)
        );
        $paramList = array(
            $pno,
            $custNo,
            $this->_eid,
            $digestSecret,
            $encoding,
        );

        self::printDebug('set_customer_no', $paramList);

        $result = $this->xmlrpc_call('set_customer_no', $paramList);

        return $result == 'ok';
    }

    /**
     * Removes a customer number from association with a pno.
     *
     * @param string $custNo The customer number.
     *
     * @throws Exception\KlarnaException
     *
     * @return bool True, if the customer number association was removed.
     */
    public function removeCustomerNo($custNo)
    {
        $this->_checkArgument($custNo, 'custNo');

        $digestSecret = self::digest(
            self::colon($this->_eid, $custNo, $this->_secret)
        );

        $paramList = array(
            $custNo,
            $this->_eid,
            $digestSecret,
        );

        self::printDebug('remove_customer_no', $paramList);

        $result = $this->xmlrpc_call('remove_customer_no', $paramList);

        return $result == 'ok';
    }

    /**
     * Get the PClasses from Klarna Online.<br>
     * You are only allowed to call this once, or once per update of PClasses
     * in KO.<br>.
     *
     * <b>Note</b>:<br>
     * You should store these in a DB of choice for later use.
     *
     * @example docs/examples/getPClasses.php How to get your estore PClasses.
     *
     * @param string|int $country  {@link Country Country} constant,
     *                             or two letter code.
     * @param mixed      $language {@link Language Language} constant,
     *                             or two letter code.
     * @param mixed      $currency {@link Currency Currency} constant,
     *                             or three letter code.
     *
     * @throws Exception\KlarnaException
     *
     * @return PClass[] A list of pclasses.
     */
    public function getPClasses($country = null, $language = null, $currency = null)
    {
        extract(
            $this->getLocale($country, $language, $currency),
            EXTR_OVERWRITE
        );

        $this->_checkConfig();

        $digestSecret = self::digest(
            $this->_eid.':'.$currency.':'.$this->_secret
        );

        $paramList = array(
            $this->_eid,
            $currency,
            $digestSecret,
            $country,
            $language,
        );

        self::printDebug('get_pclasses array', $paramList);

        $result = $this->xmlrpc_call('get_pclasses', $paramList);

        self::printDebug('get_pclasses result', $result);

        $pclasses = array();

        foreach ($result as $data) {
            $pclass = new PClass();
            $pclass->setEid($this->_eid);
            $pclass->setId($data[0]);
            $pclass->setDescription(self::num_htmlentities($data[1]));
            $pclass->setMonths($data[2]);
            $pclass->setStartFee($data[3] / 100);
            $pclass->setInvoiceFee($data[4] / 100);
            $pclass->setInterestRate($data[5] / 100);
            $pclass->setMinAmount($data[6] / 100);
            $pclass->setCountry($data[7]);
            $pclass->setType($data[8]);
            $pclass->setExpire(strtotime($data[9]));

            $pclasses[] = $pclass;
        }

        return $pclasses;
    }

    /**
     * Returns the cheapest, per month, PClass related to the specified sum.
     *
     * <b>Note</b>: This choose the cheapest PClass for the current country.<br>
     * {@link Klarna::setCountry()}
     *
     * <b>Flags can be</b>:<br>
     * {@link Flags::CHECKOUT_PAGE}<br>
     * {@link Flags::PRODUCT_PAGE}<br>
     *
     * @param float    $sum      The product cost, or total sum of the cart.
     * @param int      $flags    Which type of page the info will be displayed on.
     * @param PClass[] $pclasses The list of pclasses to search in.
     *
     * @throws Exception\KlarnaException
     *
     * @return PClass or false if none was found.
     */
    public function getCheapestPClass($sum, $flags, $pclasses)
    {
        if (!is_numeric($sum)) {
            throw new Exception\InvalidPriceException($sum);
        }

        if (!is_numeric($flags)
            || !in_array(
                $flags,
                array(
                    Flags::CHECKOUT_PAGE, Flags::PRODUCT_PAGE, )
            )
        ) {
            throw new Exception\InvalidTypeException(
                'flags',
                Flags::CHECKOUT_PAGE.' or '.Flags::PRODUCT_PAGE
            );
        }

        $lowest_pp = $lowest = false;

        foreach ($pclasses as $pclass) {
            $lowest_payment = Calc::get_lowest_payment_for_account(
                $pclass->getCountry()
            );
            if ($pclass->getType() < 2 && $sum >= $pclass->getMinAmount()) {
                $minpay = Calc::calc_monthly_cost(
                    $sum,
                    $pclass,
                    $flags
                );

                if ($minpay < $lowest_pp || $lowest_pp === false) {
                    if ($pclass->getType() == PClass::ACCOUNT
                        || $minpay >= $lowest_payment
                    ) {
                        $lowest_pp = $minpay;
                        $lowest = $pclass;
                    }
                }
            }
        }

        return $lowest;
    }

    /**
     * Creates a XMLRPC call with specified XMLRPC method and parameters from array.
     *
     * @param string $method XMLRPC method.
     * @param array  $array  XMLRPC parameters.
     *
     * @throws Exception\KlarnaException
     *
     * @return mixed
     */
    protected function xmlrpc_call($method, $array)
    {
        $this->_checkConfig();

        if (!isset($method) || !is_string($method)) {
            throw new Exception\InvalidTypeException('method', 'string');
        }
        if ($array === null || count($array) === 0) {
            throw new Exception\KlarnaException('Parameterlist is empty or null!', 50067);
        }
        if (self::$disableXMLRPC) {
            return true;
        }
        try {
            /*
             * Disable verifypeer for CURL, so below error is avoided.
             * CURL error: SSL certificate problem, verify that the CA
             * cert is OK.
             * Details: error:14090086:SSL
             * routines:SSL3_GET_SERVER_CERTIFICATE:certificate verify failed (#8)
             */
            $this->xmlrpc->verifypeer = false;

            $timestart = microtime(true);

            //Create the XMLRPC message.
            $msg = new \xmlrpcmsg($method);
            $params = array_merge(
                array(
                    $this->PROTO, $this->VERSION,
                ),
                $array
            );

            $msg = new \xmlrpcmsg($method);
            foreach ($params as $p) {
                if (!$msg->addParam(
                    php_xmlrpc_encode($p, array('extension_api'))
                )
                ) {
                    throw new Exception\KlarnaException(
                        'Failed to add parameters to XMLRPC message.',
                        50068
                    );
                }
            }

            //Send the message.
            $selectDateTime = microtime(true);
            if (self::$xmlrpcDebug) {
                $this->xmlrpc->setDebug(2);
            }
            $xmlrpcresp = $this->xmlrpc->send($msg);

            //Calculate time and selectTime.
            $timeend = microtime(true);
            $time = (int) (($selectDateTime - $timestart) * 1000);
            $selectTime = (int) (($timeend - $timestart) * 1000);

            $status = $xmlrpcresp->faultCode();

            if ($status !== 0) {
                throw new Exception\KlarnaException($xmlrpcresp->faultString(), $status);
            }

            return php_xmlrpc_decode($xmlrpcresp->value());
        } catch (\Exception\KlarnaException $e) {
            //Otherwise it is caught below, and rethrown.
            throw $e;
        } catch (\Exception $e) {
            throw new Exception\KlarnaException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Create a new CurlTransport.
     *
     * @return CurlTransport New CurlTransport instance
     */
    public function createTransport()
    {
        return new CurlTransport(
            new CurlHandle(),
            isset($this->config['timeout']) ? intval($this->config['timeout']) : 10
        );
    }

    /**
     * Perform a checkout service request.
     *
     * @example docs/examples/checkoutService.php How to use the checkout service.
     *
     * @param int|float $price    The total price for the checkout including VAT.
     * @param string    $currency ISO 4217 Currency Code
     * @param string    $locale   Specify what locale is used by the checkout.
     *                            ISO 639 language and ISO 3166-1 country separated
     *                            by underscore. Example: sv_SE
     * @param string    $country  (Optional) Specify what ISO 3166-1 country to use
     *                            for fetching payment methods. If not specified
     *                            the locale country will be used.
     *
     * @throws RuntimeException If the curl extension is not loaded
     *
     * @return CheckoutServiceResponse Response with payment methods
     */
    public function checkoutService($price, $currency, $locale, $country = null)
    {
        $this->_checkAmount($price);

        $params = array(
            'merchant_id' => $this->config['eid'],
            'total_price' => $price,
            'currency' => strtoupper($currency),
            'locale' => strtolower($locale),
        );

        if ($country !== null) {
            $params['country'] = $country;
        }

        return $this->createTransport()->send(
            new CheckoutServiceRequest($this->config, $params)
        );
    }

    /**
     * Removes all relevant order/customer data from the internal structure.
     */
    public function clear()
    {
        $this->goodsList = null;
        $this->comment = '';

        $this->billing = null;
        $this->shipping = null;

        $this->shipInfo = array();
        $this->extraInfo = array();
        $this->bankInfo = array();
        $this->incomeInfo = array();
        $this->activateInfo = array();

        $this->reference = '';
        $this->reference_code = '';

        $this->orderid[0] = '';
        $this->orderid[1] = '';

        $this->artNos = array();
    }

    /**
     * Implodes parameters with delimiter ':'.
     * Null and "" values are ignored by the colon function to
     * ensure there is not several colons in succession.
     *
     * @return string Colon separated string.
     */
    public static function colon()
    {
        $args = func_get_args();

        return implode(
            ':',
            array_filter(
                $args,
                array('self', 'filterDigest')
            )
        );
    }

    /**
     * Implodes parameters with delimiter '|'.
     *
     * @return string Pipe separated string.
     */
    public static function pipe()
    {
        $args = func_get_args();

        return implode('|', $args);
    }

    /**
     * Check if the value has a string length larger than 0.
     *
     * @param mixed $value The value to check.
     *
     * @return bool True if string length is larger than 0
     */
    public static function filterDigest($value)
    {
        return strlen(strval($value)) > 0;
    }

    /**
     * Creates a digest hash from the inputted string,
     * and the specified or the preferred hash algorithm.
     *
     * @param string $data Data to be hashed.
     * @param string $hash hash algoritm to use
     *
     * @throws Exception\KlarnaException
     *
     * @return string Base64 encoded hash.
     */
    public static function digest($data, $hash = null)
    {
        if ($hash === null) {
            $preferred = array(
                'sha512',
                'sha384',
                'sha256',
                'sha224',
                'md5',
            );

            $hashes = array_intersect($preferred, hash_algos());

            if (count($hashes) == 0) {
                throw new Exception\KlarnaException(
                    'No available hash algorithm supported!'
                );
            }
            $hash = array_shift($hashes);
        }
        self::printDebug('digest() using hash', $hash);

        return base64_encode(pack('H*', hash($hash, $data)));
    }

    /**
     * Converts special characters to numeric htmlentities.
     *
     * <b>Note</b>:<br>
     * If supplied string is encoded with UTF-8, o umlaut ("ö") will become two
     * HTML entities instead of one.
     *
     * @param string $str String to be converted.
     *
     * @return string String converted to numeric HTML entities.
     */
    public static function num_htmlentities($str)
    {
        $charset = 'ISO-8859-1';

        if (!self::$htmlentities) {
            self::$htmlentities = array();
            $table = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES, $charset);
            foreach ($table as $char => $entity) {
                self::$htmlentities[$entity] = '&#'.ord($char).';';
            }
        }

        return str_replace(
            array_keys(
                self::$htmlentities
            ),
            self::$htmlentities,
            htmlentities($str, ENT_COMPAT | ENT_HTML401, $charset)
        );
    }

    /**
     * Prints debug information if debug is set to true.
     * $msg is used as header/footer in the output.
     *
     * If FirePHP is available it will be used instead of
     * dumping the debug info into the document.
     *
     * It uses print_r and encapsulates it in HTML/XML comments.
     * (<!-- -->)
     *
     * @param string $msg   Debug identifier, e.g. "my array".
     * @param mixed  $mixed Object, type, etc, to be debugged.
     */
    public static function printDebug($msg, $mixed)
    {
        if (self::$debug) {
            if (class_exists('FB', false)) {
                FB::send($mixed, $msg);
            } else {
                echo "\n<!-- ".$msg.": \n";
                print_r($mixed);
                echo "\n end ".$msg." -->\n";
            }
        }
    }

    /**
     * Checks/fixes so the invNo input is valid.
     *
     * @param string $invNo Invoice number.
     *
     * @throws Exception\KlarnaException
     */
    private function _checkInvNo(&$invNo)
    {
        if (!isset($invNo)) {
            throw new Exception\ArgumentNotSetException('Invoice number');
        }
        if (!is_string($invNo)) {
            $invNo = strval($invNo);
        }
        if (strlen($invNo) == 0) {
            throw new Exception\ArgumentNotSetException('Invoice number');
        }
    }

    /**
     * Checks/fixes so the quantity input is valid.
     *
     * @param int $qty Quantity.
     *
     * @throws Exception\KlarnaException
     */
    private function _checkQty(&$qty)
    {
        if (!isset($qty)) {
            throw new Exception\ArgumentNotSetException('Quantity');
        }
        if (is_numeric($qty) && !is_int($qty)) {
            $qty = intval($qty);
        }
        if (!is_int($qty)) {
            throw new Exception\InvalidTypeException('Quantity', 'integer');
        }
    }

    /**
     * Checks/fixes so the artTitle input is valid.
     *
     * @param string $artTitle Article title.
     *
     * @throws Exception\KlarnaException
     */
    private function _checkArtTitle(&$artTitle)
    {
        if (!is_string($artTitle)) {
            $artTitle = strval($artTitle);
        }
        if (!isset($artTitle) || strlen($artTitle) == 0) {
            throw new Exception\ArgumentNotSetException('artTitle', 50059);
        }
    }

    /**
     * Checks/fixes so the artNo input is valid.
     *
     * @param int|string $artNo Article number.
     *
     * @throws Exception\KlarnaException
     */
    private function _checkArtNo(&$artNo)
    {
        if (is_numeric($artNo) && !is_string($artNo)) {
            //Convert artNo to string if integer.
            $artNo = strval($artNo);
        }
        if (!isset($artNo) || strlen($artNo) == 0 || (!is_string($artNo))) {
            throw new Exception\ArgumentNotSetException('artNo');
        }
    }

    /**
     * Checks/fixes so the credNo input is valid.
     *
     * @param string $credNo Credit number.
     *
     * @throws Exception\KlarnaException
     */
    private function _checkCredNo(&$credNo)
    {
        if (!isset($credNo)) {
            throw new Exception\ArgumentNotSetException('Credit number');
        }

        if ($credNo === false || $credNo === null) {
            $credNo = '';
        }
        if (!is_string($credNo)) {
            $credNo = strval($credNo);
            if (!is_string($credNo)) {
                throw new Exception\InvalidTypeException('Credit number', 'string');
            }
        }
    }

    /**
     * Checks so that artNos is an array and is not empty.
     *
     * @param array $artNos Array from {@link Klarna::addArtNo()}.
     *
     * @throws Exception\KlarnaException
     */
    private function _checkArtNos(&$artNos)
    {
        if (!is_array($artNos)) {
            throw new Exception\InvalidTypeException('artNos', 'array');
        }
        if (empty($artNos)) {
            throw new Exception\KlarnaException('ArtNo array is empty!', 50064);
        }
    }

    /**
     * Checks/fixes so the integer input is valid.
     *
     * @param int    $int   {@link Flags flags} constant.
     * @param string $field Name of the field.
     *
     * @throws Exception\KlarnaException
     */
    private function _checkInt(&$int, $field)
    {
        if (!isset($int)) {
            throw new Exception\ArgumentNotSetException($field);
        }
        if (is_numeric($int) && !is_int($int)) {
            $int = intval($int);
        }
        if (!is_numeric($int) || !is_int($int)) {
            throw new Exception\InvalidTypeException($field, 'integer');
        }
    }

    /**
     * Checks/fixes so the VAT input is valid.
     *
     * @param float $vat VAT.
     *
     * @throws Exception\KlarnaException
     */
    private function _checkVAT(&$vat)
    {
        if (!isset($vat)) {
            throw new Exception\ArgumentNotSetException('VAT');
        }
        if (is_numeric($vat) && (!is_int($vat) || !is_float($vat))) {
            $vat = floatval($vat);
        }
        if (!is_numeric($vat) || (!is_int($vat) && !is_float($vat))) {
            throw new Exception\InvalidTypeException('VAT', 'integer or float');
        }
    }

    /**
     * Checks/fixes so the amount input is valid.
     *
     * @param int $amount Amount.
     *
     * @throws Exception\KlarnaException
     */
    private function _checkAmount(&$amount)
    {
        if (!isset($amount)) {
            throw new Exception\ArgumentNotSetException('Amount');
        }
        if (is_numeric($amount)) {
            $this->_fixValue($amount);
        }
        if (is_numeric($amount) && !is_int($amount)) {
            $amount = intval($amount);
        }
        if (!is_numeric($amount) || !is_int($amount)) {
            throw new Exception\InvalidTypeException('amount', 'integer');
        }
    }

    /**
     * Checks/fixes so the price input is valid.
     *
     * @param int $price Price.
     *
     * @throws Exception\KlarnaException
     */
    private function _checkPrice(&$price)
    {
        if (!isset($price)) {
            throw new Exception\ArgumentNotSetException('Price');
        }
        if (is_numeric($price)) {
            $this->_fixValue($price);
        }
        if (is_numeric($price) && !is_int($price)) {
            $price = intval($price);
        }
        if (!is_numeric($price) || !is_int($price)) {
            throw new Exception\InvalidTypeException('Price', 'integer');
        }
    }

    /**
     * Multiplies value with 100 and rounds it.
     * This fixes value/price/amount inputs so that KO can handle them.
     *
     * @param float $value value
     */
    private function _fixValue(&$value)
    {
        $value = round($value * 100);
    }

    /**
     * Checks/fixes so the discount input is valid.
     *
     * @param float $discount Discount amount.
     *
     * @throws Exception\KlarnaException
     */
    private function _checkDiscount(&$discount)
    {
        if (!isset($discount)) {
            throw new Exception\ArgumentNotSetException('Discount');
        }
        if (is_numeric($discount)
            && (!is_int($discount) || !is_float($discount))
        ) {
            $discount = floatval($discount);
        }

        if (!is_numeric($discount)
            || (!is_int($discount) && !is_float($discount))
        ) {
            throw new Exception\InvalidTypeException('Discount', 'integer or float');
        }
    }

    /**
     * Checks/fixes so that the estoreOrderNo input is valid.
     *
     * @param string $estoreOrderNo Estores order number.
     *
     * @throws Exception\KlarnaException
     */
    private function _checkEstoreOrderNo(&$estoreOrderNo)
    {
        if (!isset($estoreOrderNo)) {
            throw new Exception\ArgumentNotSetException('Order number');
        }

        if (!is_string($estoreOrderNo)) {
            $estoreOrderNo = strval($estoreOrderNo);
            if (!is_string($estoreOrderNo)) {
                throw new Exception\InvalidTypeException('Order number', 'string');
            }
        }
    }

    /**
     * Checks/fixes to the PNO/SSN input is valid.
     *
     * @param string $pno Personal number, social security  number, ...
     * @param int    $enc {@link Encoding PNO Encoding} constant.
     *
     * @throws Exception\KlarnaException
     */
    private function _checkPNO(&$pno, $enc)
    {
        if (!$pno) {
            throw new Exception\ArgumentNotSetException('PNO/SSN');
        }

        if (!Encoding::checkPNO($pno)) {
            throw new Exception\InvalidPNOException();
        }
    }

    /**
     * Checks/fixes to the country input is valid.
     *
     * @param int $country {@link Country Country} constant.
     *
     * @throws Exception\KlarnaException
     */
    private function _checkCountry(&$country)
    {
        if (!isset($country)) {
            throw new Exception\ArgumentNotSetException('Country');
        }
        if (is_numeric($country) && !is_int($country)) {
            $country = intval($country);
        }
        if (!is_numeric($country) || !is_int($country)) {
            throw new Exception\InvalidTypeException('Country', 'integer');
        }
    }

    /**
     * Checks/fixes to the language input is valid.
     *
     * @param int $language {@link Language Language} constant.
     *
     * @throws Exception\KlarnaException
     */
    private function _checkLanguage(&$language)
    {
        if (!isset($language)) {
            throw new Exception\ArgumentNotSetException('Language');
        }
        if (is_numeric($language) && !is_int($language)) {
            $language = intval($language);
        }
        if (!is_numeric($language) || !is_int($language)) {
            throw new Exception\InvalidTypeException('Language', 'integer');
        }
    }

    /**
     * Checks/fixes to the currency input is valid.
     *
     * @param int $currency {@link Currency Currency} constant.
     *
     * @throws Exception\KlarnaException
     */
    private function _checkCurrency(&$currency)
    {
        if (!isset($currency)) {
            throw new Exception\ArgumentNotSetException('Currency');
        }
        if (is_numeric($currency) && !is_int($currency)) {
            $currency = intval($currency);
        }
        if (!is_numeric($currency) || !is_int($currency)) {
            throw new Exception\InvalidTypeException('Currency', 'integer');
        }
    }

    /**
     * Checks/fixes so no/number is a valid input.
     *
     * @param int $no Number.
     *
     * @throws Exception\KlarnaException
     */
    private function _checkNo(&$no)
    {
        if (!isset($no)) {
            throw new Exception\ArgumentNotSetException('no');
        }
        if (is_numeric($no) && !is_int($no)) {
            $no = intval($no);
        }
        if (!is_numeric($no) || !is_int($no) || $no <= 0) {
            throw new Exception\InvalidTypeException('no', 'integer > 0');
        }
    }

    /**
     * Checks/fixes so reservation number is a valid input.
     *
     * @param string $rno Reservation number.
     *
     * @throws Exception\KlarnaException
     */
    private function _checkRNO(&$rno)
    {
        if (!is_string($rno)) {
            $rno = strval($rno);
        }
        if (strlen($rno) == 0) {
            throw new Exception\ArgumentNotSetException('RNO');
        }
    }

    /**
     * Checks/fixes so that reference/refCode are valid.
     *
     * @param string $reference Reference string.
     * @param string $refCode   Reference code.
     *
     * @throws Exception\KlarnaException
     */
    private function _checkRef(&$reference, &$refCode)
    {
        if (!is_string($reference)) {
            $reference = strval($reference);
            if (!is_string($reference)) {
                throw new Exception\InvalidTypeException('Reference', 'string');
            }
        }

        if (!is_string($refCode)) {
            $refCode = strval($refCode);
            if (!is_string($refCode)) {
                throw new Exception\InvalidTypeException('Reference code', 'string');
            }
        }
    }

    /**
     * Checks/fixes so that the OCR input is valid.
     *
     * @param string $ocr OCR number.
     *
     * @throws Exception\KlarnaException
     */
    private function _checkOCR(&$ocr)
    {
        if (!is_string($ocr)) {
            $ocr = strval($ocr);
            if (!is_string($ocr)) {
                throw new Exception\InvalidTypeException('OCR', 'string');
            }
        }
    }

    /**
     * Check so required argument is supplied.
     *
     * @param string $argument argument to check
     * @param string $name     name of argument
     *
     * @throws Exception\ArgumentNotSetException
     */
    private function _checkArgument($argument, $name)
    {
        if (!is_string($argument)) {
            $argument = strval($argument);
        }

        if (strlen($argument) == 0) {
            throw new Exception\ArgumentNotSetException($name);
        }
    }

    /**
     * Check so Locale settings (country, currency, language) are set.
     *
     * @throws Exception\KlarnaException
     */
    private function _checkLocale()
    {
        if (!is_int($this->_country)
            || !is_int($this->_language)
            || !is_int($this->_currency)
        ) {
            throw new Exception\InvalidLocaleException();
        }
    }

    /**
     * Checks wether a goodslist is set.
     *
     * @throws Exception\MissingGoodslistException
     */
    private function _checkGoodslist()
    {
        if (!is_array($this->goodsList) || empty($this->goodsList)) {
            throw new Exception\MissingGoodslistException();
        }
    }

    /**
     * Ensure the configuration is of the correct type.
     *
     * @param array|ArrayAccess|null $config an optional config to validate
     */
    private function _checkConfig($config = null)
    {
        if ($config === null) {
            $config = $this->config;
        }
        if (!($config instanceof \ArrayAccess)
            && !is_array($config)
        ) {
            throw new Exception\IncompleteConfigurationException();
        }
    }
}