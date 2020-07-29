<?php
/**
 * Created by PhpStorm.
 * User: jayding
 * Date: 2020/7/14
 * Time: 09:25
 */

return [
    // 消息通知地址
    'webhook'          => env('WEBHOOK', 'https://oapi.dingtalk.com/robot/send?access_token=9038692c3011861871e94a056faf2fc9ca408ffd861f0131b9f1092d24a5e8e5'),

    // 同花顺数据中心 http://data.10jqka.com.cn/ipo/bond/
    'source_data'      => [
        'ths' => env('KZZ_SOURCE_DATA', 'http://data.10jqka.com.cn/ipo/kzz/'),
        'jsl' => env('JSL_SOURCE_DATA', 'https://www.jisilu.cn/data/cbnew/cb_list/?___jsl=LST___t=' . intval(microtime(1) * 1000)),
    ],

    // 默认索引图
    'default_indexpic' => env('DEFAULT_INDEXPIC', 'http://img.m2oplus.nmtv.cn/20200712e3580bdee14c3faac0b4c364c39ae600.jpg'),

    // 集思录鉴权
    'header_auth'      => 'kbzw__Session=407nu991j0h8g2gpiof0o76br2; Hm_lvt_164fe01b1433a19b507595a43bf58262=1595209223; kbz_newcookie=1; kbzw_r_uname=jayding; kbzw__user_login=7Obd08_P1ebax9aX4cPvxeDl2pmcndHV7Ojg6N7bwNOM2NmnrsPRlKSr2c-w0NuSp5KsraermKnDpKmtytqir8eXnKTs3Ny_zYyqqaidrJ-YnaO2uNXQo67f293l4cqooaWSlonE2Nbhz-TQ5-GwicLa68figcTY1piww4HMmaaZ2J2swauKl7jj6M3VuNnbwNLtm6yVrY-qrZOgrLi1wcWhieXV4seWqNza3ueKkKTc6-TW3puvlaSRpaukqJSekqWvlbza0tjU35CsqqqmlKY.; Hm_lpvt_164fe01b1433a19b507595a43bf58262=1595209234',

    // 持仓可转债
    'owner'            => [110058, 123045, 128025, 128082, 128110],
];