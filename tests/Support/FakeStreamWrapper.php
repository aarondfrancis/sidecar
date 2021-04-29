<?php

namespace Hammerstone\Sidecar\Tests\Support;

class FakeStreamWrapper
{
    public $position;

    public $path;

    public static $paths = [];

    public static $calls = [];

    public static $protocols = [];

    public static function reset()
    {
        foreach (static::$protocols as $protocol) {
            stream_wrapper_unregister($protocol);
        }

        static::$paths = [];
        static::$calls = [];
        static::$protocols = [];
    }

    public static function register($protocol = 's3')
    {
        static::$protocols[] = $protocol;

        stream_wrapper_register($protocol, get_called_class());
    }

    public function url_stat($path, $flags)
    {
        static::$calls[] = [__FUNCTION__, func_get_args()];

        if (!array_key_exists($path, static::$paths)) {
            return null;
        }

        $stat = [
            0 => 0, 'dev' => 0,
            1 => 0, 'ino' => 0,
            2 => 0, 'mode' => 0,
            3 => 0, 'nlink' => 0,
            4 => 0, 'uid' => 0,
            5 => 0, 'gid' => 0,
            6 => -1, 'rdev' => -1,
            7 => 0, 'size' => 0,
            8 => 0, 'atime' => 0,
            9 => 0, 'mtime' => 0,
            10 => 0, 'ctime' => 0,
            11 => -1, 'blksize' => -1,
            12 => -1, 'blocks' => -1,
        ];

        $stat['mode'] = $stat[2] = 0100777;
        $stat['size'] = $stat[7] = mb_strlen(static::$paths[$path]);
        $stat['mtime'] = $stat[9] = $stat['ctime'] = $stat[10] = now()->timestamp;

        return $stat;
    }

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        static::$calls[] = [__FUNCTION__, func_get_args()];

        $this->path = $path;
        $this->position = 0;

        static::$paths[$this->path] = '';

        return true;
    }

    public function stream_write($data)
    {
        static::$calls[] = [__FUNCTION__];

        $left = substr(static::$paths[$this->path], 0, $this->position);
        $right = substr(static::$paths[$this->path], $this->position + strlen($data));
        static::$paths[$this->path] = $left . $data . $right;
        $this->position += strlen($data);
        return strlen($data);
    }

}
