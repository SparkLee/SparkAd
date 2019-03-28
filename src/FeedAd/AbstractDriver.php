<?php
namespace SparkLee\Ad\FeedAd;

/**
 * 信息流广告-抽象父类
 *
 * @author Spark Lee <liweijsj@163.com>
 * @since  2019/03/22 18:00
 */
abstract class AbstractDriver {
	// 设备操作系统类型
	const OS_UNKNOWN  = 0; # 未知
	const OS_ANDROID  = 1; # 安卓
	const OS_IOS      = 2; # 苹果
	const OS_WINPHONE = 3; # 微软

	/**
	 * 信息流广告平台名称
	 * @var string
	 */
	public $name = '';

	/**
	 * 推广活动记录（对应表 c_ad_feed_activity 中的一条记录）
	 * @var array
	 */
	public $activity = [];

	/**
	 * 点击监测请求地址（完整请求地址）
	 * @var string
	 */
	public $click_req_url = '';

	/**
	 * 点击监测请求参数数组
	 * @var array
	 */
	public $click_req_params = [];

	public function __construct($activity) {
		// GET请求地址
		// @example 百度原生oCPC: http://local.sdk.duojiao.tv/index.php/FeedAd/Api/click/id/7952?ip=113.247.34.000&ua=Mozilla%2F5.0+%28iPhone%3B+CPU+iPhone+OS+12_1_4+like+Mac+OS+X%29+AppleWebKit%2F605.1.15+%28KHTML%2C+like+Gecko%29+Mobile%2F16D57&os=1&ts=1553394847000&idfa=91A2BC72-EF8D-471D-8CFC-201903240006&imei_md5=&android_id=&a_type=&a_value=&userid=&aid=34567&pid=&uid=&callback_url=&click_id=-194570134185690940_1553222664000&ext_info=5s7pFaQvW2b%2BB9wieNFYJ0c88LgSacfJxwK8lU%2FN71rbMpg82fnaGyRQmyiuj%2FZ4PrMs%2B6Izp3ARjRThuEpM1NQgsTcIr7qJLfPYVM2SHZgkSMTtmhIbomHpasaqh%2BaXpI7Dsj8E6g3%2BZSHo%2FBiLf12HpvF8yqfLG9KpiStLhpH1ZC5DhIA3g7zBzYOEkKODL7Gb4x4bKA1VwePjpxWwFuxAos8A6%2FBeBsQzadCKMmcD2EPwcb31d1vHcijvnf0qEDRG1pRzeEbVw%2F75z83CY5ZMX98S%2F0EuVcHj46cVsBbsQKLPAOvwXhHRcZXZ6GVRlgZpyumh1Rgu%2FbmqIO%2F4mWEgAMNObOFYHBncQC1D3Q%2FAlsScjS1NRNoT3zG%2BPiKh12sTEQ9hUPZXw738vMVebMQeHMesNtdDZxaIgTvQ3kO8HTaS9SgkUpBbe5oy3%2FiLXhHCYh8Q0RwQ%2BtrA5WRqrg%3D%3D&sign=
		// @example 今日头条    : http://local.sdk.duojiao.tv/index.php/FeedAd/Api/click/id/1?ip=113.247.34.000&ua=Mozilla/5.0%20(iPhone;%20CPU%20iPhone%20OS%2012_1_4%20like%20Mac%20OS%20X)%20AppleWebKit/605.1.15%20(KHTML,%20like%20Gecko)%20Mobile/16D57&ua1=Mozilla%2F5.0+%28iPhone%3B+CPU+iPhone+OS+12_1_4+like+Mac+OS+X%29+AppleWebKit%2F605.1.15+%28KHTML%2C+like+Gecko%29+Mobile%2F16D57&os=1&ts=1553394847000&idfa=91A2BC72-EF8D-471D-8CFC-201903240007&udid=&openudid=&imei_md5=&android_id=&android_id1=&uuid=&mac=&mac1=&cid=1&aid=2&csite=1&ctype=1&callback_url=&callback_param=&convert_id=1&siteid=1&sign=
		$this->click_req_url = "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

		// GET请求参数
		parse_str($_SERVER['QUERY_STRING'], $this->click_req_params);

		// 活动记录
		$this->activity = $activity;
	}

	/**
	　* 弹出URL中的一个指定参数
	 * 示例：PopUrlParam("http://www.domain.com/api?a=1&b=2&sign=c403f7d181b6b468579629e82237cb20", 'sign') = 'http://www.domain.com/api?a=1&b=2'
	 * 注：不能使用parse_str，http_build_query之类的函数，因为这些函数会自动urldecode/urlencode，从而破坏原始请求URL，可能导致验签失败等问题；parse_url不会变更URL的编码
	 * @return [弹出指定参数后的URL, 被弹出的参数值]
	 */
	public function PopUrlParam($url, $param_name, $return = '') {
		$url_parsed = parse_url($url);

		// 去掉请求参数中的签名参数sign
		$query_string = $url_parsed['query'];

		// 将query字符串拆成数组进行操作
		$query_arr = explode('&', $query_string);
		foreach ($query_arr as $k => $v) {
			$v = explode('=', $v);
			if($v[0] == $param_name) {
				unset($query_arr[$k]);
				$param_val = $v[1];
				break;
			}
		}

		// 把query数组再组装回字符串
		$query_string_poped = '';
		foreach ($query_arr as $query) {
			$query_string_poped .= $query.'&';
		}
		$query_string_poped = trim($query_string_poped, '&');

		$new_url = "{$url_parsed['scheme']}://{$url_parsed['host']}{$url_parsed['path']}?{$query_string_poped}";

		switch ($return) {
			case 'url':
				return $new_url;
				break;
			case 'param':
				return $param_val;
				break;
			default:
				return [$new_url, $param_val];
				break;
		}
	}

	/**
	 * 根据UA获取终端操作系统版本
	 */
	private function getOSVersionFromUA($ua = null) {
		!$ua && $ua = $_SERVER['HTTP_USER_AGENT'];

		if(strpos($ua, 'Android') !== false) {
			if(preg_match('#Android [\d|\.]+#', $ua, $matches)) {
				return str_replace(' ', '', $matches[0]);
			}
		}else if(strpos($ua, 'iPad') !== false) {
			if(preg_match('#OS ([\d|_]+)#', $ua, $matches)) {
				return str_replace('_', '.', "iPad{$matches[1]}");
			}
		}else if(strpos($ua, 'iPhone') !== false) {
			if(preg_match('#OS ([\d|_]+)#', $ua, $matches)) {
				return str_replace('_', '.', "iPhone{$matches[1]}");
			}
		}else {
			return '';
		}
	}
	/**
	 * 根据广告平台的点击监测请求链接中的参数获取设备唯一标识
	 */
	public function getDeviceID() {
		if(!empty($this->click_req_params['idfa'])) {
			return $this->click_req_params['idfa'];
		} elseif (!empty($this->click_req_params['imei_md5'])) {
			return $this->click_req_params['imei_md5'];
		} else {
			return trim($this->click_req_params['ip'].'|'.$this->getOSVersionFromUA($this->click_req_params['ua']), '|');
		}
	}
}