/**
 * The VA WSD the phantom thief Admin
 *
 * @package WordPress
 * @subpackage VA WSD the phantom thief
 * @author KUCKLU <kuck1u@visualive.jp>
 * @copyright Copyright (c) 2015 KUCKLU, VisuAlive.
 * @license http://opensource.org/licenses/gpl-2.0.php GPLv2
 * @link http://visualive.jp/
 */
jQuery(function($){
	"use strict";

	var $iedit = $('.iedit').find('.post-title'),
		$miscPublishingActions = $('#misc-publishing-actions');

	$('.add-new-h2').remove();
	$('#bulk-action-selector-top').find('.hide-if-no-js').remove();
	$('#bulk-action-selector-bottom').find('.hide-if-no-js').remove();
	$iedit.find('.inline').remove();
	$iedit.find('.clone').remove();
	$iedit.find('.edit_as_new_draft').remove();
	$miscPublishingActions.find('.hide-if-no-js').remove();
	$miscPublishingActions.find('.hide-if-js').remove();
	$('#message.updated').find('a').remove();
});