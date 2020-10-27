<?php

/**
 * Device 2.0
 * Common Class for determining device
 *
 * @author Paul S. Clarke (8/17/2010 rewrite)
 * @author Raymondc@torzo.com (original version)
 *
 * @see http://www.mobile-phone-specs.com (for reference)
 * * to test add device=mobile to the query string
 */

class Device {

    // ************************************************************************
    // **** DEVICES ***********************************************************
    
    // there is a practical limit of 31 of these -- can we remove the devices? -paulc 8/17/2010
    const PC = 0;
    const IPAD = 1;
    const IPHONE = 2;
    const IPOD = 3;
    const BLACKBERRY = 4;
    const BBSTORM = 5;
    const ANDROID = 6;
    const PSP = 7;
    const PS3 = 8;
    const WII = 9;
    const DS = 10;
    const PALM = 11;
    const WEB_OS = 12;
    const NOKIA = 13;
    const SAMSUNG = 14;
    const OPERA_MINI = 15;
    const LG = 16;
    const WINDOWS_MOBILE = 17;
    const ZUNE = 18;
    const FEATURE_PHONE = 19;
    const SYMBIAN = 20;
    const OPERA = 21;
    const BLACKBERRY_TORCH = 22;
    const TV = 23;
    const WINDOWS_MOBILE_7 = 24;
    const GALAXY_TAB = 25;
    
    // this not only gives the name, but also specifies which name to return
    // (top to bottom) if more than one matched.
    private static $deviceName = array(
        self::OPERA_MINI => 'OperaMini',
        self::OPERA => 'Opera', // OPERAS GO FIRST -- they run on many devices and are crappier than the built-in browser
        self::TV => 'TV',
        self::IPAD => 'iPad',
        self::PC => 'PC',
        self::IPHONE => 'iPhone',
        self::IPOD => 'iPod',
        self::BLACKBERRY_TORCH => 'BlackBerryTorch',
        self::BBSTORM => 'BBStorm',
        self::BLACKBERRY => 'BlackBerry',
        self::GALAXY_TAB => 'Tab',
        self::ANDROID => 'Android',
        self::PSP => 'PSP',
        self::PS3 => 'PS3',
        self::WII => 'Wii',
        self::DS => 'DS',
        self::WEB_OS => 'webOS',
        self::PALM => 'Palm',
        self::NOKIA => 'Nokia',
        self::SAMSUNG => 'Samsung',
        self::LG => 'LG',
        self::WINDOWS_MOBILE_7 => 'WINM7',
        self::WINDOWS_MOBILE => 'WINM',
        self::ZUNE => 'Zune',
        self::FEATURE_PHONE => 'FeaturePhone',
        self::SYMBIAN => 'Symbian',
        );

    // list of devices we count as "mobile"
    private static $mobileDeviceList = array(self::OPERA_MINI, 
        self::BLACKBERRY, self::BLACKBERRY_TORCH, self::ANDROID, self::PALM, 
        self::PSP, self::DS, self::NOKIA, self::SAMSUNG, self::SYMBIAN, 
        self::WINDOWS_MOBILE, self::LG, self::FEATURE_PHONE, self::IPHONE, 
        self::WEB_OS, self::IPOD);

    // list of devices we count as "mobile"
    private static $tabletDeviceList = array(self::IPAD, self::GALAXY_TAB,
        );


    // ************************************************************************
    // **** INTERFACE *********************************************************


    /**
    * @param string $deviceName    a device name
    * @return mixed    one of the device constants or NULL if not found
    */
    public static function getConstFromName($deviceName) {
        foreach (self::$deviceName as $const => $name) {
            if (strtolower($deviceName) == strtolower($name)) {
                return $const;
            }
        }
        return null;
    }
	
    /**
    * @return string The name of the device (or best guess) in use.
    */
    public static function getName() {
        self::determineDevice();

        foreach (self::$deviceName as $const => $name) {
            if (self::isDevice($const)) {
                return $name;
            }
        }

        return '';  // no device name?
    }

    
    /**
    * @return The current user agent, or the one indicated by setAgent()
    */
    public static function getAgent() {
        if (!isset(self::$agent)) {
            self::$agent = (isset($_SERVER['HTTP_USER_AGENT']))? $_SERVER['HTTP_USER_AGENT'] : '';
        }        
        return self::$agent;
    }
    
    
    /**
    * @param string $agent A fake user agent string.
    * @return Boolean Returns TRUE for historical reasons.
    */
    public static function setAgent($agent) {
        self::$agent = $agent;

        // nullifying this will cause it to redetect
        self::$device = NULL;

        return TRUE;
    }


    /**
    * @return The test string, in lowercase, originally from $_REQUEST['device']
    */
    private static function getTest() {
        if (!isset(self::$testString)) {
            self::$testString = isset($_REQUEST['device']) ? strtolower($_REQUEST['device']) : '';
        }
        return self::$testString;
    }

    /**
    * @return Boolean TRUE if a test string was used, FALSE elsewise
    */
    private static function isUsingTestMode() {
        $str = self::getTest();
        return !empty($str);
    }
    
    /**
    * @param mixed $devices A device name, device const, or array of those.
    * @return TRUE if any of $devices are being tested.
    */
    private static function isTesting($devices) {

        return false; 
        // always return false, meaning: never respect the value of $_REQUEST['device'];
        // I'm doing this now because we've stopped using UrlInspector in GSE/IPVP's index.php
        // file, which had had the same effect on the user experience as this change in this file has
        //
        // - Joel 2010/Sep/30

        $found = FALSE;

        foreach ((array)$devices as $d) {
            if (!$found) {
                // figure out a string
                if (is_string($d)) {
                    $name = strtolower($d);
                }
                elseif (isset(self::$deviceName[$d])) {
                    $name = strtolower(self::$deviceName[$d]);
                }
                else {
                    $name = NULL;
                }

                if (!is_null($name)) {
                    $found |= (self::getTest() == $name);
                }
            }
        }
        
        return $found;
    }

    /**
    * @param string $string A string to find in the user agent.
    * @return Boolean TRUE if the string is in the user agent.
    */
    private static function findInUserAgent($string) {
        return strpos(strtolower(self::getAgent()), strtolower($string)) !== FALSE;
    }

    
    // ************************************************************************
    // **** FEATURE-DETERMINERS ***********************************************

    /**
    * isSmartphone
    *
    * This category includes phones generally in league with the iPhone (or
    * non-phones like the iPod Touch), which can browse real web pages and
    * watch videos.
    *
    * Generally, capabilities include Javascript and AJAX.
    *
    * The iPad is deliberately excluded.
    *
    * @return Boolean TRUE if this device fits in this category.
    */
    public static function isSmartphone() {
        return (self::isIphone() || self::isIpod() || self::isAndroid() ||
            self::isBlackBerryTorch() || self::isPalmPre());
    }

    /**
    * isLowQualityPhone
    *
    * This is a sieve term that collects mobile devices which are lacking
    * common features or for which we will always serve a low-grade tour.
    *
    * @return Boolean TRUE if this device fits in this category.
    */
    public static function isLowQualityPhone() {
        $lowquality = FALSE;

        // I put these on their own lines because some are complex statements -paul
        $lowquality |= self::isMobileOpera();
        $lowquality |= (self::isMobile() && self::isOpera());
        $lowquality |= self::isWindowsMobile();
        $lowquality |= self::isOldBlackBerry();

        return $lowquality;
    }

    /**
    * @return bool    TRUE if the user agent is known to support the HTML 5 video tag
    */
    public static function supportsHtml5Video() {
        return !self::findInUserAgent('Firefox') && !self::findInUserAgent('MSIE');
    }


    // ************************************************************************
    // **** DEVICES ***********************************************************
        
    /**
	* @return Boolean TRUE if this is a mobile device
	*/
	public static function isMobile() {	
        self::determineDevice();
        return self::$mobile;
	}

    /**
	* @return Boolean TRUE if this is a tablet device
	*/
	public static function isTablet() {	
        self::determineDevice();
        return self::$tablet;
	}
    
    /**
	* @return Boolean TRUE if this is NOT a mobile device
    */
	public static function isPC() {
        self::determineDevice();
        return self::isDevice(self::PC);
	}
	
    /**
	* @return Boolean TRUE if this is an iPhone
    */
	public static function isIphone() {
        self::determineDevice();
        return self::isDevice(self::IPHONE);
	}
    
    /**
	* @return Boolean TRUE if this is an iPad
    */
	public static function isIpad() {
        self::determineDevice();
        return self::isDevice(self::IPAD);
	}
    
    /**
	* @return Boolean TRUE if this is an iPad
    */
	public static function isGalaxyTab() {
        self::determineDevice();
        return self::isDevice(self::GALAXY_TAB);
	}

    /**
    * @return Boolean TRUE if this is any of a variety of tablets.
    */
    public static function isTabletDevice() {
        return (self::isIpad() || self::isGalaxyTab());
    }
	
    /**
	* @return Boolean TRUE if this is an iPod
    */
	public static function isIpod() {
        self::determineDevice();
        return self::isDevice(self::IPOD);
	}
	
    /**
	* @return Boolean TRUE if this is any Apple mobile device
    */
	public static function isAppleMobile() {
        return (self::isIphone() || self::isIpod());
	}
    
    /**
	* @return Boolean TRUE if this is a Blackberry
    */
	public static function isBlackBerry() {
        self::determineDevice();
        return self::isDevice(self::BLACKBERRY);
	}

    /**
    * @return bool    TRUE if this is a BlackBerry Torch (9800 series)
    */
    public static function isBlackBerryTorch() {
        self::determineDevice();
        return self::isDevice(self::BLACKBERRY_TORCH);
    }

    /**
    * @return Boolean TRUE if this is a BlackBerry, but not the good kind
    */
    public static function isOldBlackBerry() {
        return self::isBlackBerry() && !self::isBlackBerryTorch();
    }

    /**
	* @return Boolean TRUE if this is a Blackberry Storm 9530
    */
    public static function isBlackBerryStorm() {
        self::determineDevice();
        return self::isDevice(self::BBSTORM);
    }
    
    /**
	* @return Boolean TRUE if this device is running Opera
    */
    public static function isOpera() {
        self::determineDevice();
        return self::isDevice(self::OPERA);
    }
    
    /**
	* @return Boolean TRUE if this device is running Opera Mini
    */
    public static function isOperaMini() {
        self::determineDevice();
        return self::isDevice(self::OPERA_MINI);
    }
    
    /**
	* @return self::isOperaMini
    */
	public static function isMobileOpera() {
        return self::isOperaMini();
	}
   
    /**
	* @return Boolean TRUE if this device is running Android
    */
	public static function isAndroid() {
        self::determineDevice();
        return self::isDevice(self::ANDROID);
	}

    /**
	* @return Float Android OS version
    */
    public static function getAndroidVersion() {
        $matches = array();
        preg_match ( '/Android ([0-9]\.[0-9])/' , self::getAgent(), $matches);
        $version = (isset($matches[1])) ? (float) $matches[1] : 0;
        return $version;
    }
    
    /**
	* @return Boolean TRUE if this device is a Nokia
    */
	public static function isNokia() {
        self::determineDevice();
        return self::isDevice(self::NOKIA);
	}
       
    /**
	* @return Boolean TRUE if this device is a Samsung
    */
	public static function isSamsung() {
        self::determineDevice();
        return self::isDevice(self::SAMSUNG);
	}
          
    /**
	* @return Boolean TRUE if this device is an LG
    */
	public static function isLG() {
        self::determineDevice();
        return self::isDevice(self::LG);
	}
   
    /**
	* @return Boolean TRUE if this device is a Palm
    */
	public static function isPalm() {
        self::determineDevice();
        return self::isDevice(self::PALM);
	}

    /**
    * @return Boolean TRUE if this is Palm but not running webOS
    */
    public static function isOldPalm() {
        return self::isPalm() && !self::isWebOs();
    }

    /**
	* @return Boolean TRUE if this device is running webOS
    */
    public static function isWebOs() {
        self::determineDevice();
        return self::isDevice(self::WEB_OS);
    }
    
    /**
    * This actually maps to isWebOs(), and includes the Pixi.
    *
	* @return Boolean TRUE if this device is running webOS
    */
	public static function isPalmPre() {
        return self::isWebOs();
	}
    
    /**
	* @return Boolean TRUE if this device is a PSP
    */
	public static function isPSP() {
        self::determineDevice();
        return self::isDevice(self::PSP);
	}
    
    /**
	* @return Boolean TRUE if this device is a PS3
    */
	public static function isPS3() {
        self::determineDevice();
        return self::isDevice(self::PS3);
	}
    
    /**
	* @return Boolean TRUE if this device is a Wii
    */
	public static function isWii() {
        self::determineDevice();
        return self::isDevice(self::WII);
	}
    
    /**
	* @return Boolean TRUE if this device is a DS
    */
	public static function isDS() {
        self::determineDevice();
        return self::isDevice(self::DS);
	}
    
    /**
	* @return Boolean TRUE if this device is running Windows Mobile
    */
	public static function isWindowsMobile() {
        self::determineDevice();
        return self::isDevice(self::WINDOWS_MOBILE);
	}
    
    /**
	* @return Boolean TRUE if this device is running Windows Mobile
    */
	public static function isWindowsMobile7() {
        self::determineDevice();
        return self::isDevice(self::WINDOWS_MOBILE_7);
	}
    
    /**
	* @return Boolean TRUE if this device is a Zune
    */
	public static function isZune() {
        self::determineDevice();
        return self::isDevice(self::ZUNE);
	}
    
    /**
	* @return Boolean TRUE if this device is a TV (e.g. GoogleTV)
    */
	public static function isTv() {
        self::determineDevice();
        return self::isDevice(self::TV);
	}
    
    /**
	* @return Boolean TRUE if this device is running Symbian
    */
	public static function isSymbian() {
        self::determineDevice();
        return self::isDevice(self::SYMBIAN);
	}
    
    /**
	* @return Boolean TRUE if this is one of many weird mobile user agents
    */
    public static function isBadMobileDevice() {
        self::determineDevice();
        return self::isDevice(self::FEATURE_PHONE);
    }

    /**
    * Returns the version of Apple's iOS the device is running.
    * If you don't request $asString, it returns as a decimal of the major
    * and minor version.
    *
    * @param Boolean $asString Return as a string with all 3 version parts (default: FALSE)
    * @return mixed The iOS version or NULL if this is not an iOS device.
    */
    public static function getIosVersion($asString = FALSE) {
        if (preg_match('/(iPhone|iPod|iPad); U; (CPU )?(iPhone|iPod|iPhone)? ?i?(OS)? (\d+)_(\d+)_?(\d*)/', self::getAgent(), $match)) {
            $base = "{$match[5]}.{$match[6]}";

            // 5, 6 and 7 are the version parts
            if ($asString) {
                if (!empty($match[7])) {
                    $base .= ".{$match[7]}";
                }
                return $base;
            }
            else {
                return (float)$base;
            }
        }
        else if (preg_match('/(iPhone|iPod|iPad); U; CPU like/', self::getAgent())) {
            return $asString ? (string)1 : 1;
        }

        return NULL;
    }


    
    // ************************************************************************
    // **** INTERNAL GETTERS **************************************************

    // ************************************************************************
    // **** IMPLEMENTATION DETAILS ********************************************

	const VIDEO_TYPE_PIF = 1;
    const VIDEO_TYPE_DVD = 2;
    
    private static $agent;  // string: the user agent (from the environment or set by setAgent)); fetch this ONLY using getAgent
    private static $mobile; // Boolean: TRUE if the device is mobile; fetch this ONLY using isMobile
    private static $tablet; // Boolean: TRUE if the device is mobile; fetch this ONLY using isTablet
    private static $testString; // string: retrieved from $_REQUEST['device']
    private static $device; // bitmap: A map of all detected devices.

    
    /**
    * Sets a specified device as active for isDevice().
    * @param mixed $const A device constant from this class.
    */
    private static function addDevice($const) {
        self::$device |= (1 << (int) $const);
    }

    /**
    * Removes a specified device as active from isDevice().
    * @param mixed $const A device constant from this class.
    */
    private static function removeDevice($const) {
        self::$device &= ~(1 << (int) $const);
    }

    /**
    * @param mixed $const A device constant from the top of this class.
    * @return Boolean TRUE if this device is the one requested.
    */
    private static function isDevice($const) {
        return (self::$device & (1 << (int)$const)) > 0;
    }


    /**
    * Determins if a provided device exists in the user agent, and marks it
    * otherwise.  This also checks devices requested in $_REQUEST['device'].
    *
    * @param mixed $const One of the device constants from the top of this class.
    * @param mixed $userAgent A string or array of strings to check in the user agent (optional).
    * @param mixed $testing A string or array of strings to check $_REQUEST for (optional).
    */
    private static function checkOneDevice($const, $userAgent = array(), $testing = array()) {
        $found = FALSE; // $found will short-circuit other tests to reduce work

        if (self::isTesting($const)) {
            $found = TRUE;
        }

        foreach ((array)$testing as $t) {
            if (!$found && self::isTesting($t)) {
                $found = TRUE;
            }
        }

        foreach ((array)$userAgent as $ua) {
            if (!$found && self::findInUserAgent($ua)) {
                $found = TRUE;
            }
        }

        // found?  mark it
        if ($found) {
            self::addDevice($const);
        }
    }

    
    /**
    * Master function for setting up the data used in this class.
    *
    * This examines the various environmental factors, and sets things up
    * for other getters.
    */
    private static function determineDevice() {
        if (!isset(self::$device)) {
            // initialize
            self::$device = 0;

            // check devices (this will flag them if they pass)
            self::checkOneDevice(self::IPAD, 'iPad');
            self::checkOneDevice(self::IPHONE, 'iPhone');
            self::checkOneDevice(self::IPOD, 'iPod');
            self::checkOneDevice(self::BLACKBERRY, 'BlackBerry', array('blackberry', 'bb', 'bbstorm', 'blackberry storm'));
            self::checkOneDevice(self::BBSTORM, 'BlackBerry9530', array('blackberry storm', 'bbstorm'));
            self::checkOneDevice(self::BLACKBERRY_TORCH, array('BlackBerry 9800'), array('bbtorch'));
            self::checkOneDevice(self::GALAXY_TAB, array('GT-P1000', 'SPH-P100', 'SHW-M180S', 
                'SCH-I800', 'SGH-I987', 'SGH-T849', 'SC-01C'));
            self::checkOneDevice(self::ANDROID, 'Android');
            self::checkOneDevice(self::OPERA, 'Opera');
            self::checkOneDevice(self::OPERA_MINI, array('Opera Mini', 'Opera Mobi'), array('OperaMini', 'Opera Mini'));
            self::checkOneDevice(self::NOKIA, 'Nokia');
            self::checkOneDevice(self::SAMSUNG, 'Samsung');
            self::checkOneDevice(self::LG, 'LG');
            self::checkOneDevice(self::WINDOWS_MOBILE_7, 'Windows Phone OS 7', array('wm7'));
            self::checkOneDevice(self::WINDOWS_MOBILE, array('Windows CE', 'Windows Phone', 'MSIEMobile'), array('winm', 'wm', 'windowsmobile'));
            self::checkOneDevice(self::PALM, 'Palm');
            self::checkOneDevice(self::WEB_OS, array('webOS', ' Pre/'), array('palmpre', 'Palm Pre'));
            self::checkOneDevice(self::PSP, array('PSP', 'PlayStation Portable'));
            self::checkOneDevice(self::PS3, 'PLAYSTATION 3');
            self::checkOneDevice(self::WII, 'Wii');
            self::checkOneDevice(self::DS, array('Nintendo DS', 'Nitro'));
            self::checkOneDevice(self::ZUNE, 'Zune');
            self::checkOneDevice(self::SYMBIAN, 'Symbian');
            self::checkOneDevice(self::TV, 'GoogleTV');

            // special cases:
                // PC is done below in the mobile section


            // sometimes iPo/ad includes iPhone?
            if (self::isDevice(self::IPHONE) && (self::isDevice(self::IPOD) || self::isDevice(self::IPAD))) {
                self::removeDevice(self::IPHONE);
            }


            // FEATURE_PHONE
            {
                $mobileAgents = array(
                    '1207', '3gso', '4thp', '501i', '502i', '503i', '504i', '505i', '506i',
                    '6310', '6590', '770s', '802s', 'a wa', 'acer', 'acs-', 'airn', 'alav',
                    'asus', 'attw', 'au-m', 'aur ', 'aus ', 'abac', 'acoo', 'aiko', 'alco',
                    'alca', 'amoi', 'anex', 'anny', 'anyw', 'aptu', 'arch', 'argo', 'bell',
                    'bird', 'bw-n', 'bw-u', 'beck', 'benq', 'bilb', 'blac', 'c55/', 'cdm-',
                    'chtm', 'capi', 'comp', 'cond', 'craw', 'dall', 'dbte', 'dc-s', 'dica',
                    'ds-d', 'ds12', 'dait', 'devi', 'dmob', 'doco', 'dopo', 'el49', 'erk0',
                    'esl8', 'ez40', 'ez60', 'ez70', 'ezos', 'ezze', 'elai', 'emul', 'eric',
                    'ezwa', 'fake', 'fly-', 'fly_', 'g-mo', 'g1 u', 'g560', 'gf-5', 'grun',
                    'gene', 'go.w', 'good', 'grad', 'hcit', 'hd-m', 'hd-p', 'hd-t', 'hei-',
                    'hp i', 'hpip', 'hs-c', 'htc ', 'htc-', 'htca', 'htcg', 'htcp', 'htcs',
                    'htct', 'htc_', 'haie', 'hita', 'huaw', 'hutc', 'i-20', 'i-go', 'i-ma',
                    'i230', 'iac-', 'iac/', 'ig01', 'im1k', 'inno', 'iris', 'jata', 'java',
                    'kddi', 'kgt/', 'kpt ', 'kwc-', 'klon', 'lexi', 'lg g', 'lg-a', 'lg-b',
                    'lg-c', 'lg-d', 'lg-f', 'lg-g', 'lg-k', 'lg-l', 'lg-m', 'lg-o', 'lg-p',
                    'lg-s', 'lg-t', 'lg-u', 'lg-w', 'lg/k', 'lg/l', 'lg/u', 'lg50', 'lg54',
                    'lge-', 'lge/', 'lynx', 'leno', 'm1-w', 'm3ga', 'm50/', 'maui', 'mc01',
                    'mc21', 'mcca', 'medi', 'meri', 'mio8', 'mioa', 'mo01', 'mo02', 'mode',
                    'modo', 'mot ', 'mot-', 'mt50', 'mtp1', 'mtv ', 'mate', 'maxo', 'merc',
                    'mits', 'mobi', 'motv', 'mozz', 'n100', 'n101', 'n102', 'n202', 'n203',
                    'n300', 'n302', 'n500', 'n502', 'n505', 'n700', 'n701', 'n710', 'nec-',
                    'nem-', 'newg', 'neon', 'netf', 'noki', 'nzph', 'o2 x', 'o2-x', 'opwv',
                    'owg1', 'opti', 'oran', 'p800', 'pand', 'pg-1', 'pg-2', 'pg-3', 'pg-6',
                    'pg-8', 'pg-c', 'pg13', 'phil', 'pn-2', 'pt-g', 'palm', 'pana', 'pire',
                    'pock', 'pose', 'psio', 'qa-a', 'qc-2', 'qc-3', 'qc-5', 'qc-7', 'qc07',
                    'qc12', 'qc21', 'qc32', 'qc60', 'qci-', 'qwap', 'qtek', 'r380', 'r600',
                    'raks', 'rim9', 'rove', 's55/', 'sage', 'sams', 'sc01', 'sch-', 'scp-',
                    'sdk/', 'se47', 'sec-', 'sec0', 'sec1', 'semc', 'sgh-', 'shar', 'sie-',
                    'sk-0', 'sl45', 'slid', 'smb3', 'smt5', 'sp01', 'sph-', 'spv ', 'spv-',
                    'sy01', 'samm', 'sany', 'sava', 'scoo', 'send', 'siem', 'smar', 'smit',
                    'soft', 'sony', 't-mo', 't218', 't250', 't600', 't610', 't618', 'tcl-',
                    'tdg-', 'telm', 'tim-', 'ts70', 'tsm-', 'tsm3', 'tsm5', 'tx-9', 'tagt',
                    'talk', 'teli', 'topl', 'tosh', 'up.b', 'upg1', 'utst', 'v400', 'v750',
                    'veri', 'vk-v', 'vk40', 'vk50', 'vk52', 'vk53', 'vm40', 'vx98', 'virg',
                    'vite', 'voda', 'vulc', 'w3c ', 'w3c-', 'wapj', 'wapp', 'wapu', 'wapm',
                    'wig ', 'wapi', 'wapr', 'wapv', 'wapy', 'wapa', 'waps', 'wapt', 'winc',
                    'winw', 'wonu', 'x700', 'xda2', 'xdag', 'yas-', 'your', 'zte-', 'zeto',
                    'aste', 'audi', 'avan', 'blaz', 'brew', 'brvw', 'bumb', 'ccwa', 'cell',
                    'cldc', 'cmd-', 'dang', 'eml2', 'fetc', 'hipt', 'http', 'ibro', 'idea',
                    'ikom', 'ipaq', 'jbro', 'jemu', 'jigs', 'keji', 'kyoc', 'kyok', 'libw',
                    'm-cr', 'midp', 'mmef', 'moto', 'mwbp', 'mywa', 'newt', 'nok6', 'o2im',
                    'pant', 'pdxg', 'play', 'pluc', 'port', 'prox', 'rozo', 'sama', 'seri',
                    'smal', 'symb', 'treo', 'upsi', 'vx52', 'vx53', 'vx60', 'vx61', 'vx70',
                    'vx80', 'vx81', 'vx83', 'vx85', 'wap-', 'webc', 'whit', 'wmlb', 'xda-');
                    
                if (in_array(strtolower(substr(self::getAgent(), 0, 4)), $mobileAgents)) {
                    self::addDevice(self::FEATURE_PHONE);
                }
            }


            // determine mobile
            $lagent = strtolower(self::getAgent());
            if (strpos($lagent, 'mobile') !== FALSE || strpos($lagent, 'phone') !== FALSE) {
                self::$mobile = TRUE;
            }
            else {
                self::$mobile = FALSE;
            }

            // additional mobile check
            foreach (self::$mobileDeviceList as $mobdev) {
                if (self::isDevice($mobdev)) {
                    self::$mobile = TRUE;
                    break;
                }
            }

            // ipad is explicitly non-mobile
            if (self::isDevice(self::IPAD)) {
                self::$mobile = FALSE;
            }

            // tablet devices
            foreach (self::$tabletDeviceList as $dev) {
                if (self::isDevice($dev)) {
                    self::$tablet = TRUE;
                    break;
                }
            }

            // PC = !mobile     PUT THIS AFTER MOBILE DETECTION ALL CAPS WARNING
            if (self::isTesting('PC') || !self::$mobile) {
                self::addDevice(self::PC);
                self::$mobile = FALSE;  // "testing PC" = not mobile
            }
        }
    }


    // ************************************************************************
    // **** LEGACY SUPPORT ****************************************************
    // (these are functions which are needed for historical compatibility)


    /**
	 * method indicates if the mobile device allows advanced javascript
        * @return bool if the client is an advanced mobile browser or not
	 */
    public static function isMobileJavascript()
    {
        return (self::isAndroid() || self::isAppleMobile());
    }
    

    /**
	 * @ return Bool Whether or not the device is the t-mobile G1
         * second agent string is for pre 1.5 versions of android. It is likely the G2 and other future android phones will have atleast 1.5 installed
	 */
	public static function isG1()
	{
		if (self::isTesting('G1')) return true;
        $agent = self::getAgent();
        return (stripos($agent, 'T-Mobile G1') !== FALSE || stripos($agent, 'Android 1.0;') !== FALSE);
	}
    
    /**
	 * @ return Bool Whether or not the device is the t-mobile myTouch 3g Android phone
	 */
	public static function isMyTouch3g()
	{
		if (self::isTesting('myTouch3G')) return true;
        $agent = self::getAgent();
        return (stripos($agent, 'T-Mobile myTouch 3G') !== FALSE);
	}
    

	/**
	 * @param String $number any phone number input
	 * @return String iPhone phone # link if applicable
	 */
	public static function iPhoneLink($number)
	{
		return (self::isIphone())
			? "<a href=\"tel:{$number}\">{$number}</a>"
			: $number;
	}
    
    /**
	 * This method is a blacklist for devices that are confirmed to not have ajax support
        * @ return Bool Whether or not the device supports ajax
	 */
	public static function isMobileAjax()
	{
		return (!self::isBlackBerry());
	}
    
    /**
    * An approximation of WebKit support (not a true definition of WebKit).
    * @return TRUE if the browser should support a WebKit-like page
    */
	public static function isWebKit() {
		$agent = strtolower(self::getAgent());

        if (strpos($agent, 'WebKit') !== FALSE) {
            return TRUE;
        }

        if (self::isSmartphone() || // affirmative condition OR
                                         // the compound negative condition below:
            (stripos($agent, 'MSIE') === FALSE && stripos($agent, 'Opera') === FALSE 
            && !self::isOldBlackBerry() && !self::isSymbian()
            && !self::isSamsung() && !self::isLG() && !self::isBadMobileDevice())
            ) {
            return true;
        }
        return false;
	}
    
    /**
        * @ return Bool Whether or not the device is using internet explorer
	 */
    public static function isIE()
    {
        return !(stripos(self::getAgent(), 'MSIE') === FALSE);
    }
    
    /**
         * Tests a numeric speed against the device to see if the device supports it
         * @param Integer $speed The numeric bitrate fo supported speed
         */
    public static function testSpeed($speed, $type = self::VIDEO_TYPE_PIF)
    {
        $speed = (int)$speed;
        // wifi speeds
        if ($type == self::VIDEO_TYPE_PIF 
            && $speed >= 750 
            && (self::isAndroid() || self::isWebOs() || self::isPSP())
          )
        {
            return false;
        }
        // hd speeds
        elseif ($speed > 750 && self::isMobile()){
            return false;
        }
        return true;
    }
    

}
