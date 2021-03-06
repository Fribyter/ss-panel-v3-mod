<?php

namespace App\Utils;

use App\Models\User;
use App\Services\Config;
use DateTime;

class Tools
{

    /**
     * 根据流量值自动转换单位输出
     */
    static function flowAutoShow($value = 0)
    {
        $kb = 1024;
        $mb = 1048576;
        $gb = 1073741824;
        if (abs($value) > $gb) {
            return round($value / $gb, 2) . "GB";
        } else if (abs($value) > $mb) {
            return round($value / $mb, 2) . "MB";
        } else if (abs($value) > $kb) {
            return round($value / $kb, 2) . "KB";
        } else {
            return round($value, 2)."B";
        }
    }

    static function toMB($traffic)
    {
        $mb = 1048576;
        return $traffic * $mb;
    }

    static function toGB($traffic)
    {
        $gb = 1048576 * 1024;
        return $traffic * $gb;
    }
	
	
    /**
     * @param $traffic
     * @return float
     */
    public static function flowToGB($traffic)
    {
        $gb = 1048576 * 1024;
        return $traffic / $gb;
    }

    //获取随机字符串
    public static function genRandomChar($length = 8)
    {
        // 密码字符集，可任意添加你需要的字符
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $char = '';
        for ($i = 0; $i < $length; $i++) {
            $char .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $char;
    }

    public static function genToken()
    {
        return self::genRandomChar(64);
    }
	
	public static function is_ip($a){  
		$b = preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/",$a);
		return $b;
    } 


    // Unix time to Date Time
    public static function toDateTime($time)
    {
        return date('Y-m-d H:i:s', $time);
    }
	
	public static function secondsToTime($seconds)
    {
        $dtF = new DateTime("@0");
        $dtT = new DateTime("@$seconds");
        return $dtF->diff($dtT)->format('%a 天, %h 小时, %i 分 + %s 秒');
    }

    public static function genSID()
    {
        $unid = uniqid(Config::get('key'));
        return Hash::sha256WithSalt($unid);
    }

    public static function genUUID()
    {
        // @TODO
        return self::genSID();
    }

    public static function getLastPort()
    {
        $user = User::orderBy('id', 'desc')->first();
        if ($user == null) {
            return 1024; // @todo
        }
        return $user->port;
    }
	
	public static function getAvPort()
    {
		//检索User数据表现有port
		$det = User::pluck('port')->toArray();

		$port = array_diff(range(Config::get('min_port'),Config::get('max_port')),$det);
		shuffle($port);

		return $port[0];
    }
	
	
	public static function base64_url_encode($input) {
		return strtr(base64_encode($input), '+/', '-_');
	}

	public static function base64_url_decode($input) {
		return base64_decode(strtr($input, '-_', '+/'));
	}
	
	public static function getDir($dir) {
		$dirArray[]=NULL;
		if (false != ($handle = opendir ( $dir ))) {
			$i=0;
			while ( false !== ($file = readdir ( $handle )) ) {
				if ($file != "." && $file != ".."&&!strpos($file,".")) {
					$dirArray[$i]=$file;
					$i++;
				}
			}
			closedir ( $handle );
		}
		return $dirArray;
	}
	
	
	public static function is_validate($str)
	{
		$pattern = "/[^A-Za-z0-9\-_\.]/";
		if (preg_match($pattern, $str))
		{
			return false;
		}
		return true;
	}

	public static function is_relay_rule_avaliable($rule, $ruleset, $node_id)
	{
		$cur_id = $rule->id;
		
		foreach($ruleset as $single_rule)
		{
			if(($rule->port == $single_rule->port || $single_rule->port == 0) && ($node_id == $single_rule->source_node_id || $single_rule->source_node_id == 0) && (($rule->id != $single_rule->id && $rule->priority < $single_rule->priority) || ($rule->id < $single_rule->id && $rule->priority == $single_rule->priority)))
			{
				$cur_id = $single_rule->id;
			}
		}

		if($cur_id != $rule->id)
		{
			return False;
		}
		
		return True;
	}
	
	public static function pick_out_relay_rule($relay_node_id, $port, $ruleset)
	{
		
		/* 
		for id in self.relay_rule_list:
			if ((self.relay_rule_list[id]['user_id'] == user_id or self.relay_rule_list[id]['user_id'] == 0) or row['is_multi_user'] != 0) and (self.relay_rule_list[id]['port'] == 0 or self.relay_rule_list[id]['port'] == port):
				has_higher_priority = False
				for priority_id in self.relay_rule_list:
					if ((self.relay_rule_list[priority_id]['priority'] > self.relay_rule_list[id]['priority'] and self.relay_rule_list[id]['id'] != self.relay_rule_list[priority_id]['id']) or (self.relay_rule_list[priority_id]['priority'] == self.relay_rule_list[id]['priority'] and self.relay_rule_list[id]['id'] > self.relay_rule_list[priority_id]['id'])) and (self.relay_rule_list[id]['user_id'] == self.relay_rule_list[priority_id]['user_id'] or self.relay_rule_list[priority_id]['user_id'] == 0) and (self.relay_rule_list[id]['port'] == self.relay_rule_list[priority_id]['port'] or self.relay_rule_list[priority_id]['port'] == 0):
						has_higher_priority = True
						continue

				if has_higher_priority:
					continue

			temp_relay_rules[id] = self.relay_rule_list[id] 
		*/
		
		$match_rule = null;
		
		foreach($ruleset as $single_rule)
		{
			if(($single_rule->port == $port || $single_rule->port == 0) && ($single_rule->source_node_id == 0 || $single_rule->source_node_id == $relay_node_id))
			{
				$has_higher_priority = False;
				foreach($ruleset as $priority_rule)
				{
					if(($priority_rule->port == $port || $priority_rule->port == 0) && ($priority_rule->source_node_id == 0 || $priority_rule->source_node_id == $relay_node_id))
					{
						if(($priority_rule->priority > $single_rule->priority && $priority_rule->id != $single_rule->id) || ($priority_rule->priority == $single_rule->priority && $priority_rule->id < $single_rule->id))
						{
							$has_higher_priority = True;
							continue;
						}
					}
				}
				
				if($has_higher_priority)
				{
					continue;
				}
				
				$match_rule = $single_rule;
			}
		}
		
		return $match_rule;
	}
	
	public static function get_middle_text($origin_text, $begin_text, $end_text)
	{
		$begin_pos = strpos($origin_text, $begin_text);
		if($begin_pos == FALSE)
		{
			return null;
		}
		
		$end_pos = strpos($origin_text, $end_text, $begin_pos + strlen($begin_text));
		if($end_pos == FALSE)
		{
			return null;
		}
		
		return substr($origin_text, $begin_pos + strlen($begin_text), $end_pos - $begin_pos - strlen($begin_text));
	}
	
	public static function is_param_validate($type, $str)
	{
		$list = Config::getSupportParam($type);
		if (in_array($str, $list))
		{
			return true;
		}
		return false;
	}
	
	public static function is_protocol_relay($user)
	{
		$relay_able_list = array('auth_aes128_md5', 'auth_aes128_sha1');
		
		if(in_array($user->protocol, $relay_able_list) || Config::get('relay_insecure_mode') == 'true')
		{
			return true;
		}
		
		return false;
	}
}
