/**
 * The VA WSD the phantom thief Ajax
 *
 * @package WordPress
 * @subpackage VA WSD the phantom thief
 * @author KUCKLU <kuck1u@visualive.jp>
 * @copyright Copyright (c) 2015 KUCKLU, VisuAlive.
 * @license http://opensource.org/licenses/gpl-2.0.php GPLv2
 * @link http://visualive.jp/
 */

/**
 * Ready
 */
jQuery(function($){
	"use strict";

	$(document).ready(function(){
		$([id^="va-wsd-the-phantom-thief-"]).VisuAliveWebSiteDataThePhantomThief();
	});
});


(function ( $, window, document, undefined ) {
	"use strict";

	var pluginName = "VisuAliveWebSiteDataThePhantomThief",
		defaults   = {
			childrenClass: ".va-wsd-the-phantom-thief"
		};

	function Plugin ( element, options ) {
		this.element   = element;
		this.settings  = $.extend( {}, defaults, options );
		this._defaults = defaults;
		this._name     = pluginName;
		this.init();
	}

	$.extend(Plugin.prototype, {
		init: function () {
			var $target   = $(this.element).find(this.settings.childrenClass),
				urlRegExp = this.urlRegExp,
				ajaxGet   = this.ajaxGet;

			$target.each(function(){
				var $this      = $(this),
					$targetURL = $this.data("url"),
					html       = [];

				if(true === urlRegExp($targetURL)) {
					ajaxGet($targetURL).done(function(result){
						if(true === result.success) {
							if(true === result.data.anchor_target) {
								html.push('<a class="vawsdptp_anchor" href="' + result.data.post_url + '" rel="nofollow" target="_blank">');
							} else {
								html.push('<a class="vawsdptp_anchor" href="' + result.data.post_url + '">');
							}
							if ( "" !== result.data.post_image) {
								html.push('<div class="vawsdptp_image"><img src="' + result.data.post_image + '"></div>');
							}
							html.push('<div class="vawsdptp_body">');
							html.push('<div class="vawsdptp_title">'+result.data.post_title+'</div>');
							if ( "" !== result.data.post_content) {
								html.push('<div class="vawsdptp_content">' + result.data.post_content + '</div>');
							}
							html.push('<div class="vawsdptp_domain">');
							if ( "" !== result.data.post_favicon) {
								html.push('<img class="vawsdptp_favicon" src="' + result.data.post_favicon + '">');
							}
							html.push('<span class="vawsdptp_domain_txt">' + result.data.post_domain + '</span>');
							html.push('</div>');
							html.push('</div>');
							html.push('</a>');

							$this[0].removeAttribute("data-url");
							$this[0].removeChild($this[0].childNodes.item(0));
							$this[0].insertAdjacentHTML('afterbegin', html.join(''));
						} else {
							$this[0].removeAttribute("data-url");
							console.table(result.data);
						}
					});
				}
			});
		},
		urlRegExp: function (str) {
			var re = new RegExp("https?:\/\/[a-zA-Z0-9\-_\.:@!~*'\(¥);/?&=\+$,%#]+", 'g');

			if (str.match(re)) {
				return true;
			} else {
				return false;
			}
		},
		ajaxGet: function (targetURL) {
			var defer = $.Deferred();
			$.ajax({
				type: 'POST',
				url: VAWSDTPT.endpoint,
				data: {
					'action': VAWSDTPT.action,
					'url': targetURL,
					'vawsdtpt_nonce': VAWSDTPT.nonce
				},
				dataType: 'json',
				success: defer.resolve,
				error: defer.reject
			});
			return defer.promise();
		}
	});

	$.fn[ pluginName ] = function ( options ) {
		return this.each(function() {
			if ( !$.data( this, "plugin_" + pluginName ) ) {
				$.data( this, "plugin_" + pluginName, new Plugin( this, options ) );
			}
		});
	};
})( jQuery, window, document );
