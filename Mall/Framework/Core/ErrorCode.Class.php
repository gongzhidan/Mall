<?php
/**
 * 统一的错误编码 方便Service 和 其它项目之间Swoole传输判断
 */
namespace Mall\Framework\Core;

class ErrorCode
{
    /** @var int $paramError 参数错误 */
    public static $paramError = 1001;

    /** @var int $userNotFount 用户不存在 */
    public static $userNotFount = 1002;

    /** @var int $notAllowAccess 非法的请求 */
    public static $notAllowAccess = 1003;

    /** @var int $mobileCodeFail 验证码错误 */
    public static $mobileCodeFail = 1004;

    /** @var int $dberror 数据库操作错误 */
    public static $dberror = 1005;

    /** @var int $accountfail 登录帐号密码错误 */
    public static $accountfail = 1006;

    /** @var int $swooleRecvError swoole服务未返回消息 */
    public static $swooleRecvError = 1007;

    /** @var int $loginedexpire  登陆过期 */
    public static $loginedexpire = 1008;

    /** @var int $redisWriteError  redis写入失败 */
    public static $redisWriteError = 1009;

    /** @var int $notHaveAclAccess 没有权限访问 */
    public static $notHaveAclAccess = 1010;

    /** @var int $signfail 签名错误 */
    public static $signfail = 1011;

    /** @var int $mobileishaved 注册手机号已经存在 */
    public static $mobileishaved = 1012;

    /** @var int $uploadEroor 上传操作失败 */
    public static $uploadEroor = 1013;

    /** @var int $configEroor 配置文件错误 */
    public static $configEroor = 1014;

    /** @var int $apiNotResult 接口返回数据错误 */
    public static $apiNotResult = 1015;

    /** @var int $serviceError 服务错误 */
    public static $serviceError = 1016;

    /** @var int $notAllowToken 非法或无效的Token */
    public static $notAllowToken = 1017;

    /** @var int $contentNotExists 资讯内容不存在 */
    public static $contentNotExists = 1018;

    /** @var int $nickNameIsHaved 昵称已经注册过了 */
    public static $nickNameIsHaved = 1019;

    /** @var int $actionIsDo 重复得操作 */
    public static $actionIsDo = 1020;

    /** @var int $weixinPayError 微信支付接口返回错误 */
    public static $weixinPayError = 1021;

    /** @var int $invalidAccess 无效范围 */
    public static $invalidAccess = 1021;
}