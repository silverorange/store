function StoreProvStateEntry(id, data)
{
	this.id = id;
	this.data = data;
	this.provstate_id = {};
	YAHOO.util.Event.onDOMReady(this.init, this, true);
};

StoreProvStateEntry.required_text = '(required)';

StoreProvStateEntry.prototype.init = function()
{
	this.flydown = document.getElementById(this.id + '_flydown');
	this.entry = document.getElementById(this.id + '_entry');
	this.field = YAHOO.util.Dom.getAncestorByClassName(
		this.flydown,
		'swat-form-field'
	);
	if (this.field) {
		var that = this;
		this.label = YAHOO.util.Dom.getElementBy(
			function (n) {
				return (n.getAttribute('for') == that.id + '_flydown');
			},
			'label',
			this.field
		);
	} else {
		this.label = null;
	}

	this.mode = document.createElement('input');
	this.mode.setAttribute('type', 'hidden');
	this.mode.setAttribute('name', this.id + '_mode');

	var form = YAHOO.util.Dom.getAncestorByTagName(this.flydown, 'form');
	form.appendChild(this.mode);

	if (this.country_flydown_id) {
		this.country = document.getElementById(this.country_flydown_id);

		YAHOO.util.Event.on(
			this.country,
			'change',
			this.handleCountryChange,
			this,
			true
		);

		var country_id =
			this.country.options[this.country.selectedIndex].value;

		this.provstate_id[country_id] =
			this.flydown.options[this.flydown.selectedIndex].value;

		this.updateProvState();
	}

	YAHOO.util.Event.on(
		this.flydown,
		'change',
		this.handleProvStateChange,
		this,
		true
	);
};

StoreProvStateEntry.prototype.setCountryFlydown = function(country_flydown_id)
{
	this.country_flydown_id = country_flydown_id;
};

StoreProvStateEntry.prototype.handleCountryChange = function(e)
{
	this.updateProvState();
};

StoreProvStateEntry.prototype.handleProvStateChange = function(e)
{
	if (this.country_flydown_id) {
		var country_id =
			this.country.options[this.country.selectedIndex].value;

		this.provstate_id[country_id] =
			this.flydown.options[this.flydown.selectedIndex].value;
	}

};

StoreProvStateEntry.prototype.updateProvState = function()
{
	var country_id =
		this.country.options[this.country.selectedIndex].value;

	if (this.data[country_id]) {
		var provstates = this.data[country_id].provstates;
		if (provstates === null) {
			YAHOO.util.Dom.removeClass(this.entry, 'swat-hidden');
			YAHOO.util.Dom.addClass(this.flydown, 'swat-hidden');
			if (this.label) {
				this.updateProvStateLabel(this.data[country_id], this.entry.id);
			}
			this.mode.value = 'entry';
		} else {
			YAHOO.util.Dom.addClass(this.entry, 'swat-hidden');
			YAHOO.util.Dom.removeClass(this.flydown, 'swat-hidden');
			while (this.flydown.firstChild) {
				this.flydown.removeChild(this.flydown.firstChild);
			}

			var option;

			// add blank option
			option = document.createElement('option');
			option.value = '';
			option.className = 'swat-blank-option';
			option.appendChild(
				document.createTextNode(
					this.data[country_id].select_title
				)
			);
			this.flydown.appendChild(option);

			// add provstates
			for (var i = 0; i < provstates.length; i++) {
				option = document.createElement('option');
				option.value = provstates[i].id;

				if (typeof this.provstate_id[country_id] != 'undefined' &&
					provstates[i].id == this.provstate_id[country_id]) {
					option.selected = 'selected';
				}

				option.appendChild(document.createTextNode(provstates[i].title));
				this.flydown.appendChild(option);
			}
			if (this.label) {
				this.updateProvStateLabel(this.data[country_id], this.flydown.id);
			}
			this.mode.value = 'flydown';
		}
	}
};

StoreProvStateEntry.prototype.updateProvStateLabel = function(data, for_value)
{
	this.label.setAttribute('for', for_value);

	if (!data.field_title) {
		return;
	}

	while (this.label.firstChild) {
		this.label.removeChild(this.label.firstChild);
	}

	this.label.appendChild(
		document.createTextNode(
			data.field_title
		)
	);

	if (data.required) {
		var required = document.createElement('span');
		required.className = 'swat-required';
		required.appendChild(
			document.createTextNode(
				StoreProvStateEntry.required_text
			)
		);

		this.label.appendChild(document.createTextNode(' '));
		this.label.appendChild(required);
	}
}
