<?php
if (! defined('MX_TITLE_CONTROL_KEY'))
{
	define('MX_TITLE_CONTROL_NAME', 'MX Title Control');
	define('MX_TITLE_CONTROL_VER',  '2.9.1');
	define('MX_TITLE_CONTROL_KEY', 'Mx_title_control');
	define('MX_TITLE_CONTROL_AUTHOR',  'Max Lazar');
	define('MX_TITLE_CONTROL_DOCS',  'http://www.eec.ms/add-on/mx-title-control');
	define('MX_TITLE_CONTROL_DESC',  'MX *Title Control allows you to change the *Title & URL title field label for each of channel and (optional) for each of languages and also change default field length for Title and URL_Title fields. You can also setup Title & URL title auto generation based on patterns');

}

/**
 * < EE 2.6.0 backward compat
 */
 
if ( ! function_exists('ee'))
{
    function ee()
    {
        static $EE;
        if ( ! $EE) $EE = get_instance();
        return $EE;
    }
}
