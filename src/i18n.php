<?php
/*
 * Fork this project on GitHub!
 * https://github.com/Philipp15b/php-i18n
 *
 * License: MIT
 */

declare(strict_types = 1);

namespace Philipp15b;

/**
 * Class i18n
 *
 * @package Philipp15b
 */
class i18n
{
    /**
     * Language file path
     * This is the path for the language files. You must use the '{LANGUAGE}' placeholder for the language or the
     * script wont find any language files.
     *
     * @var string
     */
    protected $filePath = './lang/lang_{LANGUAGE}.ini';

    /**
     * Cache file path
     * This is the path for all the cache files. Best is an empty directory with no other files in it.
     *
     * @var string
     */
    protected $cachePath = './langcache/';

    /**
     * Fallback language
     * This is the language which is used when there is no language file for all other user languages. It has the
     * lowest priority. Remember to create a language file for the fallback!!
     *
     * @var string
     */
    protected $fallbackLang = 'en';

    /**
     * Merge in fallback language
     * Whether to merge current language's strings with the strings of the fallback language ($fallbackLang).
     *
     * @var bool
     */
    protected $mergeFallback = false;

    /**
     * The class name of the compiled class that contains the translated texts.
     *
     * @var string
     */
    protected $prefix = 'L';

    /**
     * Forced language
     * If you want to force a specific language define it here.
     *
     * @var string
     */
    protected $forcedLang;

    /**
     * This is the separator used if you use sections in your ini-file.
     * For example, if you have a string 'greeting' in a section 'welcomepage' you will can access it via
     * 'L::welcomepage_greeting'. If you changed it to 'ABC' you could access your string via
     * 'L::welcomepageABCgreeting'
     *
     * @var string
     */
    protected $sectionSeparator = '_';

    /*
     * The following properties are only available after calling init().
     */

    /**
     * User languages
     * These are the languages the user uses.
     * Normally, if you use the getUserLangs-method this array will be filled in like this:
     * 1. Forced language
     * 2. Language in $_GET['lang']
     * 3. Language in $_SESSION['lang']
     * 4. Fallback language
     *
     * @var string[]
     */
    protected $userLangs = [];

    /**
     * @var string
     */
    protected $appliedLang;

    /**
     * @var string
     */
    protected $langFilePath;

    /**
     * @var string
     */
    protected $cacheFilePath;

    /**
     * @var bool
     */
    protected $isInitialized = false;

    /**
     * Constructor
     * The constructor sets all important settings. All params are optional, you can set the options via extra
     * functions too.
     *
     * @param string [$filePath] This is the path for the language files. You must use the '{LANGUAGE}' placeholder for
     *     the language.
     * @param string [$cachePath] This is the path for all the cache files. Best is an empty directory with no other
     *     files in it. No placeholders.
     * @param string [$fallbackLang] This is the language which is used when there is no language file for all other
     *     user languages. It has the lowest priority.
     * @param string [$prefix] The class name of the compiled class that contains the translated texts. Defaults to
     *     'L'.
     */
    public function __construct(string $filePath = null, string $cachePath = null, string $fallbackLang = null, string $prefix = null)
    {
        // Apply settings
        if ($filePath != null) {
            $this->filePath = $filePath;
        }

        if ($cachePath != null) {
            $this->cachePath = $cachePath;
        }

        if ($fallbackLang != null) {
            $this->fallbackLang = $fallbackLang;
        }

        if ($prefix != null) {
            $this->prefix = $prefix;
        }
    }

    /**
     * @return TranslatorInterface
     * @throws \BadMethodCallException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \Exception
     */
    public function init(): TranslatorInterface
    {
        if ($this->isInitialized()) {
            throw new \BadMethodCallException(
                'This object from class '
                .__CLASS__
                .' is already initialized. It is not possible to init one object twice!'
            );
        }

        $this->isInitialized = true;

        $this->userLangs = $this->getUserLangs();

        // search for language file
        $this->appliedLang = null;

        foreach ($this->userLangs as $priority => $langcode) {
            $this->langFilePath = $this->getConfigFilename($langcode);

            if (file_exists($this->langFilePath)) {
                $this->appliedLang = $langcode;
                break;
            }
        }

        if ($this->appliedLang == null) {
            throw new \RuntimeException('No language file was found.');
        }

        // search for cache file
        $this->cacheFilePath = $this->cachePath
            .'/php_i18n_'
            .md5(md5_file(__FILE__).$this->prefix.$this->langFilePath)
            .'_'
            .$this->prefix
            .'_'
            .$this->appliedLang
            .'.cache.php';

        // whether we need to create a new cache file
        $outdated = !file_exists($this->cacheFilePath)
            || filemtime($this->cacheFilePath) < filemtime($this->langFilePath)
            || ( // the language config was updated
                $this->mergeFallback
                && filemtime($this->cacheFilePath) < filemtime(
                    $this->getConfigFilename($this->fallbackLang)
                )); // the fallback language config was updated

        if ($outdated) {
            $config = $this->load($this->langFilePath);

            if ($this->mergeFallback) {
                $config = array_replace_recursive($this->load($this->getConfigFilename($this->fallbackLang)), $config);
            }

            $compiled = '<?php'.PHP_EOL
                .'return new class extends \Philipp15b\Translator {'.PHP_EOL
                .'    protected $_keys = ['.PHP_EOL
                .$this->compile($config).PHP_EOL
                .'    ];'.PHP_EOL.'};'.PHP_EOL;

            if (!is_dir($this->cachePath)) {
                mkdir($this->cachePath, 0755, true);
            }

            if (file_put_contents($this->cacheFilePath, $compiled) === false) {
                throw new \Exception('Could not write cache file to path "'.$this->cacheFilePath.'". Is it writable?');
            }

            chmod($this->cacheFilePath, 0755);
        }

        return include $this->cacheFilePath;
    }

    /**
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->isInitialized;
    }

    /**
     * @return string
     */
    public function getAppliedLang(): string
    {
        return $this->appliedLang;
    }

    /**
     * @return string
     */
    public function getCachePath(): string
    {
        return $this->cachePath;
    }

    /**
     * @return string
     */
    public function getFallbackLang(): string
    {
        return $this->fallbackLang;
    }

    /**
     * @param string $filePath
     *
     * @throws \BadMethodCallException
     */
    public function setFilePath(string $filePath)
    {
        $this->fail_after_init();
        $this->filePath = $filePath;
    }

    /**
     * @param string $cachePath
     *
     * @throws \BadMethodCallException
     */
    public function setCachePath(string $cachePath)
    {
        $this->fail_after_init();
        $this->cachePath = $cachePath;
    }

    /**
     * @param string $fallbackLang
     *
     * @throws \BadMethodCallException
     */
    public function setFallbackLang(string $fallbackLang)
    {
        $this->fail_after_init();
        $this->fallbackLang = $fallbackLang;
    }

    /**
     * @param string $mergeFallback
     *
     * @throws \BadMethodCallException
     */
    public function setMergeFallback(string $mergeFallback)
    {
        $this->fail_after_init();
        $this->mergeFallback = $mergeFallback;
    }

    /**
     * @param string $prefix
     *
     * @throws \BadMethodCallException
     */
    public function setPrefix(string $prefix)
    {
        $this->fail_after_init();
        $this->prefix = $prefix;
    }

    /**
     * @param string $forcedLang
     *
     * @throws \BadMethodCallException
     */
    public function setForcedLang(string $forcedLang)
    {
        $this->fail_after_init();
        $this->forcedLang = $forcedLang;
    }

    /**
     * @param string $sectionSeparator
     *
     * @throws \BadMethodCallException
     */
    public function setSectionSeparator(string $sectionSeparator)
    {
        $this->fail_after_init();
        $this->sectionSeparator = $sectionSeparator;
    }

    /**
     * @deprecated Use setSectionSeparator.
     *
     * @param string $sectionSeparator
     *
     * @throws \BadMethodCallException
     */
    public function setSectionSeperator(string $sectionSeparator)
    {
        $this->setSectionSeparator($sectionSeparator);
    }

    /**
     * getUserLangs()
     * Returns the user languages
     * Normally it returns an array like this:
     * 1. Forced language
     * 2. Language in $_GET['lang']
     * 3. Language in $_SESSION['lang']
     * 4. HTTP_ACCEPT_LANGUAGE
     * 5. Fallback language
     * Note: duplicate values are deleted.
     *
     * @return string[] with the user languages sorted by priority.
     */
    public function getUserLangs(): array
    {
        $userLangs = [];

        // Highest priority: forced language
        if ($this->forcedLang != null) {
            $userLangs[] = $this->forcedLang;
        }

        // 2nd highest priority: GET parameter 'lang'
        if (isset($_GET['lang']) && is_string($_GET['lang'])) {
            $userLangs[] = $_GET['lang'];
        }

        // 3rd highest priority: SESSION parameter 'lang'
        if (isset($_SESSION['lang']) && is_string($_SESSION['lang'])) {
            $userLangs[] = $_SESSION['lang'];
        }

        // 4th highest priority: HTTP_ACCEPT_LANGUAGE
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $part) {
                $userLangs[] = strtolower(substr($part, 0, 2));
            }
        }

        // Lowest priority: fallback
        $userLangs[] = $this->fallbackLang;

        // remove duplicate elements
        $userLangs = array_unique($userLangs);

        // remove illegal userLangs
        $userLangs2 = [];

        foreach ($userLangs as $key => $value) {
            // only allow a-z, A-Z and 0-9 and _ and -
            if (preg_match('/^[a-zA-Z0-9_-]*$/', $value) === 1) {
                $userLangs2[$key] = $value;
            }
        }

        return $userLangs2;
    }

    /**
     * @param string $langcode
     *
     * @return string
     */
    protected function getConfigFilename(string $langcode): string
    {
        return str_replace('{LANGUAGE}', $langcode, $this->filePath);
    }

    /**
     * @param string $filename
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function load(string $filename)
    {
        $ext = substr(strrchr($filename, '.'), 1);

        switch ($ext) {
            case 'properties':
            case 'ini':
                return parse_ini_file($filename, true);
            case 'yml':
            case 'yaml':
                return spyc_load_file($filename);
            case 'json':
                return json_decode(file_get_contents($filename), true);
        }

        throw new \InvalidArgumentException($ext.' is not a valid extension!');
    }

    /**
     * Recursively compile an associative array to PHP code.
     *
     * @param mixed[] $config
     * @param string  $prefix
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function compile(array $config, string $prefix = ''): string
    {
        return implode(','.PHP_EOL, $this->arrayCompile($config, $prefix));
    }

    /**
     * @param mixed[] $config
     * @param string  $prefix
     *
     * @return string[]
     * @throws \InvalidArgumentException
     */
    protected function arrayCompile(array $config, string $prefix = ''): array
    {
        $code = [];

        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $code = array_merge($code, $this->arrayCompile($value, $prefix.$key.$this->sectionSeparator));
            } else {
                $fullName = $prefix.$key;

                if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\\.\x7f-\xff]*$/', $fullName)) {
                    throw new \InvalidArgumentException(
                        __CLASS__
                        .': Cannot compile translation key '
                        .$fullName
                        .' because it is not a valid PHP identifier.'
                    );
                }

                $code[] = '        \''.$fullName.'\' => \''.str_replace('\'', '\\\'', $value).'\'';
            }
        }

        return $code;
    }

    /**
     * @throws \BadMethodCallException
     */
    protected function fail_after_init()
    {
        if ($this->isInitialized()) {
            throw new \BadMethodCallException(
                'This '.__CLASS__.' object is already initialized, so you can not change any settings.'
            );
        }
    }
}
