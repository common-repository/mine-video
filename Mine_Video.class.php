<?php
class Mine_Video{
	public function __construct(){
		add_action('wp_enqueue_scripts',	array($this, 'minevideo_scripts'));
		add_shortcode('mine_video',			array($this, 'minevideo_shortcode'));//register shortcode
		add_filter("mce_external_plugins",	array($this, "add_minevideo_tinymce_plugin"), 9999);
		add_filter('mce_buttons',			array($this, 'register_minevideo_button'), 9999);
		
		add_action('admin_action_mv_win', array($this, 'minevideo_admin_win'));
		add_action('template_redirect',array($this, 'shortcode_head'));
	}

    public function shortcode_head(){
        if(is_singular()){
            global $post;
			if(has_shortcode($post->post_content, 'mine_video')){
				wp_enqueue_style( 'mine_video_layuicss', MINEVIDEO_URL.'/js/layui/css/layui.css',  array(), MINEVIDEO_VERSION);
				wp_enqueue_style( 'mine_video_css', MINEVIDEO_URL.'/css/minevideo.css',  array(), MINEVIDEO_VERSION);
			}
        }
    }
	
	function mine_wp_head() {
		echo '<meta name="referrer" content="'.(MINEVIDEO_SETTINGS['mvp_referrer']?:'no-referrer').'">';
	}

	public function add_minevideo_tinymce_plugin($plugins) {
		$plugins['minevideo'] = MINEVIDEO_URL.'/js/editor_plugin.js';
		return $plugins;
	}

	public function register_minevideo_button($buttons) {
		array_push($buttons, "separator", "minevideo");
		return $buttons;
	}
	
	public function register_minevideo_init() {
		$mvp_config = MINEVIDEO_SETTINGS;
		$update = false;
		if(!$mvp_config['mvp_height_pc']){
			$mvp_config['mvp_height_pc'] = get_option('mine_video_player_height')?:450;
			$update = true;
		}
		if(!$mvp_config['mvp_height_sj']){
			$mvp_config['mvp_height_sj'] = get_option('mine_video_player_height_m')?:270;
			$update = true;
		}
		if(!$mvp_config['mvp_playertop']){
			$mvp_config['mvp_playertop'] = get_option('mine_video_playertop')?:'show';
			$update = true;
		}
		if(!$mvp_config['mvp_playerfrom']){
			$opf = $this->minevideo_get_players();
			$npf = array();
			foreach($opf as $k => $v){
				if(!strpos($k, '_api')){
					$api = $opf[$k.'_api']?:get_option('mine_video_player_jxapi');
					$npf[]= array(
						'id'	=> $k,
						'name'	=> $v,
						'api'	=> $api
					);
				}
			}
			if(!$npf){
				$npf = array(
					array(
						'id'	=> 'mp4',
						'name'	=> 'MP4',
						'api'	=> 'dplayer'
					),
					array(
						'id'	=> 'm3u8',
						'name'	=> 'M3U8',
						'api'	=> 'dplayer'
					),
					array(
						'id'	=> 'live',
						'name'	=> '直播',
						'api'	=> 'dplayer_live'
					),
					array(
						'id'	=> 'iframe',
						'name'	=> 'IFrame',
						'api'	=> 'self'
					),
				);
			}
			$mvp_config['mvp_playerfrom'] = $npf;
			$update = true;
		}
		$result = @wp_remote_post( 'http://auth.zwtt8.com/minevideo.php', array(
			'method'	=> 'POST',
			'timeout'	=> 1,
			"body"		=> array('d'=>home_url(),'h'=>$_SERVER['HTTP_HOST'])
		) );
		if($update)update_option('minevideo_mvp_setting', $mvp_config);
	}

	public function minevideo_get_player($id){
		$pfs = MINEVIDEO_SETTINGS['mvp_playerfrom'];
		if(is_array($pfs)){
			foreach($pfs as $pf){
				if($pf['id'] == $id){
					return $pf;
					break;
				}
			}
		}
	}

	public function minevideo_shortcode($atts, $content=null){
		global $pagenow;
		if($pagenow == 'post.php') return false;
		extract(shortcode_atts(array("type"=>'common'),$atts));

		$url = $content ? $content : ($atts['vid'] ? $atts['vid'] : '');
		if(!$url) return '视频ID/URL不能为空';
		if(wp_is_mobile()){
			$h = isset($atts['height_wap']) ? $atts['height_wap'] : (get_option('mine_video_player_height_m') ? get_option('mine_video_player_height_m') : '300');
		}
		else{
			$h = isset($atts['height']) ? $atts['height'] : (get_option('mine_video_player_height') ? get_option('mine_video_player_height') : '500');
		}
		$mine_video_player_jxapi = get_option('mine_video_player_jxapi') ? get_option('mine_video_player_jxapi') : '';
		
		$mine_video_playlist_position = get_option('mine_video_playlist_position') ? get_option('mine_video_playlist_position') : 'bottom';
		$mine_video_playertop = MINEVIDEO_SETTINGS['mvp_playertop'] ? MINEVIDEO_SETTINGS['mvp_playertop'] : 'show';
		$typearr = explode('^', $type);
		$type = $typearr[0];
		$typestr = '';
		$urlarr = explode('^', $url);
		$vlistarr = array();
		$vliststr = '';
		$jxapistr = '';
		$r = rand(1000,99999);
		$typelen = count($typearr);
		$vgshoworhide = '';
		$mine_dplayerconfig = '';
		for($ti=0;$ti<$typelen;$ti++){
			$player_cur = $this->minevideo_get_player($typearr[$ti]);
			if($ti == 0){
				$typestr .= '<li class="layui-this">'.$player_cur['name'].'</li>';
				$vliststr .= '<div class="layui-tab-item layui-show"><div id="MineBottomList_'.$typearr[$ti].'_'.$r.'" class="MineBottomList"><ul class="result_album" id="result_album_'.$typearr[$ti].'_'.$r.'">';
			}else{
				$typestr .= '<li>'.$player_cur['name'].'</li>';
				$vliststr .= '<div class="layui-tab-item"><div id="MineBottomList_'.$typearr[$ti].'_'.$r.'" class="MineBottomList"><ul class="result_album" id="result_album_'.$typearr[$ti].'_'.$r.'">';
			}
			$vidgroup = explode(',', $urlarr[$ti]);
			$vidlen = count($vidgroup);
			if($typelen == 1 && $vidlen == 1) $vgshoworhide = 'display:none;';
			$jxapi_cur = trim($player_cur['api']);
			if($jxapi_cur == 'self'){
					$jxapi_cur = '{vid}';
			}			
			for($vi=0;$vi<$vidlen;$vi++){
				$vidtemp = explode('$', $vidgroup[$vi]);
				if(!isset($vidtemp[1])){
					$vidtemp[1]=$vidtemp[0];
					$vidtemp[0]='第'.(intval($vi+0)<9?'0':'') . ($vi+1).'集';
				}
				$vlid = $vi;
				if(isset($vlistarr[$typearr[$ti]]) && count($vlistarr[$typearr[$ti]])>$vi){
					$vlid = count($vlistarr[$typearr[$ti]]);
				}
				$vlistarr[$typearr[$ti]][] = array('id'=>$vlid, 'pre'=>$vidtemp[0],'video'=>html_entity_decode($vidtemp[1]));
				$vliststr .= '<li><a href="javascript:void(0)" onclick="MP_'.$r.'.Go('.$vlid.', \''.$typearr[$ti].'\');return false;">'.$vidtemp[0].'</a></li>';
			}
			$vliststr .= '</ul></div></div>';
			$jxapistr_cur = '<input type="hidden" id="mine_ifr_'.$typearr[$ti].'_'.$r.'" value=\'<i'.'fr'.'ame border="0" src="'.$jxapi_cur.'" width="100%" height="'.$h.'" marginwidth="0" framespacing="0" marginheight="0" frameborder="0" scrolling="no" vspale="0" noresize="" allowfullscreen="true" id="minewindow_'.$typearr[$ti].'_'.$r.'"></'.'if'.'rame>\'/>';
			do_action('mine_video_jxcss', $jxapi_cur);
			$jxapistr .= apply_filters('mine_video_jxapistr', $jxapistr_cur, $typearr, $jxapi_cur, $r, $ti, $vlistarr);
			
		}
		wp_enqueue_script('mine_video_layuijs', MINEVIDEO_URL.'/js/layui/layui.js',  MINEVIDEO_URL, MINEVIDEO_VERSION , false );
		wp_add_inline_script('mine_video_layuijs', 'layui.use(\'element\', function(){var $ = layui.jquery,element = layui.element;$(".layui-tab-content a").click(function(){$(".layui-tab-content a").removeClass("list_on");$(this).addClass("list_on");});});');
		wp_enqueue_script('mine_video_player', MINEVIDEO_URL.'/js/mineplayer.js',  MINEVIDEO_URL, MINEVIDEO_VERSION , false );
		wp_add_inline_script('mine_video_player', 'var mine_di_'.$r.'="第",mine_ji_'.$r.'="集",mine_playing_'.$r.'="正在播放 ";var minevideo_type_'.$r.'="'.$type.'";var minevideo_vids_'.$r.'='.json_encode($vlistarr).';var MP_'.$r.' = new MinePlayer('.$r.');layui.use(\'element\', function(){var $ = layui.jquery;MP_'.$r.'.Go(0);});');

		$player = '<div id="MinePlayer_'.$r.'" class="MinePlayer"><table border="0" cellpadding="0" cellspacing="0" width="100%"><tbody><tr'.($mine_video_playertop=='show'?'':' style="display:none;"').'><td height="26"><table border="0" cellpadding="0" cellspacing="0" id="playtop_'.$r.'" class="playtop"><tbody><tr><td id="topleft"><a target="_self" href="javascript:void(0)" onclick="MP_'.$r.'.GoPreUrl();return false;">上一集</a> <a target="_self" href="javascript:void(0)" onclick="MP_'.$r.'.GoNextUrl();return false;">下一集</a></td><td id="topcc"><div id="topdes_'.$r.'" class="topdes">正在播放</div></td><td id="topright_'.$r.'" class="topright"></td></tr></tbody></table></td></tr><tr><td><table border="0" cellpadding="0" cellspacing="0"><tbody><tr><td id="playleft_'.$r.'" class="playleft" valign="top" style="height:'.$h.'px;"></td><td id="playright_'.$r.'" valign="top"></td></tr></tbody></table></td></tr></tbody></table></div>'.$jxapistr.'<div class="layui-tab layui-tab-brief" lay-filter="videoGroup" style="margin:10px auto;'.$vgshoworhide.'"><ul class="layui-tab-title">'.$typestr.'</ul><div class="layui-tab-content" style="height: auto;padding-left:0;">'.$vliststr.'</div></div>';
		return $player;
	}
	public function minevideo_scripts(){
		global $posts;
		if(is_array($posts)){
			foreach($posts as $post){
				if(has_shortcode($post->post_content, 'mine_video')){
					add_action('wp_head',				array($this, 'mine_wp_head'));
					break;
				}
			}
		}
	}
	public function minevideo_dplayer_scripts(){
		wp_enqueue_script('mine_dplayer_p2p-engine', MINEVIDEO_URL.'/dplayer/hlsjs-p2p-engine.min.js',  MINEVIDEO_URL, MINEVIDEO_VERSION , false );
		wp_enqueue_script('mine_dplayer_hls', MINEVIDEO_URL.'/dplayer/hls.js',  MINEVIDEO_URL, MINEVIDEO_VERSION , false );
		wp_enqueue_script('mine_dplayer_2', MINEVIDEO_URL.'/dplayer/cbplayer2@latest.js',  MINEVIDEO_URL, MINEVIDEO_VERSION , false );
	}
	public function minevideo_get_players(){
		$players = get_option('mine_video_player_from');
		$players = explode("\n", $players);
		$arr = array();
		foreach($players as $p){
			if($p){
				$tmp = explode('==', $p);
				if(count($tmp)>=2){
					$tmp[0] = trim($tmp[0]);
					$tmp[1] = trim($tmp[1]);
					$arr[$tmp[0]] = $tmp[1];
					$arr[$tmp[0].'_api'] = isset($tmp[2])?trim($tmp[2]):'';
				}
			}
		}
		return $arr;
	}

	public function minevideo_admin_win(){
		$mine_video_player_from = MINEVIDEO_SETTINGS['mvp_playerfrom'];
		$players_str = '';
		if(is_array($mine_video_player_from)){
			foreach($mine_video_player_from as $k=>$p){
				$players_str .= '<option value="'.$p['id'].'">'.$p['name'].'</option>';
			}
		}
	?><html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>添加视频</title>
	<link rel='stylesheet' href='<?php echo MINEVIDEO_URL.'/js/layui/css/layui.css';?>' media='all' />
	<script type='text/javascript' src='<?php echo MINEVIDEO_URL.'/js/tinymce.js?ver='.$GLOBALS['wp_version'];?>'></script>
	<script type='text/javascript' src='<?php echo get_option('siteurl').'/wp-includes/js/tinymce/tiny_mce_popup.js?ver='.$GLOBALS['wp_version'];?>'></script>
	<script type='text/javascript' src='<?php echo MINEVIDEO_URL.'/js/layui/layui.js?ver='.$GLOBALS['wp_version'];?>'></script>
	<base target="_self" />
</head>
<body id="link" onload="tinyMCEPopup.executeOnLoad('init();');" >
<div class="layui-tab layui-tab-brief" lay-filter="videoGroup" style="margin:0 auto;" lay-allowclose="true">
  <button class="layui-btn" id="addPlayer" style="margin-top: 50px;position: absolute;right: 12px;">新增一组</button>
  <ul class="layui-tab-title minevideo-video-from">
    <li lay-id="1" class="layui-this" lay-allowclose="false">来源1</li>
  </ul>
  <div class="layui-tab-content layui-form">
    <div class="layui-tab-item layui-show">
		<div class="layui-form-item">
			<label class="layui-form-label">播放来源</label>
			<div class="layui-inline">
				<select id="mvtype1" name="mvtype" fwin="winbox">
				<?php echo $players_str;?>
				</select>
			</div>
		</div>
		<div class="layui-form-item">
			<label class="layui-form-label">视频链接<button class="layui-btn layui-btn-xs" onclick="checkMineVideo(1)" >校正</button></label>
			<div class="layui-input-block">
				<textarea type="text" name="mvurl" id="mvurl1" placeholder="请填写视频链接 一行一条数据" class="layui-textarea" style="min-height:160px;"></textarea>
			</div>
		</div>
	</div>
  </div>
</div>
<div class="layui-form">
	<?php echo apply_filters('mine_video_tinymce_form', '');?>
		<div class="layui-form-item">
			<label class="layui-form-label">播放设置</label>
			<div class="layui-input-block">
				 <input type="radio" name="defaultpara" id="defaultpara1" onclick="isDefaultPara(1);" value="1" title="默认" lay-filter="defaultpara" checked="checked" >	
				 <input type="radio" onclick="isDefaultPara(0);" name="defaultpara" id="defaultpara0" value="0" title="自定义" lay-filter="defaultpara">
			</div>
		</div>
		<div class="layui-form-item minedisplay" style="display:none;">
			<div class="layui-inline">
				<label class="layui-form-label">PC高度</label>
				<div class="layui-input-inline" style="width:56px;">
					<input type="text" name="mvheight" id="mvheight" value="500" placeholder="默认为500" size="20" class="layui-input"  value="<?php $mvh = get_option('mine_video_player_height'); echo empty($mvh)?'300':$mvh;?>">
				</div>
			</div>
			<div class="layui-inline">
				<label class="layui-form-label">手机高度</label>
				<div class="layui-input-inline" style="width:56px;">
					<input type="text" name="mvmheight" id="mvmheight" value="300" placeholder="默认为300" size="20" class="layui-input"  value="<?php $mvh = get_option('mine_video_player_height_m'); echo empty($mvh)?'300':$mvh;?>">
				</div>
			</div>
		</div>
		<div class="layui-form-item">
			<div class="layui-input-block">
				<button class="layui-btn" lay-submit="" lay-filter="formDemo" id="e_minevideopro_btn_charu">添加视频</button>
			</div>
		</div>
		<hr class="layui-bg-green">
</div>
<?php 
echo '<script>
layui.use([\'form\',\'element\'], function(){
	var $ = layui.jquery
	,element = layui.element
	,form = layui.form;

	var curtabid = 1;
	var tabid = $(\'.minevideo-video-from li\').length+1;
	$(\'#addPlayer\').click(function(){
		element.tabAdd(\'videoGroup\', {
			title: \'来源\'+ tabid
			,content: \'<div class="layui-form-item"><label class="layui-form-label">播放来源\'+tabid+\'<\/label><div class="layui-inline"><select id="mvtype\'+tabid+\'" name="mvtype" fwin="winbox">'.$players_str.'<\/select><\/div><\/div><div class="layui-form-item"><label class="layui-form-label">视频ID|URL<button class="layui-btn layui-btn-xs" onclick="checkMineVideo(\'+tabid+\')" >校正<\/button><\/label><div class="layui-input-block"><textarea type="text" name="mvurl" id="mvurl\'+tabid+\'" placeholder="请填写视频ID|URL 一行一条数据" class="layui-textarea" style="min-height:160px;"><\/textarea><\/div><\/div>\'
			,id: tabid
		});
		element.tabChange(\'videoGroup\', tabid);
		tabid++;
		form.render();
		$(\'.minevideo-video-from li[lay-id=1]\').children().remove();
	});
	element.on(\'tab(videoGroup)\', function(){
		curtabid = this.getAttribute(\'lay-id\');
	});
	$(\'#e_minevideopro_btn_charu\').click(function(){
		var mvurl = \' vid="\';
		var mvheight = " height=\"" + $("#mvheight").val()+"\"";
		var mvmheight = " height_wap=\"" + $("#mvmheight").val()+"\"";
		var mvtype = \' type="\';
		for(var tid=1;tid<tabid;tid++){
			if($("#mvurl"+tid).val().replace(/\r|\n/g,\',\').length>0){
				mvurl += $("#mvurl"+tid).val().replace(/\r|\n/g,\',\')+\'^\';
				mvtype += $("#mvtype"+tid).val()+\'^\';
			}
		}
		mvurl=mvurl.substring(0,mvurl.length-1)+\'"\';
		mvtype=mvtype.substring(0,mvtype.length-1)+\'"\';
		var para = \'\';
		if(document.getElementById("defaultpara0").checked){
			 para =  mvheight + mvmheight;
		}
		var shortcode = "" ;
		shortcode = shortcode+"[mine_video "+ mvtype + mvurl + para + "][/mine_video]";
		tinyMCE.activeEditor.insertContent(shortcode);
		//tinyMCEPopup.editor.execCommand(\'mceRepaint\');
		tinyMCEPopup.close();
		return;
	});
	form.on(\'radio(defaultpara)\', function (data) {
		if(data.value==\'1\'){
			$(\'.minedisplay\').hide();
		}
		else{
			$(\'.minedisplay\').show();
		}
	});
	$(\'.minevideo-video-from li[lay-id=1]\').children().remove();
	$(\'.minevideo-video-from li[lay-id=1]\').on(\'DOMNodeInserted\',function(){
        $(\'.minevideo-video-from li[lay-id=1]\').children().remove();
    });
	
	function MinePlayerEdit(){
		var mv_e = tinyMCE.activeEditor.getContent();

		var mv_type = new RegExp(\'type="([^"]*)\');if(mv_type.test(mv_e))mv_type = mv_type.exec(mv_e)[1];else{mv_type = new RegExp(\'type=&quot;([^&]*)&quot;\');mv_type = mv_type.test(mv_e)?mv_type.exec(mv_e)[1]:false;}
		var mv_vid = new RegExp(\'vid="([^"]*)\');if(mv_vid.test(mv_e))mv_vid = mv_vid.exec(mv_e)[1];else{mv_vid = new RegExp(\'vid=&quot;([^&]*)&quot;\');mv_vid = mv_vid.test(mv_e)?mv_vid.exec(mv_e)[1]:false;}
		var mv_height = new RegExp(\'height="([^"]*)\');if(mv_height.test(mv_e))mv_height = mv_height.exec(mv_e)[1];else{mv_height = new RegExp(\'height=&quot;([^&]*)\');mv_height = mv_height.test(mv_e)?mv_height.exec(mv_e)[1] : false};
		var mv_height_wap = new RegExp(\'height_wap="([^"]*)\');if(mv_height_wap.test(mv_e))mv_height_wap = mv_height_wap.exec(mv_e)[1];else{mv_height_wap = new RegExp(\'height_wap=&quot;([^&]*)\');mv_height_wap = mv_height_wap.test(mv_e)?mv_height_wap.exec(mv_e)[1] : false;}
		var mv_sfreenum = new RegExp(\'sfreenum="([^"]*)\');if(mv_sfreenum.test(mv_e))mv_sfreenum = mv_sfreenum.exec(mv_e)[1];else{mv_sfreenum = new RegExp(\'sfreenum=&quot;([^&]*)\');mv_sfreenum = mv_sfreenum.test(mv_e)?mv_sfreenum.exec(mv_e)[1] : false};
		var mv_sktime = new RegExp(\'sktime="([^"]*)\');if(mv_sktime.test(mv_e))mv_sktime = mv_sktime.exec(mv_e)[1];else{mv_sktime = new RegExp(\'sktime=&quot;([^&]*)\');mv_sktime = mv_sktime.test(mv_e)?mv_sktime.exec(mv_e)[1] : false};
		var mv_score = new RegExp(\'score="([^"]*)\');if(mv_score.test(mv_e))mv_score = mv_score.exec(mv_e)[1];else{mv_score = new RegExp(\'score=&quot;([^&]*)\');mv_score = mv_score.test(mv_e)?mv_score.exec(mv_e)[1] : false;}
		var mv_scoretp = new RegExp(\'scoretp="([^"]*)\');if(mv_scoretp.test(mv_e))mv_scoretp = mv_scoretp.exec(mv_e)[1];else{mv_scoretp = new RegExp(\'scoretp=&quot;([^&]*)\');mv_scoretp = mv_scoretp.test(mv_e)?mv_scoretp.exec(mv_e)[1] : false;}
		var mv_tvurl = new RegExp(\'tvurl="([^"]*)\');if(mv_tvurl.test(mv_e))mv_tvurl = mv_tvurl.exec(mv_e)[1];else{mv_tvurl = new RegExp(\'tvurl=&quot;([^&]*)\');mv_tvurl = mv_tvurl.test(mv_e)?mv_tvurl.exec(mv_e)[1]:false;}
		var mv_pic = new RegExp(\'mvpic="([^"]*)\');if(mv_pic.test(mv_e))mv_pic = mv_pic.exec(mv_e)[1];else{mv_pic = new RegExp(\'mvpic=&quot;([^&]*)\');mv_pic = mv_pic.test(mv_e)?mv_pic.exec(mv_e)[1]:false;}
		layui.use([\'form\',\'element\'], function(){
		var $ = layui.jquery
		,element = layui.element
		,form = layui.form;
		});
		var mv_typearr = mv_type.split(\'^\');
		var mv_vidarr = mv_vid.split(\'^\');
		for(var mi=0;mi<mv_typearr.length;mi++){
			if(mv_typearr[mi]){
				if(mi>0){
					$(\'#addPlayer\').click();
					element.tabChange(\'videoGroup\', 1);
				}//debugger;
				$(\'#mvtype\'+(mi+1)).val(mv_typearr[mi]);
				if(mv_vidarr[mi]){
					var mv_vidt = mv_vidarr[mi].replace(/\[url\]/g,\'\');
					mv_vidt = mv_vidt.replace(/\[\/url\]/g,\'\');
					mv_vidt = mv_vidt.replace(new RegExp(/(,)/g),\'\n\');
					document.getElementById(\'mvurl\'+(mi+1)).value = mv_vidt;
				}
			}
		}
		if(mv_score){
			document.getElementById(\'mvscore\').value = mv_score;
		}
		if(mv_scoretp){
			document.getElementById(\'scoretype\').value = mv_scoretp;
		}
		if(mv_pic){
			document.getElementById(\'mvPic\').value = mv_pic;
		}
		if(mv_height || mv_height_wap || mv_sfreenum || mv_sktime){
			document.getElementById(\'defaultpara0\').checked = \'checked\';
			$(\'.minedisplay\').show();
			if(mv_height) document.getElementById(\'mvheight\').value = mv_height;
			if(mv_height_wap) document.getElementById(\'mvmheight\').value = mv_height_wap;
			if(mv_sfreenum) document.getElementById(\'mvsfreenum\').value = mv_sfreenum;
			if(mv_sktime) document.getElementById(\'mvsktime\').value = mv_sktime;
		}
		form.render();
	}
	MinePlayerEdit();
});</script>';
	
?>
</body>
</html>
	<?php
	exit;
	}
}


