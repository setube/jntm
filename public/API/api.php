<?php
/* KDboT
 * @banka2017 & KD·NETWORK
 */
$origin = '';
$AllowedReferrer = ''; // 'a.com|b.com|c.com' multiple domains is supported
header('content-type: application/json;charset=UTF-8');
header('access-control-allow-origin: ' . ($origin ? $origin : '*'));
header('access-control-allow-methods: GET');
header('X-XSS-Protection: 1; mode=block');
header('X-Frame-Options: sameorigin');
$get = $_GET;
$r = ['errno' => -1, 'msg' => 'Forbidden', 'data' => []];
if (($AllowedReferrer === '' || preg_match('/(^|.)(' . $AllowedReferrer . ')$/', $_SERVER['HTTP_HOST'])) && isset($get['m'])) {
    switch ($get['m']) {
        case 'getqrcode':
            $r['data'] = getBDUSS::getqrcode();
            if ($r['data']['sign']) {
                $r['errno'] = 0;
                $r['msg'] = 'Success';
            }
            break;
        case 'getbduss':
            if (isset($get['sign']) && $get['sign'] != '') {
                $r['data'] = getBDUSS::get_real_bduss($get['sign'], isset($get['full']));
                if ($r['data']) {
                    $r['errno'] = 0;
                    $r['msg'] = 'Success';
                }
            } else {
                $r['msg'] = 'Invalid QR Code or timeout';
            }
            break;
    }
}
echo json_encode($r, JSON_UNESCAPED_UNICODE);

class getBDUSS
{
    private static function scurl(string $url = 'localhost', int $timeout = 60): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        return curl_exec($ch);
    }
    public static function getqrcode(): array
    {
        $resp = ['sign' => null, 'imgurl' => null];
        $get_qrcode = json_decode(self::scurl('https://passport.baidu.com/v2/api/getqrcode?lp=pc'), true);
        if (isset($get_qrcode['imgurl']) && isset($get_qrcode['sign'])) {
            $resp = ['sign' => $get_qrcode['sign'], 'imgurl' => $get_qrcode['imgurl']];
        }
        return $resp;
    }
    public static function get_real_bduss(string $sign, bool $full): array
    {
        //status code
        //errno不等于0或1时需要要求更换二维码及sign
        //-1 更换二维码
        //0 进入下一步
        //1 无需操作
        //2 已确认
        $r = ['status' => 1, 'bduss' => '', 'msg' => '', 'fullmode' => false];
        $response = self::scurl('https://passport.baidu.com/channel/unicast?channel_id={$sign}&callback=', 35);
        if ($response) {
            $responseParse = json_decode(str_replace(array('(', ')'), '', $response), true);
            if (!$responseParse['errno']) {
                $channel_v = json_decode($responseParse['channel_v'], true);
                if ($channel_v['status']) {
                    $r['status'] = 0;
                    $r['msg'] = 'Continue';
                } else {
                    $s_bduss = json_decode(preg_replace("/'([^'']+)'/", '"$1"', str_replace('\\&', '&', self::scurl('https://passport.baidu.com/v3/login/main/qrbdusslogin?bduss=' . $channel_v['v'], 10))), true);
                    if ($s_bduss && $s_bduss['code'] === '110000') {
                        $r['status'] = 2;
                        $r['msg'] = 'Success';
                        $r['bduss'] = $s_bduss['data']['session']['bduss'];
                        if ($full) {
                            $r['fullmode'] = true;
                            $fullModeData = [];
                            $fullModeData['ptoken'] = $s_bduss['data']['session']['ptoken'];
                            $fullModeData['stoken'] = $s_bduss['data']['session']['stoken'];
                            $fullModeData['ubi'] = $s_bduss['data']['session']['ubi'];
                            $fullModeData['hao123Param'] = $s_bduss['data']['hao123Param'];
                            $fullModeData['username'] = $s_bduss['data']['user']['username'];
                            $fullModeData['userId'] = $s_bduss['data']['user']['userId'];
                            $fullModeData['portraitSign'] = $s_bduss['data']['user']['portraitSign'];
                            $fullModeData['displayName'] = $s_bduss['data']['user']['displayName'];
                            $fullModeData['stokenList'] = self::parseStoken($s_bduss['data']['session']['stokenList']);
                            $r['data'] = $fullModeData;
                        }
                    }
                }
            } else {
                $r['status'] = $responseParse['errno'];
            }
        } else {
            $r['status'] = -1;
            $r['msg'] = 'Invalid QR Code';
        }
        return $r;
    }
    public static function parseStoken(string $stokenList)
    {
        $tmpStokenList = [];
        foreach (json_decode(str_replace('&quot;', '"', $stokenList), true) as $stoken) {
            preg_match('/([\w]+)#(.*)/', $stoken, $tmpStoken);
            $tmpStokenList[$tmpStoken[1]] = $tmpStoken[2];
        }
        return $tmpStokenList;
    }
}