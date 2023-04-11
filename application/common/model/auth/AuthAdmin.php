<?php
   

namespace app\common\model\auth;

use think\facade\Cache;
use think\Model;

/**
 * 管理员表
 */
class AuthAdmin extends Model
{

    // 缓存的 key
    public static $cache_info = 'admin:id:';

    /**
     * 返回加密后的密码
     * @param $pwd
     * @return string
     */
    public static function getPass($pwd){
        return md5(md5($pwd));
    }

    /**
     * 生成 token
     * @param int $uid 用户的 UID
     * @return string
     */
    public static function createToken($uid){
        return (base64_encode($uid . time()));
    }

    /**
     * 获取登录信息
     * @param int $id 用户ID
     * @param array|string $values 如果这个值为数组则为设置用户信息，否则为 token
     * @param bool $is_login 是否验证用户是否登录
     * @return array|bool 成功返回用户信息，否则返回 false
     */
    public static function loginInfo($id, $values,$is_login = true){
        $redis = Cache::init()->handler();
        $key = self::$cache_info . $id;
        // 判断缓存类是否为 redis
        if ($redis instanceof \Redis){
            if ($values && is_array($values)){
                $values['id'] = $id;
                $values['token'] = self::createToken($id);
                $values['authRules'] = isset($values['authRules']) ? json_encode($values['authRules']) : '';
                $res = $redis->hMset($key, $values);
                $values = $values['token'];
            }
            $info = $redis->hGetAll($key);
            if ($is_login === false){
                if (isset($info['token']))  unset($info['token']);
                return $info;
            }
            if (!empty($info['id']) && !empty($info['token']) && $info['token'] == $values){
                $info['authRules'] = isset($info['authRules']) ? json_decode($info['authRules']) : '';
                return $info;
            }
        }else{
            if ($values && is_array($values)){
                $values['id'] = $id;
                $values['token'] = self::createToken($id);
                $res = Cache::set($key, $values);
                $values = $values['token'];
            }
            $info = Cache::get($key);
            if ($is_login === false){
                if (isset($info['token']))  unset($info['token']);
                return $info;
            }
            if (!empty($info['id']) && !empty($info['token']) && $info['token'] == $values){
                return $info;
            }
        }


        return false;
    }

    /**
     * 退出登录
     * @return array|mixed
     */
    public static function loginOut($id){
        $redis = Cache::init()->handler();
        $key = self::$cache_info . $id;
        // 判断缓存类是否为 redis
        if ($redis instanceof \Redis){
            $redis->del($key);
        }else{
            Cache::rm($key);
        }
    }

    /**
     * 获取头像的 URL
     * @param string $avatar 头像路径
     * @param int $size 获取的尺寸
     * @return string URL
     */
    public static function getAvatarUrl($avatar, $size = 0){
        if ($size && ($size == 50 || $size == 100)){
            $size_str = "_{$size}_{$size}";
            $ripos = strripos($avatar,'.'); // 最后一个点出现的位置
            $avatar = substr_replace($avatar, $size_str, $ripos, 0);
        }
        return get_asset_image_url($avatar);
    }

}
