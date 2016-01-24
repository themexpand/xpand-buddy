/**
 * Validator: required
 	Displays an error if the field is empty.
 	Error Msg: "This field is required"

 	Validator: length
 	Displays a message if the input value is not the exact supplied length.
 	Error Msg: "Please enter [defined length] characters (you entered [input length] characters)"
	Note: You must add this name AND properties for it to your input.
    Example: input type="text" name="username" class="length:10" id="username" />

 	Validator: minLength
 	Displays a message if the input value is less than the supplied length.
	Error Msg: "Please enter at least [defined minLength] characters (you entered [input length] characters)"
 	Note: You must add this name AND properties for it to your input.
 	Example: input type="text" name="username" class="minLength:10" id="username" />

 	Validator: maxLength
 	Displays a message if the input value is less than the supplied length.
 	Error Msg: "Please enter no more than [defined maxLength] characters (you entered [input length] characters)"
 	Note: You must add this name AND properties for it to your input.
 	Example: input type="text" name="username" class="maxLength:10" id="username" />

 	Validator: validate-numeric
 	Validates that the entry is a number.
 	Error Msg: 'Please enter only numeric values in this field ("1" or "1.1" or "-1" or "-1.1").'

 	Validator: validate-integer
 	Validates that the entry is an integer.
 	Error Msg: "Please enter an integer in this field. Numbers with decimals (e.g. 1.25) are not permitted."

 	Validator: validate-digits
	Validates that the entry contains only numbers but allows punctuation and spaces (for example, a phone number)
 	Error Msg: "Please use numbers only in this field. Please avoid spaces or other characters such as dots or commas."

 	Validator: validate-alpha
 	Validates that the entry contains only letters
 	Error Msg - "Please use letters only (a-z) in this field."

 	Validator: validate-alphanum
 	Validates that the entry is letters and numbers only
 	Error Msg: "Please use only letters (a-z) or numbers (0-9) only in this field. No spaces or other characters are allowed."

 	Validator: validate-date
 	Validates that the entry parses to a date. The dateFormat property can be set to format the date after the field is validated.
	If you want to validate a custom format, you should use Date.defineParser or use Date Locale. If Date is not included in your build, only the dd/mm/yy or dd/mm/yyyy formats are accepted.
	Error Msg: "Please enter a valid date (such as 12/31/1999)"
 	Example: input data-validators="validate-date dateFormat:'%d/%m/%Y'" />

 	Validate: validate-email
 	Validates that the entry is a valid email address.
 	Error Msg: "Please enter a valid email address. For example 'fred@domain.com'."

 	Validate: validate-url
 	Validates that the entry is a valid url
 	Error Msg: "Please enter a valid URL."

 	Validator: validate-currency-dollar
 	Validates that the entry matches any of the following:
     [$]1[##][,###]+[.##]
     [$]1###+[.##]
     [$]0.##
     [$].##
 	Error Msg: "Please enter a valid $ amount. For example $100.00 ."

 	Validator: validate-one-required
 	Validates that all the entries within the same node are not empty.
 	Error Msg: "Please enter something for at least one of the above options."
 	Note: This validator will get the parent element for the input and then check all its children. To use this validator, enclose all the inputs you want to group in another element (doesn't matter which); you only need apply this class to one of the elements.
 	Example
	div>
     input ..../>
     input ..../>
     input .... data-validators="validate-one-required"/>
 	/div>
 *
 */
Form.Validator.add('validate-one-required', {
		errorMsg: Form.Validator.getMsg.pass('oneRequired'),
		test: function(element, props){
			var p = document.id(props['validate-one-required']) || element.getParent(props['validate-one-required']);
			var inputs=new Array();
			var type=element.get('type');
			if( p.tagName=='LABEL' ){
				var index=0;
				p.getParent().getChildren().each(function(label,i){
					var el=label.getElement('input[type="'+ type +'"]');
					if( el!=null ){
						inputs[index]=el;
						index++;
					}
				});
			} else {
				inputs=p.getElements('input[type="'+ type +'"]');
			}
			return inputs.some(function(el){
				if (['checkbox', 'radio'].contains(el.get('type'))) return el.get('checked');
				return el.get('value');
			});
		}
	});
	
Form.Validator.add('validate-ckeditor', {
	errorMsg: Form.Validator.getMsg.pass('required'),
	test: function(element){
		ckeditor = CKEDITOR.instances[ element.get('id') ].getData().replace(/<[^>]*>/gi, '');
		if( ckeditor.length )
			return true;
		return false;
	}
});

Form.Validator.add('validate-minmax', {
	errorMsg: function(element, props){
		return 'Value must be from:'+props.min+' to:'+props.max;
	},
	test: function(element, props){
		return !(element.get('value') < props.min || element.get('value') > props.max);
	}
});

var WhValidatorInit=new Class({
	Extends: Form.Validator.Inline,

	initialize: function( form ){
		this.parent(form, {
			scrollToErrorsOnSubmit: true,
			evaluateFieldsOnBlur: true,
			evaluateFieldsOnChange: false,
			scrollFxOptions: {offset:{'x':0,'y':-50}}
		} );
		this.initEvent();
	},
	// переопределение родительского метода, отключаем события change & blur при проверке полей.
	watchFields:function(fields){},

	initWatchFields: function(fields){
		fields.each(function(el){
			if(!el.hasClass('validation-failed')){
				return;
			}
			if (this.options.evaluateFieldsOnBlur)
				el.addEvent('blur', this.validationMonitor.pass([el, true], this));
			if (this.options.evaluateFieldsOnChange)
				el.addEvent('change', this.validationMonitor.pass([el, true], this));
		}, this);
	},

	check: function(){
		return this.validate();
	},

	initEvent: function(){
		this.element.getElements('[type=submit]').each(function(el){
			el.addEvent('click', function(e){
				this.element.getElements('[type=submit]').each(function(i){ if( $(i.name+'_hidden_submit') ){ $(i.name+'_hidden_submit').destroy(); } });
				var input=new Element('input[type="hidden"]',{value:el.value,name:el.name,id:el.name+'_hidden_submit'});
				input.inject(el.getParent());
			}.bind(this));
		},this);
		this.addEvent('onElementFail', function( field ){
			this.rebuild( field );
			if( typeof objAccordion!=='undefined' ){
				this.stop();
				var block = parseInt( field.getParent('div.element' ).id );
				objAccordion.display(block);
				new WhValidator();
			}
		});
		this.addEvent('onElementPass', function( field ){
			field.removeEvents('blur');
			field.removeEvents('change');
			this.element.removeClass('form-validation-failed');
		}.bind(this));
		this.addEvent('onFormValidate', function( isValid ){
			var elements=this.element.getElements('[type=submit]');
			if ( isValid ){
				elements.set( 'disabled',true );
			} else {
				elements.set( 'disabled','' );
				if(!this.element.hasClass('form-validation-failed')){
					this.initWatchFields(this.getFields());
					this.element.addClass('form-validation-failed');
				}
			}
		}.bind(this));
	},

	rebuild: function( field ){
		var element=field.getParent().getChildren('.validation-advice')[0];
		element.getParent().setStyle('position','relative');
		element.addClass('wh-validator-theme');
		var a = new Element('a', {'class':'validator-close', 'href':'#'});
		a.inject(element);
		this.initClose();
	},
	initClose: function(){
		$$('a.validator-close').each(function(a){
			a.addEvent('click', function(e){
				e.stop();
				var field=a.getParent('div').getPrevious();
				this.resetField( field );
			}.bind(this));
		},this);
	}
});

var WhValidator=new Class({
	Implements: Options,
	options: {
		className:'validate'
	},
	checker:{},

	initialize: function( options ){
		this.setOptions( options );
		$$('form.'+this.options.className ).each( function( form ){
			this.reinit( form );
		},this);
	},
	reinit: function( form ){
		this.checker=new WhValidatorInit( form );
	}
});