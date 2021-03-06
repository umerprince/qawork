function qw_animate_button(elm, hide){
	//set the default value 
	if (typeof(hide)==='undefined') {hide = false};

	if(hide)
		$(elm).removeClass('btn-loading');
	else
		$(elm).addClass('btn-loading');
}
function qw_remove_animate_button(elm){
	$(elm).removeClass('btn-loading active');
}

function qw_question_meta(){
	$('#set_featured').click(function(e){
		e.preventDefault();
		qw_animate_button(this);
		$.ajax({
			data: {
				qw_ajax: true,
				qw_ajax_html: true,
				action: 'set_question_featured',
			},
			dataType: 'html',
			context:this,
			success: function (response) {				
				qw_animate_button(this, true);
				location.reload();
			},
		});		
	});
	


}

function qw_tab(){

	jQuery('.ra-option-tabs li a').click(function(e){
		e.preventDefault();
		jQuery('.ra-option-tabs li').removeClass('active');
		jQuery(this).parent().addClass('active');
		var t = jQuery(this).data('toggle');
		jQuery('.option-tab-content >div').removeClass('active');
		jQuery(t).addClass('active');
		
	});
}

function qw_set_active_sub_nav(elem){
	$(elem).closest('.qa-nav-sub-list').find('li a').removeClass('qa-nav-sub-selected');
	$(elem).addClass('qa-nav-sub-selected');
}

function qw_ajax_sub_menu(elem){
	$(elem).click(function(e){
		e.preventDefault();
		qw_set_active_sub_nav(this);
		
		var url = $(this).attr('href');
		$.get( url, function( data ) {
			var html = $(data).find('.qa-part-q-list form');
			$('.qa-part-q-list').html(html);
			
		});
	});
}

function qw_vote_click(){
	$('body').delegate('.vote-up, .vote-down', 'click', function(){
		qw_ajax_loading(this);
		if (typeof ($(this).data('id')) != 'undefined'){
			var ens=$(this).data('id').split('_');
			var parent = $(this).parent();
			var postid=ens[1];
			var vote=parseInt(ens[2]);
			var code=$(this).data('code');
			var anchor=ens[3];
			
			qa_ajax_post('vote', {postid:postid, vote:vote, code:code},
				function(lines) {
					if (lines[0]=='1') {
						qa_set_inner_html(document.getElementById('voting_'+postid), 'voting', lines.slice(1).join("\n"));
						$('.voting a').tooltip({placement:'bottom'});
						

					} else if (lines[0]=='0') {						
						qw_alert(lines[1]);					
					} else
						qa_ajax_error();
				}
			);	
		}
		return false;
	});	
}
function qw_ajax_loading($elm){
	var position = $($elm).offset();
	var html = '<div id="ajax-loading"></div>';	
	$(html).appendTo('body').ajaxStart(function () {
		$('#ajax-loading').css(position);
		$(this).show();
	});

	$("#ajax-loading").ajaxStop(function () {
		$(this).remove();
	});
}
function qw_toggle_editor(){	
	$( '#q_doanswer, #focus_doanswer' ).not('.disabled').on('click', function(event) {
		event.preventDefault();
		$('html, body').animate({
			scrollTop: $('#anew').offset().top
		}, 500);
	});
}
function qw_favorite_click()
{
	$('body').delegate( '.fav-btn', 'click', function() {
		qw_ajax_loading(this);
		var ens 	=	$(this).data('id').split('_');
		var code	=	$(this).data('code');
		var elem	=	$(this);
		qa_ajax_post('favorite', {entitytype:ens[1], entityid:ens[2], favorite:parseInt(ens[3]), code:code},
			function (lines) {
				if (lines[0]=='1'){
					
					elem.parent().empty().html(lines.slice(1).join("\n"));
					$('.fav-btn').tooltip({placement:'top'});
				}else if (lines[0]=='0') {
					//alert(lines[1]);
					//qw_remove_process(elem);
				} else
					qa_ajax_error();
			}
		);
		
		//qw_process(elem, false);
		
		return false;
	});
}
function qw_alert($mesasge){
	if($('#ra-alert').length > 0)
		$('#ra-alert').remove();
	var html = '<div id="ra-alert" class="alert fade in"><button aria-hidden="true" data-dismiss="alert" class="close" type="button">&times;</button>'+$mesasge+'</div>';
	$(html).appendTo('body');
	$('#ra-alert').css({left:($(window).width()/2 - $('#ra-alert').width()/2)}).animate({top:'50px'},300);
}
function qw_slide_menu(){
	$('.slide-mobile-menu').toggle(
		function() {
			$('#nav-top .qa-nav-main').animate({'left':0}, 200);
			$('.left-sidebar').animate({'max-width':180}, 200);
			$('.qa-main').animate({'width': $('.qa-main').width(), 'margin-left':190},200);
			$('body').addClass('menu-open');
		}, function() {			
			$('#nav-top .qa-nav-main').animate({'left':'-180'}, 200, function(){$(this).removeAttr('style')});
			$('.left-sidebar, #nav-top .qa-nav-main').animate({'max-width':0}, 200, function(){$(this).removeAttr('style')});
			$('.qa-main').animate({'width': 'auto', 'margin-left':10}, 200, function(){$(this).removeAttr('style'); $('body').removeClass('menu-open');});
		}
	);
}
function qw_float_left(){
	var winwidth 	= $(window).width();
	if(winwidth < 980)
		$('.left-sidebar .float-nav').removeAttr('style');
	else
	$(window).scroll(function(){
		var st = $(this).scrollTop();
		
		if(winwidth > 980){
			$('.left-sidebar').each(function(){
				var $this = $(this), 
					offset = $this.offset(),
					h = $this.height(),
					$float = $this.find('.float-nav'),
					floatH = $float.height(),
					topFloat = 0;
				if(st >= offset.top-topFloat){
					$float.css({'position':'fixed', 'top':topFloat+'px'});
				}else if(st < offset.top + h-topFloat - floatH){
					$float.css({'position':'absolute', 'top':0});
				}else{
					$float.css({'position':'absolute', 'top':0});
				}
			})
		}else{
			$('.left-sidebar .float-nav').removeAttr('style');
		}
	});
}

function qw_widgets(){
	$('.position-toggler').click(function(){
		$('.position-canvas').not($(this).parent().next()).hide();
		$(this).parent().next().toggle(0);
		$(this).toggleClass('icon-angle-up icon-angle-down');
	});	
	$('#ra-widgets').delegate('.widget-delete', 'click', function(){
		
		var id = $(this).closest('.draggable-widget').data('id');
		$.ajax({
			url : ajax_url,
			data: {
				id: id,
				action: 'delete_widget',
			},
			dataType: 'html',
			success: function (response) {
				
			},
		});	
		$(this).closest('.draggable-widget').remove();
	});		
	$('#ra-widgets').delegate('.draggable-widget select, .draggable-widget input, .draggable-widget textarea', 'click', function(){
		var $parent = $(this).closest('.widget-canvas');
		$parent.find('.widget-save').addClass('active');	
	});	
	$('#ra-widgets').delegate('.widget-template-to', 'click', function(){
		var $parent = $(this).closest('.position-canvas');
		$(this).closest('.draggable-widget').find('.select-template').slideToggle(200);
	});	
	$('#ra-widgets').delegate('.widget-options', 'click', function(){
		var $parent = $(this).closest('.position-canvas');
		$(this).closest('.draggable-widget').find('.widget-option').slideToggle(200);
	});	

	
	$('#ra-widgets').delegate('.widget-save.active', 'click', function(){
		var $parent = $(this).closest('.widget-canvas').find('.position-canvas');
		qw_animate_button($(this), false);
		qw_save_widget($parent);
		qw_animate_button($(this), true);
	});
	
	if ($('#ra-widgets').length>0) {
		$('#ra-widgets .widget-list .draggable-widget').draggable({
			connectToSortable: '.position-canvas',
			helper: 'clone',
			handle: '.drag-handle',
			drag: function (e, t) {
				t.helper.width(299);
				t.helper.height(42);
			}
		});

		$('.position-canvas').sortable({
			connectWith: '.column',
			opacity: .35,
			placeholder: 'placeholder',
			handle: '.drag-handle',
			start: function (e, ui) {
				ui.placeholder.height(42);
			},
			stop: function () { 
				$(this).closest('.widget-canvas').find('.widget-save').addClass('active');				
			}
		});
	}
}
function qw_save_widget($elm){
	var widget ={};
	var	locations = {};
	var	options = {};
		
	$elm.find('.draggable-widget').each(function(){		
		var name = $(this).data('name');
		var id = typeof $(this).data('id') == 'undefined' ? 0 : $(this).data('id') ;
		var order = $(this).index();
		var locations = {};
		var options = {};
		widget[order] = {'name' : name, 'id' : id, 'locations':'', 'options':''};
		
		$(this).find('.select-template input').each(function(){
			locations[$(this).attr('name')] = $(this).is(':checked') ? true : false;
		});
		$(this).find('.widget-option input, .widget-option select, .widget-option textarea').each(function(){
			if($(this).is(':checkbox')) 
				var value = $(this).is(':checked') ? 1 : 0;
			else
				var value = $(this).val();
				
			options[$(this).attr('name')] = encodeURIComponent(value);
		});
		
		widget[order]['locations'] = locations;
		widget[order]['options'] = options;
		
	});

	 $.ajax({
		type:'post',
		url : ajax_url,
		data: {
			position: $elm.data('name'),
			widget_names: JSON.stringify(widget),
			action: 'save_widget_position',
		},
		dataType: 'json',
		context: $elm,
		success: function (response) {
			$.each(response, function(index, item) {
				$elm.find('.draggable-widget').eq(index).data('id', item);
			});
			$elm.closest('.widget-canvas').find('.widget-save').removeClass('active');
		},
	});
}

function qw_ask_box_autocomplete(){
	$( "#ra-ask-search" ).autocomplete({
		source: function( request, response ) {
			var term = request.term.replace(' ', '+');
			$.ajax({
				data: {
					qw_ajax: true,
					qw_ajax_html: true,
					start_with: request.term,
					action: 'get_question_suggestion',
				},
				dataType: 'json',
				context: this,
				success: function (data) {					
					response($.map(data, function(obj) {
						return {
							label: obj.title,
							url: obj.url,
							tags: obj.tags,			
							answers: obj.answers,			
							blob: obj.blob			
						};
					}));
				},
			});
			
			if(typeof data =='undefined'){
				var result = [{
					label: '--', 
					url: site_url+'/search?q='+term
				}];
				response(result);
			 }
		},
		open: function( event, ui ) {
			var term = event.target.value.replace(' ', '+');
			$('.ui-autocomplete').append('<li class="see-all"><a href="'+site_url+'/search?q='+term+'" class="icon-search">See All Result</a></li>'); //See all results
		},
		minLength: 3,
		appendTo:".ra-ask-widget",
		messages: {
			noResults: '',
			results: function() {}
		}
	}).data( "uiAutocomplete" )._renderItem = function( ul, item ) {
		if(item.label =='--'){
			return $("<li></li>")
			.data("item.uiAutocomplete", item)
			.append('<a href="'+item.url+'" class="no-results-label"><span class="no-results">No results found!</span>Click for advance search<span class="keep-ask">Or ask a new question by clicking ask button.</span></a>')
			.appendTo(ul);
		}
		if(item.blob!=null)
			var avatar = '<img src="'+item.blob+'" />';
		return $("<li></li>")
		.data("item.uiAutocomplete", item)
		.append('<a href="'+item.url+'" class="">'+avatar+'<span class="title">' + item.label + '<span class="answer">'+item.answers+'</span></a>')
		.appendTo(ul);
	};

    $('#ra-ask-search').off('keyup keydown keypress');
}

function back_to_top(){
	$("#back-to-top").hide();
	$(function () {
		$(window).scroll(function () {
			if ($(this).scrollTop() > 50) {
				$('#back-to-top').fadeIn();
			} else {
				$('#back-to-top').fadeOut();
			}
		});
		$('#back-to-top').click(function () {
			$('body,html').animate({
				scrollTop: 0
			}, 500);
			return false;
		});
	});
}

function qw_load_login_register(){
	$('#login-register').not('active').click(function(){
		$.ajax({
			data: {
				qw_ajax: true,
				qw_ajax_html: true,
				action: 'get_login_register',
			},
			dataType: 'html',
			success: function (response) {
				$('.qa-main > .list-c').html(response);
			},
		});
	});
}
function qw_create_cache(){
	$('body').delegate('#cache_assets', 'click', function(e){
		e.preventDefault();
		if(!$(this).is('.active')){
			$.ajax({
				data: {
					qw_ajax: true,
					qw_ajax_html: true,
					action: 'build_assets_cache',
				},
				context:this,
				dataType: 'html',
				success: function (response) {
					$(this).addClass('active btn-danger');
					$(this).text(response);
				},
			});
		}else{
			$.ajax({
				data: {
					qw_ajax: true,
					qw_ajax_html: true,
					action: 'destroy_assets_cache',
				},
				context:this,
				dataType: 'html',
				success: function (response) {
					$(this).removeClass('active btn-danger');
					$(this).text(response);
				},
			});
		}
	});
}
function qw_save_image(image){
	$.ajax({
		data: {
			qw_ajax: true,
			qw_ajax_html: true,
			featured_image: image,
			action: 'save_q_meta',
		},
		dataType: 'html',
		success: function (response) {
			//$('.question-image-container').append('<img src="'+response+'" />');
		},
	});	
}

function qw_user_popover(){	
	$('body').on('mouseenter', '.avatar[data-handle]', function( event ) {
		
		if($('.user-popover').is(':visible'))
			$('.user-popover').hide();

		var handle = $(this).data('handle');
		var userid = $(this).data('id');
		var offset = $(this).offset();
		var $this = $(this);
		
		popover_time = setTimeout(function(){
		if($('body').find('#'+userid+'_popover').length == 0 && (handle.length > 0)){
		$this.addClass('mouseover');
			$.ajax({
				type: 'POST',
				data: {
					qw_ajax: true,
					action: 'user_popover',
					handle: handle,
				},
				dataType: 'html',
				context: $this,
				success: function (response) {
					$('body').append(response);
					$('#'+userid+'_popover').position({my: 'center bottom',at: 'center top', of:$this, collision: 'fit flip'});
					$('#'+userid+'_popover').show();
					$this.delay(500).queue(function() {$this.removeClass('mouseover'); $this.dequeue();});
				},
			});
		}else{
			//if($('.user-popover').is(':visible'))
				//$('.user-popover').hide();
			//$(this).addClass('mouseover');	
			$('#'+userid+'_popover').removeAttr('style');
			$('#'+userid+'_popover').position({my: 'center bottom',at: 'center top', of:$this, collision: 'fit flip'});
			$('#'+userid+'_popover').show();
		}
		},400);
	}).on('mouseleave', '.avatar[data-handle]', function( event ) {
		clearTimeout(popover_time);
		var userid = $(this).data('id');
		$('#'+userid+'_popover').hide();
		$(this).removeClass('mouseover');
	});
}

function qw_select_answer(answerid, questionid, target, code, name){

	var params={};
	
	params.answerid=answerid;
	params.questionid=questionid;
	params.code=code;
	params[name]=' ';
	
	
	qa_ajax_post('click_a', params,
		function (lines) {
			if (lines[0]=='1') {
				qa_set_inner_html(document.getElementById('a_list_title'), 'a_list_title', lines[1]);

				var l=document.getElementById('a'+answerid);
				var h=lines.slice(2).join("\n");
				
				if (h.length)
					qa_set_outer_html(l, 'answer', h);
				else
					qa_conceal(l, 'answer');
			
			} else {
				/* target.form.elements.qa_click.value=target.name;
				target.form.submit(); */
			}
		}
	);
	
	qa_show_waiting_after(target, false);
	
	return false;
								
}
function qw_toggle_comment(){
	$('body').delegate('.toggle-comment', 'click', function(){	
		var c = $(this).parent().find('.qa-c-wrap');
		if($(this).is('.open')){
			$(this).removeClass('open');
			c.animate({'height': '22px'}, 200);
			c.removeClass('open');
		}else{
			$(this).addClass('open');
			c.animate({'height': c.find('.qa-c-wrap-inner-height').height()+20}, 200);
			c.addClass('open');
		}
	});
}
function qw_check_site_status_size(){
	if($('.site-status-inner .bar-float').width() < 160)
		$('.site-status-inner > *').css({'float': 'none', 'width':'100%'});
}

function qw_change_cover(elm){
	qw_animate_button(elm);
	$.ajax({
		url: ajax_url,
		type: 'POST',
		data: {
			action: 'upload_cover'
		},
		dataType: 'html',
		context: elm,
		success: function (response) {
			$(response).appendTo('body');
			$('#upload_cover_modal').modal('show');
			qw_animate_button(elm, true);
		},
	});
}

jQuery.fn.redraw = function() {
    return this.hide(0, function(){jQuery(this).show()});
}; 

$(document).ready(function(){

	var win_height = $(window).height();
	var main_height = $('#site-body').height() +60;
	
	if( main_height < win_height)
		$('#site-body').css('height', win_height -50);
	
	qw_float_left();	
	qw_slide_menu();
	qw_vote_click();
	qw_toggle_editor();
	qw_favorite_click();
	qw_tab();
	qw_widgets();
	back_to_top();
	qw_question_meta();
	qw_load_login_register();
	qw_user_popover();
	qw_check_site_status_size();
	qw_create_cache();
	qw_toggle_comment();
	
	if ($('.ra-ask-widget').length>0)
		qw_ask_box_autocomplete();
	
	if ((typeof qa_wysiwyg_editor_config == 'object') && $('body').hasClass('qa-template-question'))
		qa_ckeditor_a_content=CKEDITOR.replace('a_content', window.qa_wysiwyg_editor_config);
	
	
	$('.question-label').click(function(){
		$(this).next().slideToggle()
	});
	
	$('.form-search .icon-search').click(function(){
		$('.search-query').focus();
	});
	
	
	//uncomment this code if you want to use default editor
	if ( $('body').hasClass('qa-template-question') && typeof (CKEDITOR) != 'undefined')
		qa_ckeditor_a_content=CKEDITOR.replace('a_content', window.qa_wysiwyg_editor_config);
	//$('.float-nav').css('min-height', $(window).height());
	//$('#left-sidebar').css('min-height', $(window).height());
	
	$('#left-position .widget-title').click(function(){
		$(this).next().slideToggle(200);
	});

	$('.voting a, .fav-btn, #focus_doanswer, .ra-tip ').tooltip({placement:'top'});
	
	$(window).resize(function(){
		$('.left-sidebar').removeAttr('style');
		$('.qa-main').removeAttr('style');
		$('#nav-top .qa-nav-main').removeAttr('style');
		qw_check_site_status_size();
		
		if($('#header-top').width() < 1300){
			$('.ra-social-links').css({'bottom':5, 'top':'auto'});
		}else{
			$('.ra-social-links').removeAttr('style');
		}
		
	});
	
	$('body').redraw();
	
	$('.qw_ask_form #notify').click(function(){
		$(this).closest('.form-group').next().toggle();
	});
	
	$('.share-btn').click(function(){
		if(!$(this).is('loaded')){
			if (typeof (IN) != 'undefined') {
				var addthis_config = {"data_track_addressbar":true};
			} else {
				$.getScript("//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-503f74fb52ed2b3c");
				var addthis_config = {"data_track_addressbar":true};
			}
			
			$(this).addClass('loaded');
		}
	});
	
	$('.about-me i').click(function(){
		$(this).parent().toggleClass('full');
		$(this).hide();
	});
	
	
	$(".oembed").oembed(null,{
		embedMethod: 'auto',    // "auto", "append", "fill" 
	});
	
	$('body').delegate('*[data-qawork]', 'click', function(){
		var action = $(this).data('qawork');
		
		if(action == 'change-cover')
			qw_change_cover(this);
	});

	$('*[data-action="slider"]').each(function(){
		var item = $(this);
		var opts = item.data('opt');
		if(typeof opts =='object'){
			$(item.children()).bxSlider(opts);
		}
	});
	
	$('.mobile-menu-toggle').click(function(){
		$(this).next().toggle();
	});

});

