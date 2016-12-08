<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;

class NetAuth extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'login:net';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '网络认证登录';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $username = "finance.app";
        $passwd = "Rmnuxtugxsnto4mx";
        $client = new Client(["cookies" => true]);
        $jar = new \GuzzleHttp\Cookie\CookieJar();
        $resp1 = $client->get("https://na.intra.sina.com.cn/portal/index_default.jsp", [
            "cookies" => $jar,
            "headers" => [
                "User-Agent" => "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:49.0) Gecko/20100101 Firefox/49.0",
                "x-insight" => "x-insight",
                "Upgrade-Insecure-Requests" => 1,
                "Host" => "na.intra.sina.com.cn",
                "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                "Accept-Language" => "zh-CN,en;q=0.8,zh;q=0.5,en-US;q=0.3",
                "Accept-Encoding" => "gzip, deflate",
                "Connection" => "keep-alive",
                "Cache-Control" => "max-age=0",
            ],
        ]);
        $params = [
            "appRootUrl" => "https://na.intra.sina.com.cn/portal/",
            "assignIpType" => "0",
            "basip" => "",
            "customPageId" => "0",
            "dcPwdNeedEncrypt" => "1",
            "entrance" => "null",
            "ifEnablePortalEntrace" => "true",
            "itUrlPortalShareKey" => "cqy56OhH6aJ5qMPHtteayw==",
            "itUrl" => "http://www.sina.com.cn",
            "language" => "",
            "loginVerifyCode" => "",
            "manualUrl" => "http://www.sina.com.cn",
            "manualUrlEncryptKey" => "cqy56OhH6aJ5qMPHtteayw==",
            "portalProxyIP" => "10.211.1.1",
            "portalProxyPort" => "50200",
            "pwdMode" => "0",
            "userDynamicPwddd" => "",
            "userName" => $username,
            "userPwd" => base64_encode($passwd),
            "userip" => "",
            "usermac" => "null",
            "userurl" => "",
            "wlannasid" => "",
            "wlanssid" => "",
        ];
        $resp = $client->request("POST", "https://na.intra.sina.com.cn/portal/pws?t=li", [
            "form_params" => $params,
            "cookies" => $jar,
            "headers" => [
                "User-Agent" => "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:49.0) Gecko/20100101 Firefox/49.0",
                "Referer" => "https://na.intra.sina.com.cn/portal/index_default.jsp",
                "X-Requested-With" => "XMLHttpRequest",
                "x-insight" => "x-insight",
                "Upgrade-Insecure-Requests" => 1,
                "Host" => "na.intra.sina.com.cn",
                "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                "Accept-Language" => "zh-CN,en;q=0.8,zh;q=0.5,en-US;q=0.3",
                "Accept-Encoding" => "gzip, deflate, br",
                "Connection" => "keep-alive",
                "Cache-Control" => "max-age=0",
                "Content-Type" => "application/x-www-form-urlencoded; charset=UTF-8"
            ],
        ]);
        $resp = strval($resp->getBody());
        dump(json_decode(urldecode(base64_decode($resp))));
    }

}
