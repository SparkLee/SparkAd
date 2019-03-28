<?php
namespace SparkLee\Ad\FeedAd\Driver;

use SparkLee\Ad\FeedAd\AbstractDriver;

/**
 * 信息流广告-百度原生oCPC
 *
 * @author Spark Lee <liweijsj@163.com>
 * @since  2019/03/22 18:00
 * @see    百度原生oCPC转化数据API对接文档：http://jinshu.baidu.com/preview?from=app&productId=202&versionId=250&tab=3&ch=99
 */
class BaiduNativeOcpc extends AbstractDriver {
	/**
	 * 信息流广告平台名称
	 * @var string
	 */
	public $name = '百度原生oCPC';

	/**
	 * 在百度原生oCPC后台新建计划时，获取的API通讯验签密钥akey
	 * @var string
	 */
	private $akey = '';

	public function __construct($activity) {
		parent::__construct($activity);

		// 每个推广活动（c_ad_feed_activity）都可以单独设置广告平台API对接验签密钥
		$this->akey = $activity['adplatform_key'];
	}

	/**
	　* 验签
	 * @see 百度原生oCPC点击监测验签文档：http://jinshu.baidu.com/preview?from=app&productId=202&versionId=250&tab=3&ch=99
	 */
	public function checkSign() {
		// 获取去除签名参数sign之后的URL，和签名参数值
		list($url, $param_sign_val) = $this->PopUrlParam($this->click_req_url, 'sign');

		// 在去掉签名参数sign的完全请求地址末尾直接追加akey
		$url .= $this->akey;

		// 生成签名
		$sign = md5($url);

		// 验证签名是否正确
		return $param_sign_val == $sign;
	}

	/**
	 * 获取点击监测链接
	 * 手百用户点击广告，客户端收集用户的点击事件，手百客户端把点击事件发送给百度广告服务器，百度原生回调监测URL，即feedbackurl(由广告主自行开发，或托管给三方监测平台)
	 * @return 包含占位符的点击监测链接 http://sdk.duojiao.tv/index.php/FeedAd/Api/click/id/1?a_type={{ATYPE}}&a_value={{AVALUE}}&userid={{USER_ID}}&aid={{IDEA_ID}}&pid={{PLAN_ID}}&uid={{UNIT_ID}}&callback_url={{CALLBACK_URL}}&click_id={{CLICK_ID}}&idfa={{IDFA}}&imei_md5={{IMEI_MD5}}&android_id={{ANDROID_ID}}&ip={{IP}}&ua={{UA}}&os={{OS}}&ts={{TS}}&ext_info={{EXT_INFO}}&sign={{SIGN}}
	 */
	public function getClickUrl($url_path) {
		$url = $url_path;

		$url .= "?ip={{IP}}";                     # 客户端IP地址
		$url .= "&ua={{UA}}";                     # UA
		$url .= "&os={{OS}}";                     # 操作系统（1:iOS，2:Android）
		$url .= "&ts={{TS}}";                     # 时间戳
		$url .= "&idfa={{IDFA}}";                 # iOS设备标识（原值）
		$url .= "&imei_md5={{IMEI_MD5}}";         # Android设备标识（md5(imei)）
		$url .= "&android_id={{ANDROID_ID}}";     # Android设备标识（原值）

		$url .= "&a_type={{ATYPE}}";              # 转化类型（activate:激活，register:注册）
		$url .= "&a_value={{AVALUE}}";            # 转换指标
		$url .= "&userid={{USER_ID}}";            # 账户ID
		$url .= "&aid={{IDEA_ID}}";               # 创意ID
		$url .= "&pid={{PLAN_ID}}";               # 计划ID
		$url .= "&uid={{UNIT_ID}}";               # 单元ID
		$url .= "&callback_url={{CALLBACK_URL}}"; # 效果数据回传URL
		$url .= "&click_id={{CLICK_ID}}";         # 点击唯一标识
		// $url .= "&ext_info={{EXT_INFO}}";         ### 热云TrackingIO点击监测链接有此参数，百度原生oCPC文档没有  
		$url .= "&sign={{SIGN}}";                 # 签名

		return $url;
	}

	// 从点击监测请求中获取：
	// OS 操作系统
	public function getOS() {
		$os = [
			'1' => self::OS_IOS,
			'2' => self::OS_ANDROID,
		];
		if(isset($this->click_req_params['os']) && in_array($this->click_req_params['os'], $os)) {
			return $os[$this->click_req_params['os']];
		} else {
			return self::OS_UNKNOWN;
		}
	}

	/**
	 * 获取点击回传地址
	 * 当广告主或第三方监测平台接受到百度原生的请求后，根据请求参数中用户的信息(例如用户的 idfa,imei 等)，来匹配该用户的转化数据（应用激活、消费等） 广告主或三方监测平台将匹配成功的转化数据与广告点击数据进行拼接，使用callback_url回传给百度。
	 */
	public function getClickCallbackUrl() {
		// 从点击监测请求URL中获取回传地址
		$callback_url = $this->PopUrlParam($this->click_req_url, 'callback_url', 'param');

		// URL解码
		$callback_url = urldecode($callback_url);

		// 参数替换（activate：代表回传的是激活【即用户首次打开游戏】数据）
		$callback_url = str_replace('{{ATYPE}}', 'activate', $callback_url);
		$callback_url = str_replace('{{AVALUE}}', '0', $callback_url);

		// 拼接签名
		$sign = md5($callback_url.$this->akey);
		$callback_url = $callback_url.'&sign='.$sign;

		return $callback_url;
	}

	/**
	　* 组装并返回热云TrackingIO点击监测链接
	 */
	public function getTkioClickUrl() {
		$tkio_click_url = $this->activity['tkio_click_url'];

		// 获取去除签名参数sign之后的URL，和签名参数值
		$url = $this->PopUrlParam($tkio_click_url, 'sign', 'url');

		// 替换热云TrackingIO点击监测链接中的占位符
		# 注：热云针对"百度原生oCPC"渠道生成的点击监测短链中没有携带参数{{CALLBACK_URL}}，但有"百度原生oCPC"对接文档
		# 中没有的特殊参数{{EXT_INFO}}，据热云的对接人员说，热云和"百度原生oCPC"渠道走了一套独立的对接方案，估计说的
		# 就是这个特殊情况。 所以，SDK不仅要提供点击监测链接，还需要主动给百度回传（通过callback_url）激活转化数据。
		$url = str_replace('{{IDFA}}',     $this->click_req_params['idfa'],          $url);
		$url = str_replace('{{OS}}',       $this->click_req_params['os'],            $url);
		$url = str_replace('{{IP}}',       $this->click_req_params['ip'],            $url);
		$url = str_replace('{{TS}}',       $this->click_req_params['ts'],            $url);
		$url = str_replace('{{IDEA_ID}}',  $this->click_req_params['aid'],           $url);
		$url = str_replace('{{CLICK_ID}}', $this->click_req_params['click_id'],      $url);
		// $url = str_replace('{{EXT_INFO}}', $this->click_req_params['ext_info'],      $url); # 百度调用SDK点击监测链接不会替换参数{{EXT_INFO}}
		$url = str_replace('{{UA}}',       urlencode($this->click_req_params['ua']), $url);

		// 生成签名
		$sign = md5($url.$this->akey);
		$url .= "&sign={$sign}"; # 上文中$url中的sign参数已被剔除，故此处需要拼接

		return $url;
	}

}