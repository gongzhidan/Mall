<?php
/**
 * 统一的内容状态码
 * 原则上按照数字顺序申请,可以提前占位,体现分组
 * !!!不允许出现重复状态码!!!
 */
namespace Mall\Framework\Core;

class StatusCode
{
    /**
     * 公用模块状态码
     *
     * 数值范围 1-100
     */

    /** @var int $delete 删除*/
    public static $delete = 4;

    /** @var int $standard 正常*/
    public static $standard = 5;

    /** @var int  $offline*/
    public static $offline = 6;

    /**
     * 咨询模块状态码
     *
     * 数值范围 101-200
     */

    /** @var int $verify 编辑提交给终审状态 */
    public static $finalVerify = 100;

    /** @var int $verify 自媒体提交给编辑待审状态 */
    public static $verify = 101;

    /** @var  int $published 预发布 */
    public static $published = 102;

    /** @var int $draft 草稿 */
    public static $draft = 103;

    /** @var int $category 列为频道正式数据 */
    public static $category = 104;

    /** @var int $commend 推荐数据 */
    public static $commend = 105;

    /** @var int $videoToTranscoding 视频转码中 */
    public static $videoToTranscoding = 106;

    /** @var int $noAccept 预发布淘汰 */
    public static $notAccept = 107;

    /** @var int $reject 编辑给自媒体驳回状态 */
    public static $reject = 108;

    /** @var int $reject 终审给编辑驳回状态 */
    public static $editorReject = 109;

    /** @var int $reject 超级管理员给终审驳回状态 */
    public static $finalReject = 110;

    /** @var int $hotSpotPass 热点通过状态 */
    public static $hotSpotPassVerify = 111;

    /** @var int $topPass 置顶通过状态 */
    public static $topPassVerify = 112;

    /** @var int $videoTranscodingFail 视频转码失败 */
    public static $videoTranscodingFail = 113;

    /** @var int $article 内容模型AppId-文章*/
    public static $article = 180;

    /** @var int $video 内容模型AppId-视频*/
    public static $video = 181;

    /** @var int $activity 内容模型AppId-活动*/
    public static $activity = 182;

    /** @var int $activity 内容模型AppId-图集*/
    public static $gallery = 183;

    /**
     * 会员模块状态码
     *
     * 数值范围 201-300
     */

    /** @var int $appRegister 注册来源APP Android*/
    public static $RegisterFromAndroid = 201;

    /** @var int $appRegister 注册来源APP iOS*/
    public static $RegisterFromiOS = 202;

    /** @var int $pcRegister 注册来源pc*/
    public static $RegisterFromPc = 203;

    /** @var int $backendAdd 注册来源pc后台添加*/
    public static $RegisterFromBackend = 204;

    /** @var int $RegisterFromWeiXin 注册来源第三方账号 weixin*/
    public static $RegisterFromWeiXin = 205;

    /** @var int $RegisterFromQQ 注册来源第三方账号 qq*/
    public static $RegisterFromQQ = 206;

    /** @var int $RegisterFromWeiBo 注册来源第三方账号 weibo*/
    public static $RegisterFromWeiBo = 207;

    /** @var int $sexMan 用户性别-男*/
    public static $sexMan = 211;

    /** @var int $sexWoMan 用户性别-女*/
    public static $sexWoMan = 212;


    /** @var array $userActionID 用户操作行为*/
    public static $userActionID = [
        'favour'=>1,// 点赞
        'tread'=>2, // 踩--不喜欢
        'report'=>3, // 举报
        'fllow'=>4, // 关注
        'avorites'=>5, //收藏
        'notAvorites' =>6, // 取消收藏
        'notFollow' => 7,  //取消关注
        'notTread' => 8,//取消踩--不喜欢
        'notFavour'=>9, //取消点赞
    ];

    /** @var array $userActionObj 用户操作对象  此处标识修改需谨慎多处关联 */
    public static $userActionObj = [
        /** @var int $actionContent 用户操作对象-文章*/
        'actionContent' => 1,
        /** @var int $actionUser 用户操作对象-用户*/
        'actionUser' => 2,
        /** @var int $actionComment 用户操作对象-评论*/
        'actionComment' => 3,
        /** @var int $actionMall 用户操作对象-商场 -店铺*/
        'actionMallStore' => 4,
        /** @var int $actionMall 用户操作对象-商场 -商品*/
        'actionMallShop' => 5,
        /** @var int $actionSea 用户操作对象-海淘--店铺*/
        'actionSeaStore' =>6,
        /** @var int $actionSea 用户操作对象-海淘--商品*/
        'actionSeaShop' =>7,
        /** @var int $actionMall 用户操作对象-商场 */
        'actionMall' => 8,
        /** @var int $actionMall 用户操作对象-旅游景点 */
        'actionScenic' => 9,
        /** @var int $actionPromotion 用户操作对象-消费打折信息*/
        'actionPromotion' => 10,



        /** @var int $actionArVocalConcert 用户操作对象-休闲--演唱会a*/
        'actionArVocalConcert' =>24,
        /** @var int $actionArTheatre 用户操作对象-休闲--剧场a*/
        'actionArTheatre' => 25,
        /** @var int $actionArTusicale 用户操作对象-休闲--音乐会a*/
        'actionArMusicale' => 26,
        /** @var int $actionArCompete 用户操作对象-休闲--比赛a*/
        'actionArCompete' => 27,
        /** @var int $actionArVariety 用户操作对象-休闲--综艺a*/
        'actionArVariety' => 28,
        /** @var int $actionArder 用户操作对象-休闲--博物馆p*/
        'actionArMuseum' => 29 ,
        /** @var int $actionArSport 用户操作对象-休闲--健身p*/
        'actionArSport' => 30,
        /** @var int $actionArCoffee 用户操作对象-休闲--咖啡p*/
        'actionArCoffee' => 31,
        /** @var int $actionMall 用户操作对象-其他 */
        'actionArOther' => 32,
        /** @var int $actionArCoffee 用户操作对象-休闲--下午茶p*/
        'actionArTea' => 33,
        /** @var int $actionArCoffee 用户操作对象-休闲--酒吧p*/
        'actionArWine' => 34 ,
        /** @var int $actionArCoffee 用户操作对象-休闲--足疗p*/
        'actionArFoot' => 35 ,
        /** @var int $actionArCoffee 用户操作对象-休闲--轰趴p*/
        'actionArParty' => 36 ,
        /** @var int $actionArCoffee 用户操作对象-休闲--瑜伽p*/
        'actionArYoga' => 37 ,
        /** @var int $actionArCoffee 用户操作对象-休闲--舞蹈p*/
        'actionArDance' => 38 ,
    ];


    /**
     * 自媒体审核状态
     */
    public static $weMediaAuditStatus = [
        'auditing'  => 1,  // 待审
        'auditPass' => 2,  // 审核通过
        'auditNotPass' => 3, //审核未通过
    ];

    /**
     * 内容统计状态
     */
    public static $contentCountStatus = [
        'success' => 1, // 已统计
        'abnormal' => 2, // 数据可能异常
    ];

    /**
     * 爆料审核状态
     */
    public static $discloseAuditStatus = [
        'auditing'  => 1,  // 待审
        'auditPass' => 2,  // 审核通过
        'auditNotPass' => 3, //审核未通过
    ];

    /**
     * 自媒体账号类型
     */
    public static $weMediaAccountType = [
        'personal' => 1, // 个人
        'media'    => 2, // 媒体
        'government' => 3, // 政府组织
        'company'  => 4, // 公司
        'other'    => 5, // 其它
    ];

    /**
     * 会员角色标识码(对应role表id)
     */
    public static $memberRole = [
        'administrator' => 1,
        'selfMedia' => 2,
        'editor' => 3,
        'finalJudgment' => 4,
        'member' => 5,
        'seaAmoy' => 6,
    ];

    /**
     * iqiyi视频托管视频状态
     */
    public static $iqiyiVideoStatus = [
        'A00000' => '视频处理完成',
        'Q00001' => '失败',
        'A00001' => '视频发布中',
        'A00002' => '视频审核失败',
        'A00003' => '视频不存在',
        'A00004' => '视频上传中',
        'A00006' => '用户取消上传',
        'A00007' => '视频发布失败',
    ];

    /**
     * 商购 - 大模块分类
     */
    public static $shopType = [
        'commend' => 1,     // 推荐
        'mall' => 2,        // 店铺
        'leisure' => 3,     // 休闲
        'travel' => 4,      // 旅游
        'seaAmoy' => 5,     // 海淘
    ];

    /**
     * 休闲场所频道集合
     */
    public static $leisurePlaceChannelId = [
        'museum' => 29 ,        //博物馆
        'sport' => 30,   // 健身
        'coffee' => 31,         // 咖啡
        'other'=>32,
        'tea' => 33,           // 下午茶
        'wine' => 34 ,          // 酒吧
        'foot' => 35 ,          // 足疗
        'party' => 36 ,          // 足疗
        'yoga' => 37 ,          // 瑜伽
        'dance' => 38 ,          // 舞蹈


    ];

    /**
     * 休闲娱乐集合$leisureAmusementChannelId
     */
    public static $leisureAmusementChannelId = [
        'theatre' => 25,        // 剧场
        'musicale' => 26,       // 音乐会
        'compete' => 27,        // 比赛
        'variety' => 28,        // 综艺
        'vocalConcert' => 24,   // 演唱会
    ];

    /**
     * 休闲娱乐票务状态
     */
    public static $leisureAmusementTicketStatus = [
        'undetermined' => 0,
        'presell' => 1, // 预售/预订
        'atTheTicketOffice' => 2, //售票中
        'returnTicket' => 3, // 退票
        4,5,6,7,8,9,10,11
    ];


    /**其他类型标识，用于文章评论点赞标识*/
    public static $allObject= [
        'content'=>1 ,// 资讯
        'comment'=>3,//评论
        'mallStore'=>4,//商场店铺
        'mallShop'=>5,//商场商品
        'seaAmoyStore'=>6,//海淘商场
        'seaAmoyProduct'=>7,//海淘商品
        'mall'=>8,//商场
        'scenic'=>9, //旅游景点
        'disclose'=>10, //爆料
        'vocalConcert' => 24,   // 演唱会
        'theatre' => 25,        // 剧场
        'musicale' => 26,       // 音乐会
        'compete' => 27,        // 比赛
        'variety' => 28,        // 综艺
        'museum' => 29 ,        //博物馆
        'sport' => 30,   // 健身
        'coffee' => 31,         // 咖啡
        'other'=>32,
        'tea' => 33,           // 下午茶
        'wine' => 34 ,          // 酒吧
        'foot' => 35 ,          // 足疗
        'party' => 36 ,          // 足疗
        'yoga' => 37 ,          // 瑜伽
        'dance' => 38 ,          // 舞蹈
   ];
    /**
     * 订单分类
     */
    public static $order = [
        'type' => [
            'planeTicket' => 1,     // 机票
            'scenicTicket' => 2,    // 景点门票
            'hotel' => 3,           // 酒店
            'seaamoy' => 4,         // 海淘
        ],
        'isMg' => [
            'no' => 2, // 不是猫逛产品
            'yes' => 1,// 是猫逛产品
        ],
        'status' => [
            'nonPayment' => 1, // 未付款
            'paymentAlreadyPaid' => 2, //已付款
            'notShipped' => 3, // 准备货物(出票准备中)
            'delete' => 4, // 已删除，请调取Status::$delete
            'shipped' => 5, // 已发货(已经出票)
            'successfulTrade' => 6,// 交易成功已经评价
            'transactionClosed' => 7,// 交易关闭
            'successfulTradeNoComment' => 8,// 交易成功未评价
        ],
        'paymentType' => [
            'online' => 1, // 在线支付
            'line' => 2  // 货到付款
        ],
        'paySource' => [
            'weixin'   => 1, // 微信支付
            'zhifubao' => 2, // 支付宝
        ],
        'orderFrom' => [
            'ios'     => 1,
            'android' => 2,
            'pc'      => 3,
        ],
    ];


    /**
     * 证件类型
     */
    public static $certificateType = [
        'noLimit' => 0, // 不限
        'identification' => 1, // 二代身份证
        'passport' => 2, // 护照
        'sergeant' => 3, // 军官证
        'Hong Kong-Macau laissez-passer' => 4, // 港澳通行证
        'other' => 6, // 其他
        'Mainland travel permit for Taiwan residents' => 7, // 台胞证
        'reentry permit' => 8, // 回乡证
        'household register' => 9, // 户口薄
        'birth certificate' => 10, // 出身证明
        'Travel permit in Taiwan' => 11, // 台湾出行证
    ];
    /*
     * 用户收货地址类型
     * **/
    public  static $addressIsdefault=[
        'default'=>1,
        'notDefault'=>2
    ];

    //请求来源，用于版本检查
    public static $appSource=[
        'android'=>1,
        'ios'=>2
    ];


    /**
     * 建议审核状态
     */
    public static $suggestStatus = [
        'all'=>[1,5],
        'auditing' => 5,  // 待处理
        'auditPass' => 1,  // 已处理
    ];

    /**
     * 用户初始频道组
     */
    public static $categoryGroup = [
        'isstudent' => 1,
        'ishavechildren' => 2,
        'iskeeppets' => 3,
        'default' => 4,
    ];

    /**是否是三级操作*/
    public static $isThree = [
        'yes' => 1,
        'no' => 0,
    ];

    /**
     * 心情类型
     */
    public static $mood = [
        'smile' => 1, // 微笑
        'calm'  => 2, // 平静
        'cry'   => 3, // 哭泣
    ];
}