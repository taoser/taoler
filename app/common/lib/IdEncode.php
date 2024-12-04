<?php
namespace app\common\lib;

use Sqids\Sqids;

class IdEncode
{
    private static $instance;

    private function __construct() {}

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Sqids(config('taoler.id_alphabet'), config('taoler.id_minlength'));
        }
        return self::$instance;
    }

    // ID加密成字符串
    public static function encode(int $id): int|string
    {
        if(config('taoler.id_status') === 1) {
            return self::getInstance()->encode([$id]);
        }
        return $id;
    }

    // ID解密
    public static function decode(string|int $string): int
    {
        if(config('taoler.id_status') === 1) {
            return self::getInstance()->decode($string)[0];
        }
        return $string;
    }
}