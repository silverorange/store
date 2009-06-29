function StoreFeedbackForm(id)
{
	StoreFeedbackForm.superclass.constructor.call(this, id);
	this.submitted = false;
	this.init();
}

YAHOO.lang.extend(StoreFeedbackForm, SwatForm, {
});

StoreFeedbackForm.prototype.init = function()
{
	YAHOO.util.Event.on(
		this.form_element,
		'submit',
		this.handleSubmit,
		this,
		true
	);
}

StoreFeedbackForm.prototype.handleSubmit = function(e)
{
	if (this.submitted)
		return;

	var that = this;

	var callback =
	{
		'success': function(o) {
			alert('success'); // TODO
		},
		'failure': function(o) {
			alert('FAIL'); // TODO
		}
	};

	this.showThrobber();

	var request = YAHOO.util.Connect.asyncRequest(
		'POST',
		this.getFormUri(),
		callback,
		this.getFormData()
	);

	this.submitted = true;

	YAHOO.util.Event.preventDefault(e);
}

StoreFeedbackForm.prototype.showThrobber = function()
{
	this.throbber = document.createElement('div');
	this.throbber.style.position = 'absolute';
	this.throbber.style.top = '-10000px';
	this.throbber.style.background = '#000';
	this.throbber.style.textAlign = 'center';
	this.throbber.style.color = '#fff';
	this.throbber.style.fontSize = '200%';

	var span = document.createElement('span');
	span.style.padding = 

	this.throbber.appendChild(document.createTextNode('sending'));

	YAHOO.util.Dom.setStyle(this.throbber, 'opacity', '0.7');

	this.form_element.appendChild(this.throbber);

	var form_region = YAHOO.util.Dom.getRegion(this.form_element);

	var region = YAHOO.util.Dom.getRegion(this.throbber);

	var form_width  = form_region.right  - form_region.left;
	var form_height = form_region.bottom - form_region.top;

	this.throbber.style.width  = form_width  + 'px';
	this.throbber.style.height = form_height + 'px';

	var width  = region.right  - region.left;
	var height = region.bottom - region.top;

	YAHOO.util.Dom.setXY(this.throbber, [form_region.left, form_region.top]);
}

StoreFeedbackForm.prototype.getFormUri = function()
{
	return this.form_element.action;
}

StoreFeedbackForm.prototype.getFormData = function()
{
	var data = '';

	var inputNodes = this.form_element.getElementsByTagName('input');
	for (var i = 0; i < inputNodes.length; i++) {
		if (inputNodes[i].name) {
			data += '&' + encodeURIComponent(inputNodes[i].name) + '=' +
				encodeURIComponent(inputNodes[i].value);
		}
	}

	var textareaNodes = this.form_element.getElementsByTagName('textarea');
	for (var i = 0; i < textareaNodes.length; i++) {
		if (textareaNodes[i].name) {
			data += '&' + encodeURIComponent(textareaNodes[i].name) + '=' +
				encodeURIComponent(textareaNodes[i].value);
		}
	}

	var checkboxNodes = this.form_element.getElementsByTagName('checkbox');
	for (var i = 0; i < checkboxNodes.length; i++) {
		if (checkboxNodes[i].name) {
			if (checkboxNodes[i].checked) {
				data += '&' + encodeURIComponent(checkboxNodes[i].name) + '=' +
					encodeURIComponent(checkboxNodes[i].value);
			} else {
				data += '&' + encodeURIComponent(checkboxNodes[i].name) + '=';
			}
		}
	}

	var radioNodes = this.form_element.getElementsByTagName('radio');
	for (var i = 0; i < radioNodes.length; i++) {
		if (radioNodes[i].checked && radioNodes[i].name) {
			data += '&' + encodeURIComponent(radioNodes[i].name) + '=' +
				encodeURIComponent(radioNodes[i].value);
		}
	}

	var selectNodes = this.form_element.getElementsByTagName('select');
	for (var i = 0; i < selectNodes.length; i++) {
		var select = selectNodes[i];
		if (select.name) {
			data += '&' + encodeURIComponent(select.name) + '=' +
				encodeURIComponent(select.options[select.selectedIndex].value);
		}
	}

	data = data.substr(1);

	return data;
}
