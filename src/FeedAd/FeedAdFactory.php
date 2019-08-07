<?php
namespace SparkLee\Ad\FeedAd;

/**
 * 信息流广告-工厂类
 *
 * @author Spark Lee <liweijsj@163.com>
 * @since  2019/03/22 18:00
 */
class FeedAdFactory {
	private static $instance = []; // 广告平台实例类数组

    /**
     * 获取单例对象
     *
     * @param array $activity_info 推广活动记录（对应表 c_ad_feed_activity 中的一条记录）
     *
     * @return $this
     *
     * @throws \Exception
     * @author Spark Lee <liweijsj@163.com>
     * @since  2019/03/22 18:00
     */
	public static function getInstance($activity_info)
    {
		$activity = [
			'adplatform_name' => $activity_info['adplatform_name'],
			'tkio_click_url'  => $activity_info['tkio_click_url'],
			'adplatform_key'  => $activity_info['adplatform_key'],
		];
		
		$adplatform_name = $activity['adplatform_name'];

		if(!empty(self::$instance[$adplatform_name])) {
			return self::$instance[$adplatform_name];
		} else {
			$class_name = '';

			// 将以"_"分隔的广告平台名称，转化为以驼峰命名的广告平台类名（示例：bd_native_ocpc -> BaiduNativeOcpc）
			$_name = array_filter(explode('_', $adplatform_name));
			foreach ($_name as $name) {
				$class_name .= ucwords($name);
			}

			$class = "\\SparkLee\\Ad\\FeedAd\\Driver\\{$class_name}";
			if(!class_exists($class)) {
				throw new \Exception("Class \"{$class}\" not found.");
			}

			self::$instance[$adplatform_name] = new $class($activity);
			return self::$instance[$adplatform_name];
		}
	}	
}
