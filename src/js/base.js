(function ($) {
	let post_this = document.querySelectorAll( 'input[name=_wpt_post_this]' );
	let wrapper   = document.querySelector( '.wpt-options-metabox' );

	post_this.forEach( (el) => { 
		if ( el && el.checked && el.value === 'no' ) {
			wrapper.style.display = 'none';
		}
		el.addEventListener( 'change', function() {
			console.log( el.value );
			if ( el.checked && el.value == 'yes' ) {
				wrapper.style.display = 'block';
			} else {
				wrapper.style.display = 'none';
			}
		});
	});

	let add_image = document.querySelectorAll( 'input[name=_wpt_image]' );
	let image_holder = document.querySelector( '.wpt_custom_image' );

	add_image.forEach( (el) => { 
		if ( el && el.checked && el.value === '1' ) {
			image_holder.style.display = 'none';
		}
		el.addEventListener( 'change', function() {
			if ( el.checked && el.value == '0' ) {
				image_holder.style.display = 'block';
			} else {
				image_holder.style.display = 'none';
			}
		});
	});
	$('#wpt_custom_tweet, #wpt_retweet_0, #wpt_retweet_1, #wpt_retweet_3').charCount({
		allowed: wptSettings.allowed,
		counterText: wptSettings.text
	});
	// add custom retweets
	$('.wp-to-twitter .expandable').hide();
	$('.wp-to-twitter .tweet-toggle').on('click', function (e) {
		let dashicon = $( '.wp-to-twitter .tweet-toggle span ');
		if ( $( '.wp-to-twitter .expandable' ).is( ':visible' ) ) {
			dashicon.addClass( 'dashicons-plus' );
			dashicon.removeClass( 'dashicons-minus' );
			dashicon.parent('button').attr( 'aria-expanded', 'false' );
		} else {
			dashicon.removeClass( 'dashicons-plus' );
			dashicon.addClass( 'dashicons-minus' );
			dashicon.parent('button').attr( 'aria-expanded', 'true' );
		}
		$('.wp-to-twitter .expandable').toggle('slow');
	});
	// tweet history log
	$('.wp-to-twitter .history').hide();
	$('.wp-to-twitter .history-toggle').on('click', function (e) {
		let dashicon = $( '.wp-to-twitter .history-toggle span ');
		if ( $( '.wp-to-twitter .history' ).is( ':visible' ) ) {
			dashicon.addClass( 'dashicons-plus' );
			dashicon.removeClass( 'dashicons-minus' );
			dashicon.parent( 'button' ).attr( 'aria-expanded', 'false' );
		} else {
			dashicon.removeClass( 'dashicons-plus' );
			dashicon.addClass( 'dashicons-minus' );
			dashicon.parent( 'button' ).attr( 'aria-expanded', 'true' );
		}
		$('.wp-to-twitter .history').toggle( 300 );
	});

	const templateTags = document.querySelectorAll( '#wp2t .inline-list button' );
	let   custom       = document.getElementById( 'wpt_custom_tweet' );
	let   template     = document.querySelector( '#wp2t .wpt-template code' );
	let   customText   = ( null !== custom ) ? custom.value : '';
	let   templateText = ( null !== template ) ? template.innerText : '';
	templateTags.forEach((el) => {
		el.addEventListener( 'click', function(e) {
			customText   = ( null !== custom ) ? custom.value : '';
			let pressed  = el.getAttribute( 'aria-pressed' );
			let tag      = el.innerText;
			templateText = ( customText ) ? customText : templateText;
			if ( 'true' === pressed ) {
				let newText  = templateText.replace( tag, '' ).trim();
				templateText = newText;
				custom.value = newText;
				el.setAttribute( 'aria-pressed', 'false' );
			} else {
				templateText = templateText + ' ' + tag;
				custom.value = templateText;
				el.setAttribute( 'aria-pressed', 'true' );			
			}
			wp.a11y.speak( wptSettings.updated );
		});
	});
}(jQuery));