<?php

namespace Dashbrew\Util;

/**
 * Config Class.
 *
 * @package Dashbrew\Util
 */
class Config {

    const CONFIG_FILE       = '/vagrant/config/config.yaml';
    const CONFIG_FILE_TEMP  = '/vagrant/provision/main/etc/config.yaml.old';

    /**
     * @var array
     */
    protected static $config;

    /**
     * @var array
     */
    protected static $configOld;

    public function init() {

        $yaml = Util::getYamlParser();
        $fs   = Util::getFilesystem();

        self::$config    = $yaml->parse(file_get_contents(self::CONFIG_FILE));
        self::$configOld = [];

        if($fs->exists(self::CONFIG_FILE_TEMP)){
            self::$configOld = $yaml->parse(file_get_contents(self::CONFIG_FILE_TEMP));
            self::$config    = self::mergeOldConfig();
        }
    }

    /**
     * @param string $key
     * @return mixed
     */
    public static function get($key = null) {

        if(!isset(self::$config)){
            self::init();
        }

        if($key !== null){
            return self::$config[$key];
        }

        return self::$config;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public static function getOld($key = null) {

        if(!isset(self::$configOld)){
            self::init();
        }

        if($key !== null){
            if(!isset(self::$configOld[$key])){
                return null;
            }

            return self::$configOld[$key];
        }

        return self::$configOld;
    }

    /**
     * @param null $key
     * @return bool
     */
    public static function hasChanges($key = null) {

        if(!isset(self::$configOld)){
            self::init();
        }

        if(isset($key)){
            if(!isset(self::$configOld[$key])){
                return true;
            }

            return self::$config[$key] !== self::$configOld[$key];
        }

        return self::$config !== self::$configOld;
    }

    public static function writeTemp() {

        return Util::getFilesystem()->copy(self::CONFIG_FILE, self::CONFIG_FILE_TEMP, true, 'vagrant');
    }

    protected static function mergeOldConfig() {

        $config = self::$config;
        foreach(self::$configOld as $mkey => $mvalue){
            if(!is_array($mvalue)){
                continue;
            }

            foreach($mvalue as $key => $value){
                if(isset($config[$mkey][$key])){
                    continue;
                }

                switch($mkey){
                    case 'os::packages':
                        if($value){
                            $config[$mkey][$key] = false;
                        }
                        break;
                    case 'php::builds':
                        if(!isset($value['installed']) || !$value['installed']){
                            $config[$mkey][$key]['installed'] = false;
                        }
                        break;
                    case 'apache::modules':
                        if($value){
                            $config[$mkey][$key] = false;
                        }
                        break;
                    case 'npm::packages':
                        if($value){
                            $config[$mkey][$key] = false;
                        }
                        break;
                }
            }
        }

        return $config;
    }
}
