<?php
namespace SparkLee\Ad\FeedAd\Driver;

use SparkLee\Ad\FeedAd\AbstractDriver;

/**
 * 信息流广告-今日头条
 *
 * @author Spark Lee <liweijsj@163.com>
 * @since  2019/03/22 18:00
 * @see 巨量引擎-开发文档-移动应用API上报数据：https://ad.toutiao.com/openapi/doc/index.html?id=252
 * @see 巨量引擎-推广后台：https://ad.toutiao.com/overture/data/advertiser/ad/
 */
class Toutiao extends AbstractDriver {
	/**
	 * 信息流广告平台名称
	 * @var string
	 */
	public $name = '今日头条';

	/**
	 * 在今日头条后台新建转化时，获取的API通讯验签密钥
	 * @var string
	 */
	private $convert_secret_key = '';

	public function __construct($activity) {
		parent::__construct($activity);

		// 今日头条加密密钥
		$this->convert_secret_key = $activity['adplatform_key'];
	}

	/**
	　* 验签
	 * 
	 * 今日头条对接文档对sign参数的定义：使用替换后的 url+convert_secret_key进行 md5 生成签名；注意！！！该字段一定放到 url 的最后，作为最后一个参数；备注：签名是为了接口反作弊使用，一般不推荐使用。
	 */
	public function checkSign() {
		// 获取去除签名参数sign之后的URL，和签名参数值
		list($url, $param_sign_val) = $this->PopUrlParam($this->click_req_url, 'sign');

		// 在去掉签名参数sign的完全请求地址末尾直接追加akey
		$url .= $this->convert_secret_key;

		// 生成签名
		$sign = md5($url);

		// 验证签名是否正确
		return $param_sign_val == $sign;
	}

	/**
	 * 获取点击监测链接
	 * @return 包含占位符的点击监测链接 http://sdk.duojiao.tv/index.php/FeedAd/Api/click/id/1??ip=__IP__&ua=__UA__&ua1=__UA1__&os=__OS__&ts=__TS__&idfa=__IDFA__&udid=__UDID__&openudid=__OPENUDID__&imei_md5=__IMEI__&android_id=__ANDROIDID__&android_id1=__ANDROIDID1__&uuid=__UUID__&mac=__MAC__&mac1=__MAC1__&cid=__CID__&aid=__AID__&csite=__CSITE__&ctype=__CTYPE__&callback_url=__CALLBACK_URL__&callback_param=__CALLBACK_PARAM__&convert_id=__CONVERT_ID__&siteid=__UNION_SITE__&sign=__SIGN__
	 */
	public function getClickUrl($url_path) {
		$url = $url_path;

		$url .= "?ip=__IP__";                         # 客户端IP地址       
		$url .= "&ua=__UA__";                         # UA（原值）
		$url .= "&ua1=__UA1__";                       # UA（urlencode编码）
		$url .= "&os=__OS__";                         # 客户端操作系统类型（0:Android，1:iOS，2:WP，3:Others）
		$url .= "&ts=__TS__";                         # 客户端发生广告点击事件的时间	(UTC时间戳)
		$url .= "&idfa=__IDFA__";                     # iOS设备标识（适用iOS 6及以上）
		$url .= "&udid=__UDID__";                     # iOS UDID（取md5摘要，iOS手机硬件唯一标识，一般情况下取不到）
		$url .= "&openudid=__OPENUDID__";             # Android和iOS手机均有，用软件生成的一个可变的替代UDID的标识，通过第三方的Open UDID SDK生成（原值）
		$url .= "&imei_md5=__IMEI__";                 # Android广告唯一标识（取md5摘要，IMEI为15位数字，双卡手机可能有两个IMEI）
		$url .= "&android_id=__ANDROIDID__";          # Android硬件设备唯一标识（取md5摘要）
		$url .= "&android_id1=__ANDROIDID1__";        # Android硬件设备唯一标识（原值）
		$url .= "&uuid=__UUID__";                     # Android手机系统生成的设备ID（原值）
		$url .= "&mac=__MAC__";                       # 用户终端的eth0接口的MAC地址（去除分隔符":"，取md5sum摘要，入网硬件地址）
		$url .= "&mac1=__MAC1__";                     # 用户终端的eth0接口的MAC地址（保留分隔符":"，取md5sum摘要，入网硬件地址）

		$url .= "&cid=__CID__";                       # 广告创意ID
		$url .= "&aid=__AID__";                       # 广告计划ID
		$url .= "&aid_name=__AID_NAME__";             # 广告计划名称（urlencode编码）
		$url .= "&csite=__CSITE__";                   # 广告投放位置（1:头条信息流，3:详情页，11:段子信息流，10001:西瓜视频，30001:火山小视频，40001:抖音）
		$url .= "&ctype=__CTYPE__";                   # 创意样式（2:小图模式，3:大图模式，4:组图模式，5:视频）
		$url .= "&callback_url=__CALLBACK_URL__";     # 激活回调地址（方案一，urlencode编码）
		$url .= "&callback_param=__CALLBACK_PARAM__"; # 激活回调地址（方案二，urlencode编码）
		$url .= "&convert_id=__CONVERT_ID__";         # 转化跟踪ID
		$url .= "&siteid=__UNION_SITE__";             ### 热云TrackingIO点击监测链接有此参数，今日头条文档没有 
		$url .= "&sign=__SIGN__";                     # 签名（使用替换后的 url+convert_secret_key进行 md5 生成签名，注意！！！该字段一定放到 url 的最后，作为最后一个参数，备注：签名是为了接口反作弊使用，一般不推荐使用）

		return $url;
	}

	// 从点击监测请求中获取：
	// 客户端IP地址
	public function getIP() {
		return empty($this->click_req_params['ip'])? $this->click_req_params['ip'] : '';
	}
	// UA（原值）
	public function getUA() {
		return empty($this->click_req_params['ua'])? $this->click_req_params['ua'] : '';
	}
	// UA（urlencode编码）
	public function getUA1() {
		return empty($this->click_req_params['ua1'])? $this->click_req_params['ua1'] : '';
	}
	// OS 操作系统
	public function getOS() {
		$os = [
			'0' => self::OS_ANDROID,
			'1' => self::OS_IOS,
			'2' => self::OS_WINPHONE,
		];
		if(isset($this->click_req_params['os']) && in_array($this->click_req_params['os'], $os)) {
			return $os[$this->click_req_params['os']];
		} else {
			return self::OS_UNKNOWN;
		}
	}

	/**
	 * 获取点击回传地址
	 * @see 回调地址：https://ad.toutiao.com/openapi/doc/index.html?id=287
	 */
	public function getClickCallbackUrl() {
		// 从点击监测请求URL中获取回传地址
		$callback_url = $this->PopUrlParam($this->click_req_url, 'callback_url', 'param');

		// URL解码
		$callback_url = urldecode($callback_url);

		// 拼接其他参数
		$callback_url .= "&source=questfree"; # 激活数据来源（比如来自talkingdata，可以填TD，广告主可自行命名）
		$callback_url .= "&event_type=0";     # 转化目标（0: 激活 1: 激活且注册 2: 激活且付费 3: 表单 4: 咨询 5: 有效咨询 6: 次留 19: 有效获客 25: 关键行为 35: 表单提交）

		// 拼接签名
		// 测试发现今日头条并未对回传请求做验签操作，因为拼接在回传请求地址中的签名参数signature不管传什么值，今日头条都会响应成功"{"msg": "success", "code": 0, "ret": 0}"

		return $callback_url;
	}

	/**
	　* 组装并返回热云TrackingIO点击监测链接
	 */
	public function getTkioClickUrl() {
		$tkio_click_url = $this->activity['tkio_click_url'];

		if(empty($tkio_click_url)) return '';

		// 获取去除签名参数sign之后的URL，和签名参数值
		$url = $this->PopUrlParam($tkio_click_url, 'sign', 'url');

		// 替换热云TrackingIO点击监测链接中的占位符
		$url = str_replace('__OPENUDID__',       $this->click_req_params['openudid'],       $url);
		$url = str_replace('__IDFA__',           $this->click_req_params['idfa'],           $url);
		$url = str_replace('__OS__',             $this->click_req_params['os'],             $url);
		$url = str_replace('__IP__',             $this->click_req_params['ip'],             $url);
		$url = str_replace('__TS__',             $this->click_req_params['ts'],             $url);
		$url = str_replace('__CID__',            $this->click_req_params['cid'],            $url);
		$url = str_replace('__AID__',            $this->click_req_params['aid'],            $url);
		$url = str_replace('__CALLBACK_PARAM__', $this->click_req_params['callback_param'], $url);
		$url = str_replace('__CALLBACK_URL__',   $this->click_req_params['callback_url'],   $url);
		$url = str_replace('__CSITE__',          $this->click_req_params['csite'],          $url);
		$url = str_replace('__CTYPE__',          $this->click_req_params['ctype'],          $url);
		$url = str_replace('__UNION_SITE__',     $this->click_req_params['siteid'],         $url);

		// 生成签名
		// 热云点击监测链接中没有签名参数，故无需拼接签名参数

		return $url;
	}

}