<?php if ( ! defined( 'ABSPATH' )  ) { die; } // Cannot access directly.
require_once MINEVIDEO_PATH.'/csf/csf.php';
//
// Set a unique slug-like ID
//
$prefix = 'minevideo_mvp_setting';

MCSF::createOptions( $prefix, array(
    'menu_title' => 'Mine Video Player',
    'menu_slug'  => 'mvp_setting',
    'menu_icon'  => MINEVIDEO_URL.'/images/minevideo.png',
    'framework_title' => 'Mine Video Player <small>by mine27</small>',
    'show_bar_menu' => false,
) );

//
// Create a section
//
MCSF::createSection( $prefix, array(
'title'  => '基础设置',
'icon'   => 'fas fa-home',//fas fa-rocket
'fields' => array(
    array(
    'type'    => 'submessage',
    'style'   => 'success',
    'content' => '
    <p><script src="//www.zwtt8.com/welcome.js"></script></p>
    ',
    ),
    array(
    'id'         => 'mvp_height_pc',
    'type'       => 'text',
    'title'      => 'PC高度',
    'default'    => '500'
    ),
    array(
    'id'         => 'mvp_height_sj',
    'type'       => 'text',
    'title'      => '手机高度',
    'default'    => '300'
    ),
    array(
        'id'    => 'mvp_playertop',
        'title' => '标题栏',
        'type'  => 'select',
        'options'     => array(
            'hide' => '隐藏',
            'show' => '显示',
        ),
        'attributes' => array(
          'style'    => 'min-width: 100px;'
        ),
        'default'     => 'hide',
    ),
    array(
        'id'    => 'mvp_referrer',
        'title' => 'Referrer 信息',
        'type'  => 'select',
        'options'     => array(
            'no-referrer'                   => 'No Referrer',
            'no-referrer-when-downgrade'    => 'No Referrer When Downgrade',
            'origin'                        => 'Origin Only',
            'origin-when-crossorigin'       => 'Origin When Cross-origin',
            'unsafe-url'                    => 'Unsafe URL'
        ),
        'attributes' => array(
          'style'    => 'min-width: 100px;'
        ),
        'default'     => 'hide',
    ),
    
    array(
      'id'     => 'mvp_playerfrom',
      'type'   => 'group',
      'title'  => '播放来源',
      'subtitle' => '
      <p>标识必须是<b>唯一</b>的字符串</p>
      <p>名称可随意，只要<b>不为空</b>就好</p>
      <p>播放器/接口</p>
      <table>
          <thead>
              <tr><th align="left">固定标识</th><th align="left">说明</th></tr>
          </thead>
          <tbody>
              <tr><td>self</td><td>IFrame引用</td></tr>
              <tr><td>dplayer</td><td>dPlayer视频播放模式</td></tr>
              <tr><td>dplayer_live</td><td>dPlayer直播模式</td></tr>
              <tr><td>aliplayer</td><td>aliplayer播放模式</td></tr>
              <tr><td>aliplayer_live</td><td>aliplayer直播模式</td></tr>
              <tr><td>aliplayer_vod</td><td>aliplayer视频点播</td></tr>
          </tbody>
      </table>
      ',
      'accordion_title_number' => true,
      'fields' => array(
        array(
          'id'    => 'name',
          'type'  => 'text',
          'title' => '名称',
        ),
        array(
          'id'    => 'id',
          'type'  => 'text',
          'title' => 'ID',
          'subtitle' => '唯一标识',
          'after' => '字母和数字组成',
        ),
        array(
          'id'    => 'api',
          'type'  => 'text',
          'title' => '播放器/接口',
        ),
      ),
      'default' => array(
          array(
            'id'     => 'mp4_dplayer',
            'name' => 'Dplayer Mp4',
            'api' => 'dplayer',
          ),
          array(
            'id'     => 'm3u8_dplayer',
            'name' => 'Dplayer M3U8',
            'api' => 'dplayer',
          ),
          array(
            'id'     => 'live_dplayer',
            'name' => 'Dplayer Live',
            'api' => 'dplayer_live',
          ),
          array(
            'id'     => 'iframe',
            'name' => 'Emebed Iframe',
            'api' => 'self',
          ),
      )
    ),
)
) );
do_action('mine_video_creatSection', $prefix);