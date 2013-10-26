<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'mx_title_control/config.php';

/**
 * MX Title Control
 *
 * MX *Title Control allows you to change the *Title & URL title field label for each of channel and (optional) for each of languages and also change default field length for Title and URL_Title fields. You can also setup Title & URL title auto generation based on patterns.
 *
 * @package  ExpressionEngine
 * @category Extension
 * @author    Max Lazar <max@eec.ms>
 * @copyright Copyright (c) 2008-2012 Max Lazar (http://www.eec.ms)
 * @license   http://creativecommons.org/licenses/MIT/  MIT License
 * @version 2.9
 */



class Mx_title_control_ext
{
	var $settings        = array();

	var $addon_name      = MX_TITLE_CONTROL_NAME;
	var $name            = MX_TITLE_CONTROL_NAME;
	var $version         = MX_TITLE_CONTROL_VER;
	var $description     = MX_TITLE_CONTROL_DESC;
	var $settings_exist  = 'y';
	var $docs_url        = '';

	/**
	 * Defines the ExpressionEngine hooks that this extension will intercept.
	 *
	 * @since Version 1.0.0
	 * @access private
	 * @var mixed an array of strings that name defined hooks
	 * @see http://codeigniter.com/user_guide/general/hooks.html
	 **/

	private $hooks = array('cp_js_end' => 'cp_js_end', 'entry_submission_end' => 'entry_submission_end');

	// -------------------------------
	// Constructor
	// -------------------------------
	function Mx_title_control_ext($settings='')
	{
		$this->EE =& get_instance();
		$this->settings = $settings;
	}

	public function __construct($settings=FALSE)
	{
		$this->EE =& get_instance();

		// define a constant for the current site_id rather than calling $PREFS->ini() all the time
		if
		(defined('SITE_ID') == FALSE)
			define('SITE_ID', $this->EE->config->item('site_id'));

		// set the settings for all other methods to access
		$this->settings = ($settings == FALSE) ? $this->_getSettings() : $this->_saveSettingsToSession($settings);
	}


	/**
	 * Prepares and loads the settings form for display in the ExpressionEngine control panel.
	 * @since Version 1.0.0
	 * @access public
	 * @return void
	 **/
	public function settings_form()
	{
		$this->EE->lang->loadfile('mx_title_control');
		$this->EE->load->model('channel_model');

		// Create the variable array
		$vars = array(
			'addon_name' => $this->addon_name,
			'error' => FALSE,
			'input_prefix' => __CLASS__,
			'message' => FALSE,
			'settings_form' =>FALSE,
			'channel_data' => $this->EE->channel_model->get_channels()->result(),
			'language_packs' => ''
		);



		$vars['settings'] = $this->settings;
		$vars['settings_form'] = TRUE;

		if
		($new_settings = $this->EE->input->post(__CLASS__))
		{

			foreach ($vars['channel_data'] as $channel)
			{

				if
				(isset($new_settings['url_title_update_' . $channel->channel_id]) or isset($new_settings['title_update_' . $channel->channel_id]))
				{

					$title_name = (isset($new_settings['title_pattern_'.$channel->channel_id])) ? $new_settings['title_pattern_'.$channel->channel_id] : FALSE;

					$url_title_name = (isset($new_settings['url_title_pattern_'.$channel->channel_id])) ? $new_settings['url_title_pattern_'.$channel->channel_id] : FALSE;

					if (trim($title_name) != '' or trim($url_title_name))
					{
						$meta = array ();
						$query = $this->EE->db->select('entry_id, title')
						->where('channel_id', $channel->channel_id)
						->get('channel_titles');

						if ($query->num_rows() > 0)
						{
							foreach ($query->result_array() as $row)
							{
								if ($title_name && isset($new_settings['title_update_' . $channel->channel_id]))
								{
									$title_name_out = $this->pattern2name($title_name, $row['entry_id']);
									$meta['title'] = $title_name_out;
								}

								if ($url_title_name && isset($new_settings['url_title_update_' . $channel->channel_id]))
								{
									$url_title_name_out = ($url_title_name == $title_name) ? $title_name_out : $this->pattern2name($url_title_name, $row['entry_id']);

									$url_title_name_out = $this->convert_accented_characters(strtolower($url_title_name_out));

									if
									(!isset($this->EE->api_channel_entries)):
										$this->EE->load->library('api');
									$this->EE->api->instantiate('channel_entries');
									endif;

									$this->EE->api_channel_entries->entry_id = $row['entry_id'];
									$this->EE->api_channel_entries->channel_id = $channel->channel_id;
									$meta['url_title'] = $this->EE->api_channel_entries->_validate_url_title($url_title_name_out, $row['title'], true);
									// echo $meta['url_title']."-<<br/>".$row['title'].'->'.$url_title_name_out.$row['entry_id'];

								}


								$this->EE->db->where('entry_id', $row['entry_id']);
								$this->EE->db->update('channel_titles', $meta);


							}
						}
					}

					$new_settings['url_title_update_' . $channel->channel_id] = false;
					$new_settings['title_update_' . $channel->channel_id] = false;
				}
			}

			$vars['settings'] = $new_settings;
			$this->_saveSettingsToDB($new_settings);
			$vars['message'] = $this->EE->lang->line('extension_settings_saved_success');

			$this->EE->load->dbforge();

			$vars['settings']['max_title'] = ((int)$vars['settings']['max_title'] > 1000 ) ? 1000 :  (int)$vars['settings']['max_title'];
			// $vars['settings']['max_url_title'] = ((int)$vars['settings']['max_url_title'] > 1000) ? 1000 :  (int)$vars['settings']['max_url_title'];

			$fields = array(
				'title' => array(
					'name' => 'title',
					'type' => 'VARCHAR('.(($vars['settings']['max_title'] < 100) ? 100 : $vars['settings']['max_title']).')',

				)
				/*	,
				'url_title' => array(
					'name' => 'url_title',
					'type' => 'VARCHAR('.(($vars['settings']['max_url_title'] < 75) ? 75 : $vars['settings']['max_url_title']).')',
				), */
			);

			$this->EE->dbforge->modify_column('channel_titles', $fields);


		}

		if  ($vars['settings']['multilanguage'] != 'y')
		{
			$vars['language_packs'][] = 'default';
		}
		else
		{
			$vars['language_packs'] =  $this->language_packs() ;
		}

		$js = str_replace('"', '\"', str_replace("\n", "", $this->EE->load->view('form_settings', $vars, TRUE)));

		return $this->EE->load->view('form_settings', $vars, true);

	}
	// END
	function entry_submission_end($entry_id, $meta, $data)
	{

		$channel_id = $meta['channel_id'];

		if (isset($meta['channel_id']))
		{

			$settings =  $this->_getSettings();

			if (isset($settings['title_pattern_'.$channel_id]) or isset($settings['url_title_pattern_'.$channel_id]))
			{

				$title_name = (isset($settings['title_pattern_'.$channel_id])) ? $settings['title_pattern_'.$channel_id] : FALSE;
				$url_title_name = (isset($settings['url_title_pattern_'.$channel_id])) ? $settings['url_title_pattern_'.$channel_id] : FALSE;
				$url_title_name_m = (isset($settings['url_title_m_'.$channel_id])) ? TRUE : FALSE;

				if ($title_name)
				{
					$title_name_out = $this->pattern2name($title_name, $entry_id);
					$meta['title'] = $title_name_out;
				}

				if ($url_title_name and ($data['entry_id'] == 0 or ($data['entry_id'] != 0 and $url_title_name_m) ))
				{
					$url_title_name_out = ($url_title_name == $title_name) ? $title_name_out : $this->pattern2name($url_title_name, $entry_id);
					$url_title_name_out = $this->convert_accented_characters(strtolower($url_title_name_out));

					if
					(!isset($this->EE->api_channel_entries)):
						$this->EE->load->library('api');
					$this->EE->api->instantiate('channel_entries');
					endif;


					$this->EE->api_channel_entries->entry_id = $entry_id;
					$meta['url_title'] = $this->EE->api_channel_entries->_validate_url_title($url_title_name_out, $meta['title'], true);
				}


				$this->EE->db->where('entry_id', $entry_id);
				$this->EE->db->update('channel_titles', $meta);
			}

		}
	}

	public static function to_unicode($s)
	{
		if (is_null($s)) return $s;

		$s2 = null;
		if (function_exists('iconv')) $s2 = @iconv('UTF-8', 'UCS-4BE', $s);
		elseif (function_exists('mb_convert_encoding')) $s2 = @mb_convert_encoding($s, 'UCS-4BE', 'UTF-8');
		if (is_string($s2)) return array_values(unpack('N*', $s2));
		if ($s2 !== null) return false;

		$a = self::str_split($s);
		if ($a === false) return false;
		return array_map(array(__CLASS__, 'ord'), $a);
	}

	function pattern2name($pattern, $entry_id)
	{
		$name = '{exp:channel:entries entry_id="'.$entry_id.'" status="not TYTTOTYESIRACKO" show_future_entries="yes" show_expired="yes"}'.$pattern.'{/exp:channel:entries}';
		$this->EE->load->library('typography');
		$this->EE->load->library('template');
		$this->EE->TMPL = new EE_Template;

		$this->EE->typography->initialize();
		$this->EE->typography->convert_curly = FALSE;
		$this->EE->typography->allow_img_url = FALSE;
		$this->EE->typography->auto_links    = FALSE;
		$this->EE->typography->encode_email  = FALSE;
		$this->EE->TMPL->parse($name, FALSE, SITE_ID);

		return trim(strip_tags(str_replace(array("\r", "\r\n", "\n\r", "\n"), "", $this->EE->TMPL->parse_globals($this->EE->TMPL->final_template))));
	}


	function convert_accented_characters($string)
	{
		include APPPATH.'config/foreign_chars.php';

		$string = $this->to_unicode($string);
		$out='';

		if (isset($this->extensions->extensions['foreign_character_conversion_array']))
		{
			$foreign_characters = $this->EE->extensions->call('foreign_character_conversion_array');
		}

		$i = 0;

		foreach ($string as $key => $code)
		{
			if (isset($foreign_characters[$code]))
			{
				$out{$i} = $foreign_characters[$code];
			}
			else
			{
				$out{$i} = chr($code);
			}
			$i=$i+1;
		}

		$out = implode("", $out);

		return $out;
	}

	function cp_js_end()
	{

		$out = '';

		if ($this->EE->extensions->last_call !== FALSE)
		{
			$out = $this->EE->extensions->last_call;
		}


		$this->EE->load->helper('array');

		parse_str(parse_url(@$_SERVER['HTTP_REFERER'], PHP_URL_QUERY), $get);

		if (element('D', $get) == 'cp' && element('C', $get) == 'content_publish' && element('M', $get) == 'entry_form' && element('channel_id', $get))
		{

			$settings =  $this->_getSettings();

			$channel_id = $this->EE->security->xss_clean(element('channel_id', $get));
	
			if  ($channel_id != '')
			{

				if  ($channel_id !== FALSE)
				{
					$channel_id =  $channel_id;
				}

				$this->EE->load->helper('string'); 
	
				$lang  = ((string) $this->settings['multilanguage']  == 'y') ?  $this->EE->session->userdata('language') : 'default';

				$title = (isset($this->settings['title_'.$lang.'_'.$channel_id])) ? $this->settings['title_'.$lang.'_'.$channel_id]  : '' ;
				$url_title = (isset($this->settings['url_title_'.$lang.'_'.$channel_id])) ? $this->settings['url_title_'.$lang.'_'.$channel_id]  : '' ;
				$max_title = (isset($this->settings['max_title'])) ? $this->settings['max_title']  : 100 ;
				$max_url_title = (isset($this->settings['max_url_title'])) ? $this->settings['max_url_title']  : 75 ;

				$field_exp = reduce_double_slashes($this->EE->config->item('theme_folder_url').'/cp_themes/default/images/field_expand.png');

				if ($url_title  !='')
				{
					$out .= '$("#sub_hold_field_url_title").prev("label").html(\'<span><img class="field_collapse" src="'.$field_exp.'" alt=""> '.$url_title.'</span>\');';
				}

				if  ($title  !='')
				{
					$out .= '$("#sub_hold_field_title").prev("label").html(\'<span><img class="field_collapse" src="'.$field_exp.'" alt=""> '.$title.'</span>\');';
				}

	
				if  (isset($this->settings['title_pattern_'.$channel_id]))
				{
					if (trim($this->settings['title_pattern_'.$channel_id])  !='')
					{
						$out .= 'if ($("#title").val() == "") {$("#title").val("auto_replace");}
						$("#hold_field_title").hide();';
					}
				}

				if  (isset($this->settings['url_title_pattern_'.$channel_id]))
				{
					if (trim($this->settings['url_title_pattern_'.$channel_id])  !='')
					{
						$out .= '$("#hold_field_url_title").hide();';
					}
				}
				//$this->EE->extensions->call('safecracker_submit_entry_start', $this);

				if ($max_title  != 100)
				{
					$out .= '$("#title").attr("maxlength", "'.$max_title.'");';
				}

				if ($max_url_title  != 75)
				{
					//     $out .= '$("#url_title").attr("maxlength", "'.$max_url_title.'"); ';
				}


			}
		}

		return $out;
	}

	function language_packs()
	{
		static $languages;

		if ( ! isset($languages))
		{
			$this->EE->load->helper('directory');

			$source_dir = APPPATH.'language/';

			if (($list = directory_map($source_dir, TRUE)) !== FALSE)
			{
				foreach ($list as $file)
				{
					if (is_dir($source_dir.$file) && $file[0] != '.')
					{
						$languages[$file] = ucfirst($file);
					}
				}

				ksort($languages);
			}
		}

		return $languages;
	}

	// --------------------------------
	//  Activate Extension
	// --------------------------------

	function activate_extension()
	{
		$this->_createHooks();
	}

	/**
	 * Saves the specified settings array to the database.
	 *
	 * @since Version 1.0.0
	 * @access protected
	 * @param array $settings an array of settings to save to the database.
	 * @return void
	 **/
	private function _getSettings($refresh = FALSE)
	{
		$settings = FALSE;
		if
		(isset($this->EE->session->cache[$this->addon_name][__CLASS__]['settings']) === FALSE || $refresh === TRUE)
		{
			$settings_query = $this->EE->db->select('settings')
			->where('enabled', 'y')
			->where('class', __CLASS__)
			->get('extensions', 1);

			if
			($settings_query->num_rows())
			{
				$settings = unserialize($settings_query->row()->settings);
				$this->_saveSettingsToSession($settings);
			}
		}
		else
		{
			$settings = $this->EE->session->cache[$this->addon_name][__CLASS__]['settings'];
		}
		return $settings;
	}

	/**
	 * Saves the specified settings array to the session.
	 * @since Version 1.0.0
	 * @access protected
	 * @param array $settings an array of settings to save to the session.
	 * @param array $sess A session object
	 * @return array the provided settings array
	 **/
	private function _saveSettingsToSession($settings, &$sess = FALSE)
	{
		// if there is no $sess passed and EE's session is not instaniated
		if
		($sess == FALSE && isset($this->EE->session->cache) == FALSE)
			return $settings;

		// if there is an EE session available and there is no custom session object
		if
		($sess == FALSE && isset($this->EE->session) == TRUE)
			$sess =& $this->EE->session;

		// Set the settings in the cache
		$sess->cache[$this->addon_name][__CLASS__]['settings'] = $settings;

		// return the settings
		return $settings;
	}


	/**
	 * Saves the specified settings array to the database.
	 *
	 * @since Version 1.0.0
	 * @access protected
	 * @param array $settings an array of settings to save to the database.
	 * @return void
	 **/
	private function _saveSettingsToDB($settings)
	{
		$this->EE->db->where('class', __CLASS__)
		->update('extensions', array('settings' => serialize($settings)));
	}
	/**
	 * Sets up and subscribes to the hooks specified by the $hooks array.
	 * @since Version 1.0.0
	 * @access private
	 * @param array $hooks a flat array containing the names of any hooks that this extension subscribes to. By default, this parameter is set to FALSE.
	 * @return void
	 * @see http://codeigniter.com/user_guide/general/hooks.html
	 **/
	private function _createHooks($hooks = FALSE)
	{
		if (!$hooks)
		{
			$hooks = $this->hooks;
		}

		$hook_template = array(
			'class' => __CLASS__,
			'settings' =>'',
			'priority' => '1',
			'version' => $this->version,
		);

		$hook_template['settings']['multilanguage'] = 'n';

		foreach ($hooks as $key => $hook)
		{
			if (is_array($hook))
			{
				$data['hook'] = $key;
				$data['method'] = (isset($hook['method']) === TRUE) ? $hook['method'] : $key;

				$data = array_merge($data, $hook);
			}
			else
			{
				$data['hook'] = $data['method'] = $hook;
			}

			$hook = array_merge($hook_template, $data);
			$hook['settings'] = serialize($hook['settings']);
			$this->EE->db->query($this->EE->db->insert_string('exp_extensions', $hook));
		}
	}

	/**
	 * Removes all subscribed hooks for the current extension.
	 *
	 * @since Version 1.0.0
	 * @access private
	 * @return void
	 * @see http://codeigniter.com/user_guide/general/hooks.html
	 **/
	private function _deleteHooks()
	{
		$this->EE->db->query("DELETE FROM `exp_extensions` WHERE `class` = '".__CLASS__."'");
	}


	// END




	// --------------------------------
	//  Update Extension
	// --------------------------------

	function update_extension( $current='' )
	{


		if ($current == '' or $current == $this->version)
		{
			return FALSE;
		}

		if ($current < '2.3')
		{

			$this->disable_acc();
			$this->EE->db->query("UPDATE exp_extensions SET method = 'cp_js_end', hook = 'cp_js_end' WHERE class = '".get_class($this)."'");

			// Update to next version
		}

		$this->EE->db->query("UPDATE exp_extensions SET version = '".$this->EE->db->escape_str($this->version)."' WHERE class = '".get_class($this)."'");
	}
	// END

	// --------------------------------
	//  Disable Acc
	// --------------------------------
	function disable_acc()
	{
		$accessory = 'Mx_title_control_acc';

		$this->EE->db->delete('accessories', array('class' => $accessory));
	}



	// --------------------------------
	//  Disable Extension
	// --------------------------------

	function disable_extension()
	{
		$this->EE->db->delete('exp_extensions', array('class' => get_class($this)));
	}
	// END
}

/* End of file ext.mx_title_control.php */
/* Location: ./system/expressionengine/third_party/mx_title_control/ext.mx_title_control.php */
