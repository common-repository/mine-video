<?php
if(!defined('ABSPATH'))exit;

define('MINEVIDEODPLAYER_URL', plugins_url('', __FILE__));
define('MINEVIDEODPLAYER_VERSION', '1.26');

function mine_video_get_dplayerconfig(){
    $mine_dplayerconfig['autoplay']	= MINEVIDEO_SETTINGS['mvp_dplayer_autoplay']=='false'?false:true;
    $mine_dplayerconfig['logo']		= MINEVIDEO_SETTINGS['mvp_dplayer_logo']?:'';
    $mine_dplayerconfig['lang']		= MINEVIDEO_SETTINGS['mvp_dplayer_lang']?:'zh-cn';
    $mine_dplayerconfig['preload']		= MINEVIDEO_SETTINGS['dplayerconfig']['preload']?:'auto';
    $mine_dplayerconfig['theme']		= MINEVIDEO_SETTINGS['dplayerconfig']['theme']?:'#b7daff';
    $mine_dplayerconfig['loop']		= MINEVIDEO_SETTINGS['dplayerconfig']['loop']?true:false;
    $mine_dplayerconfig['volume']		= MINEVIDEO_SETTINGS['dplayerconfig']['volume']?:'0.7';
    $mine_dplayerconfig['screenshot']		= MINEVIDEO_SETTINGS['dplayerconfig']['screenshot']?true:false;
    $mine_dplayerconfig['airplay']		= MINEVIDEO_SETTINGS['dplayerconfig']['airplay']?true:false;
    $mine_dplayerconfig['hotkey']		= MINEVIDEO_SETTINGS['dplayerconfig']['hotkey']?true:false;
    $contextmenu = '';
    if(MINEVIDEO_SETTINGS['mvp_dplayer_contextmenu']){
        $contextmenu = array_values(MINEVIDEO_SETTINGS['mvp_dplayer_contextmenu'])?:array();
        $mine_dplayerconfig['contextmenu'] = $contextmenu;
    }
    $mine_dplayerconfig = json_encode($mine_dplayerconfig);
    return $mine_dplayerconfig;
}

//dplayer
function mine_video_jxapistr_dplayer($jxapistr_cur, $typearr, $jxapi_cur, $r, $ti, $vlistarr){
    $mine_dplayerconfig = mine_video_get_dplayerconfig();
    if(strtolower($jxapi_cur) == 'dplayer'){
        global $current_user;
        $hashls = false;
        $hasflv = false;
        foreach($vlistarr as $tavs){
            foreach($tavs as $tav){
                if(strpos($tav['video'], '.m3u8') > 0){
                    $hashls = true;
                    continue;
                }
                if(strpos($tav['video'], '.flv')){
                    $hasflv = true;
                    continue;
                }
            }
        }
        if($hasflv)wp_enqueue_script('mine_dplayer_flv', MINEVIDEODPLAYER_URL.'/dplayer/flv.min.js',  MINEVIDEODPLAYER_URL, MINEVIDEODPLAYER_VERSION , false );
        if($hashls)wp_enqueue_script('mine_dplayer_hls', MINEVIDEODPLAYER_URL.'/dplayer/hls.min.js',  MINEVIDEODPLAYER_URL, MINEVIDEODPLAYER_VERSION , false );
        wp_enqueue_script('mine_dplayer_2', MINEVIDEODPLAYER_URL.'/dplayer/DPlayer.min.js',  MINEVIDEODPLAYER_URL, MINEVIDEODPLAYER_VERSION , false );
        $danmu = '';
        $danmuconfig = '';
        if(isset(MINEVIDEO_SETTINGS['mvp_dplayer_danmu']) && MINEVIDEO_SETTINGS['mvp_dplayer_danmu']['status'] == 'true'){
            wp_enqueue_script('mine_md5', 'https://cdn.bootcdn.net/ajax/libs/blueimp-md5/2.18.0/js/md5.min.js',  MINEVIDEODPLAYER_URL, MINEVIDEODPLAYER_VERSION , false );
            $danmuconfig = 'window.dplayerconfig_'.$r.'.danmaku = {id:md5(cur.video),api:\''.MINEVIDEO_SETTINGS['mvp_dplayer_danmu']['api'].'\'};';
        }
        elseif(isset(MINEVIDEO_SETTINGS['mvp_dplayer_luping']) && $current_user->ID>0){
            $luping = MINEVIDEO_SETTINGS['mvp_dplayer_luping'];
            if($luping['status'] == 'true'){
                $danmustr = str_replace(array('{userid}', '{username}'), array($current_user->ID, $current_user->user_login), $luping['content']);
                $danmu = 'dp.danmaku.show();setInterval(function(){dp.danmaku.draw({text:\'<div style="margin-top:\'+Math.floor(Math.random()*10)+\'0%;">'.$danmustr.'</div>\', color: \'#\' + Math.random().toString(16).substr(2, 6).toUpperCase(), type: \'right\'});}, '.$luping['time'].');dp.on(\'danmaku_hide\',function(){dp.danmaku.show();});dp.on(\'danmaku_opacity\',function(){dp.danmaku.opacity(1);});';
                $danmuconfig = 'window.dplayerconfig_'.$r.'.danmaku = {id:\'mine_video_player\',api:\''.MINEVIDEO_URL.'/dplayer/danmuku.txt?\'};';
            }
        }
        $autoheight = '';
        if(isset(MINEVIDEO_SETTINGS['mvp_dplayer_autoheight']) && MINEVIDEO_SETTINGS['mvp_dplayer_autoheight'] == 'true'){
            $autoheight = 'document.getElementById(\'playleft_\'+pid).style.height=\'auto\';';
        }
        wp_add_inline_script('mine_dplayer_2',str_replace(array("\t","\r","\n"),'','
        var dplayerconfig_'.$r.'='.$mine_dplayerconfig.';
        var dplayer_'.$r.';
        function mine_dplayer_'.$r.'(pid,cur){
            if(!window.dplayer_'.$r.'){
            document.getElementById(\'playleft_\'+pid).innerHTML = \'\';
            window.dplayerconfig_'.$r.'.container = document.getElementById("playleft_"+pid);
            window.dplayerconfig_'.$r.'.video = {url:(cur.video)};
            '.$danmuconfig.'
            window.dplayer_'.$r.' = new DPlayer(window.dplayerconfig_'.$r.');
            var dp = window.dplayer_'.$r.';
            '.$danmu.'
            '.$autoheight.'
        }else{
            window.dplayer_'.$r.'.switchVideo({url:(cur.video)});
            window.dplayer_'.$r.'.play();
        }}'));
        return '<input type="hidden" id="mine_ifr_'.$typearr[$ti].'_'.$r.'" value=\''.$jxapi_cur.'\'/>';
    }
    return $jxapistr_cur;
}
add_filter('mine_video_jxapistr', 'mine_video_jxapistr_dplayer', 10, 6);

//dplayer_live
function mine_video_jxapistr_dplayer_live($jxapistr_cur, $typearr, $jxapi_cur, $r, $ti){
    $mine_dplayerconfig = mine_video_get_dplayerconfig();
    if(strtolower($jxapi_cur) == 'dplayer_live'){
        wp_enqueue_script('mine_dplayer_hls', MINEVIDEODPLAYER_URL.'/dplayer/hls.min.js',  MINEVIDEODPLAYER_URL, MINEVIDEODPLAYER_VERSION , false );
        wp_enqueue_script('mine_dplayer_2', MINEVIDEODPLAYER_URL.'/dplayer/DPlayer.min.js',  MINEVIDEODPLAYER_URL, MINEVIDEODPLAYER_VERSION , false );
        $autoheight = '';
        if(isset(MINEVIDEO_SETTINGS['mvp_dplayer_autoheight']) && MINEVIDEO_SETTINGS['mvp_dplayer_autoheight'] == 'true'){
            $autoheight = 'document.getElementById(\'playleft_\'+pid).style.height=\'auto\';';
        }
        wp_add_inline_script('mine_dplayer_2',str_replace(array("\t","\r","\n"),'','
        var dplayerconfig_'.$r.'='.$mine_dplayerconfig.';
        var dplayer_'.$r.';
        function mine_dplayer_live_'.$r.'(pid,cur){
            if(!window.dplayer_'.$r.'){
            document.getElementById(\'playleft_\'+pid).innerHTML = \'\';
            window.dplayerconfig_'.$r.'.live = true;;
            window.dplayerconfig_'.$r.'.container = document.getElementById("playleft_"+pid);
            window.dplayerconfig_'.$r.'.video = {url:unescape(cur.video)};
            window.dplayer_'.$r.' = new DPlayer(window.dplayerconfig_'.$r.');
            window.dplayer_'.$r.'.play();
            '.$autoheight.'
        }else{
            window.dplayer_'.$r.'.switchVideo({url:unescape(cur.video)});
            window.dplayer_'.$r.'.play();
        }}'));
        return '<input type="hidden" id="mine_ifr_'.$typearr[$ti].'_'.$r.'" value=\''.$jxapi_cur.'\'/>';
    }
    return $jxapistr_cur;
}
add_filter('mine_video_jxapistr', 'mine_video_jxapistr_dplayer_live', 10, 5);

add_action('mine_video_creatSection', 'mvp_setting_dplayer', 10, 1);
function mvp_setting_dplayer($prefix){
    MCSF::createSection( $prefix, array(
        'id'    => 'mvp_dplayerconfig',
        'title' => 'DPlayer配置',
        'icon'  => 'fab fa-dochub',
      ) );
    MCSF::createSection( $prefix, array(
        'parent'     => 'mvp_dplayerconfig',
        'title'  => '基础设置',
        'icon'   => 'fab fa-dochub',
        'fields' => array(
            array(
            'type'    => 'submessage',
            'style'   => 'success',
            'content' => '
            <p>欢迎使用 Mine Video Player
            ',
            ),
            array(
                'id'    => 'mvp_dplayer_autoheight',
                'type'  => 'select',
                'title' => '高度自适应',
                'options'     => array(
                    'true'      => '启用',
                    'false'      => '禁用',
                ),
                'default' => 'false'
            ),
            array(
            'id'           => 'mvp_dplayer_logo',
            'type'         => 'upload',
            'title'        => '播放器Logo',
            'library'      => 'image',
            'button_title' => 'Upload',
            ),
            array(
                'id'    => 'mvp_dplayer_lang',
                'title' => '语言',
                'type'  => 'select',
                'options'     => array(
                    'zh-cn'      => '简体中文',
                    'en'      => '英语',
                    'zh-tw'    => '繁体中文'
                ),
                'attributes' => array(
                'style'    => 'min-width: 100px;'
                ),
                'default'     => 'zh-cn',
            ),
            array(
                'id'    => 'mvp_dplayer_autoplay',
                'type'  => 'select',
                'title' => '自动播放',
                'options'     => array(
                    'true'      => '是',
                    'false'      => '否',
                ),
                'default' => 'false'
            ),
            array(
                'id'     => 'mvp_dplayer_contextmenu',
                'type'   => 'group',
                'title'  => '右键菜单',
                'subtitle' => '',
                'accordion_title_number' => true,
                'fields' => array(
                array(
                    'id'    => 'text',
                    'type'  => 'text',
                    'title' => '名称',
                ),
                array(
                    'id'    => 'link',
                    'type'  => 'text',
                    'title' => '链接',
                ),
                ),
                'default' => array(
                    array(
                    'text'     => 'Mine Video Player',
                    'link' => 'https://wordpress.org/plugins/mine-video/',
                    ),
                )
            ),
            array(
                'id'        => 'dplayerconfig',
                'type'      => 'fieldset',
                'title'     => '配置',
                'fields'    => array(
                    array(
                        'id'    => 'theme',
                        'type'  => 'color',
                        'title' => '主色',
                        'default' => '#b7daff'
                    ),
                    array(
                        'id'    => 'loop',
                        'type'  => 'switcher',
                        'title' => '循环播放',
                        'text_on'    => '启用',
                        'text_off'   => '禁用',
                        'default' => false
                    ),
                    array(
                        'id'    => 'preload',
                        'title' => '预加载',
                        'type'  => 'select',
                        'options'     => array(
                            'auto' => 'auto',
                            'none' => 'none',
                            'metadata' => 'metadata',
                        ),
                        'attributes' => array(
                        'style'    => 'min-width: 100px;'
                        ),
                        'default'     => 'auto',
                    ),
                    array(
                        'id'       => 'volume',
                        'type'     => 'slider',
                        'title'    => '默认音量',
                        'min'      => 0.1,
                        'max'      => 1,
                        'step'     => 0.1,
                        'default'  => 0.7,
                    ),
                    array(
                        'id'    => 'screenshot',
                        'type'  => 'switcher',
                        'title' => '截图',
                        'subtitle' => '启用屏幕截图，如果为true，则视频源必须启用跨域',
                        'text_on'    => '启用',
                        'text_off'   => '禁用',
                        'default' => false
                    ),
                    array(
                        'id'    => 'airplay',
                        'type'  => 'switcher',
                        'title' => 'AirPlay',
                        'subtitle' => '在Safari中启用airplay',
                        'text_on'    => '启用',
                        'text_off'   => '禁用',
                        'default' => true
                    ),
                    array(
                        'id'    => 'hotkey',
                        'type'  => 'switcher',
                        'title' => '热键',
                        'subtitle' => '启用热键支持FF、FR、音量控制、播放和暂停',
                        'text_on'    => '启用',
                        'text_off'   => '禁用',
                        'default' => true
                    ),
                ),
            ),
        )
    ));
    MCSF::createSection( $prefix, array(
        'parent'     => 'mvp_dplayerconfig',
        'title'  => '弹幕',
        'icon'   => 'fab fa-dochub',
        'fields' => array(
            array(
                'id'        => 'mvp_dplayer_danmu',
                'type'      => 'fieldset',
                'title'     => '弹幕',
                'subtitle'  => '启用弹幕时，防录屏功能将失效',
                'fields'    => array(
                    array(
                        'id'    => 'status',
                        'type'  => 'radio',
                        'title' => '状态',
                        'inline' => true,
                        'options'    => array(
                            'true'	=> '启用',
                            'false'	=> '禁用'
                        ),
                        'default' => false,
                    ),
                    array(
                        'id'    => 'api',
                        'type'  => 'text',
                        'title' => '弹幕api',
                        'default' => ''
                    ),
                )
            ),
        )
    ));
    MCSF::createSection( $prefix, array(
        'parent'     => 'mvp_dplayerconfig',
        'title'  => '防录屏',
        'icon'   => 'fab fa-dochub',
        'subtitle'  => '启用弹幕时，防录屏功能将失效',
        'fields' => array(
            array(
                'id'        => 'mvp_dplayer_luping',
                'type'      => 'fieldset',
                'title'     => '防录屏',
                'fields'    => array(
                    array(
                    'id'    => 'status',
                    'type'  => 'radio',
                    'title' => '状态',
                    'inline' => true,
                    'options'    => array(
                        'true'	=> '启用',
                        'false'	=> '禁用'
                    ),
                    ),
                    array(
                    'id'    => 'time',
                    'type'  => 'text',
                    'title' => '间隔',
                    'default' => '3000'
                    ),
                    array(
                    'id'    => 'content',
                    'type'  => 'text',
                    'title' => '内容',
                    'default' => '用户：{userid}',
                    'desc' => '支持标签{username}{userid}'
                    ),
                )
            ),
        )
    ));
}
?>