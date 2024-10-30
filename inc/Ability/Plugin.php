<?php
namespace MineVideo\Ability;

class Plugin
{
    protected $plugin_basename = "mine-video/mine-video.php";
    public function __construct()
    {
        add_filter('plugin_action_links_' . $this->plugin_basename, array( $this, 'plugin_action_links' ) );
        add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);
    }

    public function plugin_action_links($actions){
        $actions['settings'] = '<a href="admin.php?page=mvp_setting">' . __('Settings') . '</a>';
        return $actions;
    }

    public function plugin_row_meta($plugin_meta, $plugin_file){

        if ($plugin_file === $this->plugin_basename) {
            $plugin_meta[] = sprintf( '<a href="%s">%s</a>',
                esc_url( 'https://www.zwtt8.com/docs-category/mine-video-player/?utm_source=mine_video_player&utm_medium=plugins_installation_list&utm_campaign=plugin_docs_link' ),
                __( '<strong style="color: #03bd24">Documentation</strong>', 'mine-video' )
            );
        }

        return $plugin_meta;
    }
}
