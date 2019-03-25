<?php
namespace SparkLee\Ad\FeedAd\Driver;

use SparkLee\Ad\FeedAd\AbstractDriver;

/**
 * 信息流广告-今日头条
 *
 * @author Spark Lee <liweijsj@163.com>
 * @since  2019/03/22 18:00
 */
class Toutiao extends AbstractDriver {
	/**
	 * 信息流广告平台名称
	 * @var string
	 */
	public $name = '今日头条';

	public function __construct($activity) {
		parent::__construct($activity);
	}

	/**
	　* 验签
	 * 
	 * 今日头条对接文档对sign参数的定义：使用替换后的 url+convert_secret_key进行 md5 生成签名；注意！！！该字段一定放到 url 的最后，作为最后一个参数；备注：签名是为了接口反作弊使用，一般不推荐使用。
	 */
	public function checkSign() {
		return true;
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
		$url .= "&ts=__TS__";                         # 点击事件时间戳
		$url .= "&idfa=__IDFA__";                     # iOS设备标识（适用iOS 6及以上）
		$url .= "&udid=__UDID__";                     # iOS UDID（取md5摘要，iOS手机硬件唯一标识，一般情况下取不到）
		$url .= "&openudid=__OPENUDID__";             # iOS手机用软件生成的一个可变的替代UDID的标识，通过第三方的Open UDID SDK生成（原值）
		$url .= "&imei_md5=__IMEI__";                 # Android广告唯一标识（取md5摘要，IMEI为15位数字，双卡手机可能有两个IMEI）
		$url .= "&android_id=__ANDROIDID__";          # Android硬件设备唯一标识（取md5摘要）
		$url .= "&android_id1=__ANDROIDID1__";        # Android硬件设备唯一标识（原值）
		$url .= "&uuid=__UUID__";                     # Android手机系统生成的设备ID（原值）
		$url .= "&mac=__MAC__";                       # 用户终端的eth0接口的MAC地址（去除分隔符":"，取md5sum摘要，入网硬件地址）
		$url .= "&mac1=__MAC1__";                     # 用户终端的eth0接口的MAC地址（保留分隔符":"，取md5sum摘要，入网硬件地址）

		$url .= "&cid=__CID__";                       # 广告创意ID
		$url .= "&aid=__AID__";                       # 广告计划ID
		$url .= "&csite=__CSITE__";                   # 广告投放位置（1:头条信息流，3:详情页，11:段子信息流）
		$url .= "&ctype=__CTYPE__";                   # 创意样式（2:小图模式，3:大图模式，4:组图模式，5:视频）
		$url .= "&callback_url=__CALLBACK_URL__";     # 激活回调地址（方案一，urlencode编码）
		$url .= "&callback_param=__CALLBACK_PARAM__"; # 激活回调地址（方案一，urlencode编码）
		$url .= "&convert_id=__CONVERT_ID__";         # 转化跟踪ID
		$url .= "&siteid=__UNION_SITE__";             ### 热云TrackingIO点击监测链接有此参数，今日头条文档没有 
		$url .= "&sign=__SIGN__";                     # 签名（使用替换后的 url+convert_secret_key进行 md5 生成签名，注意！！！该字段一定放到 url 的最后，作为最后一个参数，备注：签名是为了接口反作弊使用，一般不推荐使用）

		return $url;
	}

	/**
	　* 组装并返回热云TrackingIO点击监测链接
	 */
	public function getTkioClickUrl() {
		$tkio_click_url = $this->activity['tkio_click_url'];

		// 获取去除签名参数sign之后的URL，和签名参数值
		list($url, $sign_placeholder) = $this->PopUrlParam($tkio_click_url, 'sign');

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
		// $sign = md5($url.$this->akey);
		// $url .= "&sign={$sign}";

		return $url;
	}

}