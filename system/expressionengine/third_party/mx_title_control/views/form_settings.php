<?php if($message) : ?>
<div class="mor alert success">
<p><?php print($message); ?></p>
</div>
<?php endif; ?>

<?php if($settings_form) : ?>
<?php echo form_open(
		'C=addons_extensions&M=extension_settings&file=&file=mx_title_control',
		'',
		array("file" => "mx_title_control")
	)
?>


<table class="mainTable padTable" id="event_table" border="0" cellpadding="0" cellspacing="0">

<tbody>
<tr class="header">
<th  colspan="3">
</th>
</tr>

<tr>
<td class="default" colspan="3">
<div class="box" style="border-width: 0pt 0pt 1px; margin: 0pt; padding: 10px 5px;"><p><?php echo lang('extension_settings_info')?></p></div>
</td>
</tr>
</tbody> <?php endif; ?>
<tbody>


		<?php
$out="";

foreach ($language_packs as $language)
{
	$i = 1;
	$out .= '<tr class="header"><th>'. $language.'</th><th>'.lang('title').'</th><th>'.lang('url_title').'</th></tr>';
	foreach ($channel_data as $channel)
	{
		$out .= '<tr class="'.(($i&1) ? "odd" : "even").'">
						<td><strong>'.$channel->channel_title.'</strong></td>
						<td><input dir="ltr" style="width: 100%;" name="'.$input_prefix.'[title_'.strtolower($language).'_'.$channel->channel_id.']" id="" value="'.htmlentities(((isset($settings['title_'.strtolower($language).'_'.$channel->channel_id])) ? $settings['title_'.strtolower($language).'_'.$channel->channel_id] : '')).'" size="20" class="input" type="text"></td>
						<td><input dir="ltr" style="width: 100%;" name="'.$input_prefix.'[url_title_'.strtolower($language).'_'.$channel->channel_id.']" id="" value="'.((isset($settings['url_title_'.strtolower($language).'_'.$channel->channel_id])) ? $settings['url_title_'.strtolower($language).'_'.$channel->channel_id] : '').'" size="20"   class="input" type="text"></td>
						</tr>';
		$i++;
	}

}

echo $out;



?>

</tbody>

<tbody>
<tr class="header" >
<th colspan="3"><?php echo lang('multilanguage_settings_info')?></th>

</tr>
<tr>

<td><?php echo lang('multilanguage')?></td>
 <td colspan="2">
<select name="<?php echo $input_prefix ?>[multilanguage]" id='multilanguage' >
<option value="y" <?php echo (isset($settings['multilanguage'])) ? (($settings['multilanguage'] == 'y') ? " selected='selected'" : "" ) : "" ?>><?php echo lang('enable') ?></option>
<option value="n" <?php echo (isset($settings['multilanguage'])) ? (($settings['multilanguage'] == 'n') ? " selected='selected'" : "") : "" ?>><?php echo lang('disable') ?></option>
</select>



</td></tr>
</tbody>
<tbody>
<tr class="header" >
<th colspan="3"><?php echo lang('titles_length_control')?></th>

</tr>
<tr>
<td><?php echo lang('title_max_length')?></td>
 <td colspan="2">
	<input dir="ltr" style="width: 100%;" name="<?php echo $input_prefix?>[max_title]" id="" value="<?php echo ((isset($settings['max_title'])) ? $settings['max_title'] : '100')?>" size="20" maxlength="120" class="input" type="text">
 </td>
 </tr>
 <tr>
 <!--
<td><?php echo lang('url_title_max_length')?></td>
 <td colspan="2">	<input dir="ltr" style="width: 100%;" name="<?php echo $input_prefix?>[max_url_title]" id="" value="<?php echo ((isset($settings['max_url_title'])) ? $settings['max_url_title'] : '75')?>" size="20" maxlength="120" class="input" type="text"></td>
 </tr> -->
</tbody>

<?php
	$out = '';
	$i = 1;
	$out .= '<tr class="header"><th>'.lang('channel_name').'</th><th>'.lang('title_pattern').'</th><th>'.lang('url_title_pattern').'</th></tr>';
	foreach ($channel_data as $channel)
	{
		$out .= '<tr class="'.(($i&1) ? "odd" : "even").'">
						<td><strong>'.$channel->channel_title.'</strong><div style="padding-top:10px;">'.
					form_checkbox($input_prefix.'[title_update_'.$channel->channel_id.']', 'yes', ((isset($settings['title_update_'.$channel->channel_id])) ? $settings['title_update_'.$channel->channel_id] : '' ), 'id="title_update_'.$channel->channel_id.'"').' <label for="title_update_'.$channel->channel_id.'">'.lang('update_title').'</label><br/>'.

form_checkbox($input_prefix.'[url_title_update_'.$channel->channel_id.']', 'yes', ((isset($settings['url_title_update_'.$channel->channel_id])) ? $settings['url_title_update_'.$channel->channel_id] : '' ), 'id="url_title_update_'.$channel->channel_id.'"').' <label for="url_title_update_'.$channel->channel_id.'">'.lang('update_url_title').'</label>	
						</div>
						
						</td>
						<td><textarea rows="5" style="width: 100%;" name="'.$input_prefix.'[title_pattern_'.$channel->channel_id.']" id="">'.htmlentities(((isset($settings['title_pattern_'.$channel->channel_id])) ? $settings['title_pattern_'.$channel->channel_id] : '')).'</textarea>
						</td>
						<td><textarea rows="5" style="width: 100%;" name="'.$input_prefix.'[url_title_pattern_'.$channel->channel_id.']" id="">'.htmlentities(((isset($settings['url_title_pattern_'.$channel->channel_id])) ? $settings['url_title_pattern_'.$channel->channel_id] : '')).'</textarea>
						'.form_checkbox($input_prefix.'[url_title_m_'.$channel->channel_id.']', 'yes', ((isset($settings['url_title_m_'.$channel->channel_id])) ? $settings['url_title_m_'.$channel->channel_id] : '' ), 'id="url_title_m_'.$channel->channel_id.'"').' <label for="url_title_m_'.$channel->channel_id.'">'.lang('can_update').'</label>
						
						</td>
			

						</tr>';
		$i++;
	}

	echo $out;
	?>
</table>
<p class="centerSubmit"><input name="edit_field_group_name" value="<?php echo lang('save_extension_settings'); ?>" class="submit" type="submit"></p>





<?php echo form_close(); ?>

<?php



/*


function form_encode($string)
{
	return str_replace("& amp ;", "&", (htmlentities(stripslashes($string), ENT_QUOTES)));
}
*/
?>
