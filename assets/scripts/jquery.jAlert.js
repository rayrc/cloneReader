;(function($) {
	var 
		methods,
		jAlert;
		
	methods = {
		init : function( options ) {
			if ($(this).data('jAlert') == null) {
				$(this).data('jAlert', new jAlert($(this), options));
			}
			$(this).data('jAlert').show($(this), options);			
			
			return $(this);
		},

		hide: function() {
			$(this).data('jAlert').hide();
			return $(this);
		}		
	};

	$.fn.jAlert = function( method ) {
		// Method calling logic
		if ( methods[method] ) {
			return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'string' || typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.tooltip' );
		}  
	}
	
	jAlert = function() {

	}
					
	jAlert.prototype = {
		/*
		 * input indica a que elemento se le pasara el foco cuando el jAlert se cierre
		 * options puede ser un object cons las propiedades {msg, callback }
		 * 			tambien puede ser un DomNode o un String, es este caso el pluggin se encarga de mergear las options 
		 */
		show: function($input, options) {
			this.$input		= $input;
			this.options 	= $.extend(
				{
					msg:			'',
					callback:		null
				},
				(typeof options === 'string' ? { msg: options } :
					($(options).get(0).tagName != null ? { msg: options } : options ) )
			);						 

			
		
			this.$modal		= $('<div role="dialog" class="modal jAlert" />');
			this.$body 		= $('<div />').html(this.options.msg).addClass('modal-body').appendTo(this.$modal);
			this.$footer 	= $('<div />').addClass('modal-footer').appendTo(this.$modal);
			this.$btn 		= $('<button data-dismiss="modal" class="btn" />').text('Cerrar').appendTo(this.$footer);
			
			// para evitar que se vaya el foco a otro elemento de la pagina con tab
			$(document).bind('keydown.jAlertKeydown', ($.proxy(
				function(event) {
					if (event.keyCode == 27) { // esc!
						this.$modal.modal('hide');
						return false;
					}
					if ($.contains(this.$modal[0], event.target)) {
						return true;
					}
					return false;
				}
			, this)));
			
			this.$modal
				.modal( {
					backdrop: true,
					keyboard: true
				})
				.css({ 'top': 200, })
				.on('hidden', $.proxy(
					function(event) {
						$(document).unbind('keydown.jAlertKeydown');
						if(this.options.callback instanceof Function) {
							this.options.callback();
						}
						this.$input.focus();
					}
				, this));
				
				$('.modal-backdrop')
					.css('opacity', 0.3)
					.unbind();
		}
	}
})($);