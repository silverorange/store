function ItemGroupAction(id, values)
{
	var self = this;
	var is_ie = (document.addEventListener) ? false : true;

	this.id = id;
	this.values = values;
	this.title = document.getElementById(this.id + '_title');
	this.title.style.display = 'none';
	this.groups = document.getElementById(this.id + '_groups');

	function groupChangeHandler()
	{
		self.handleChange();
	}

	if (is_ie)
		this.groups.attachEvent('onchange', groupChangeHandler);
	else
		this.groups.addEventListener('change', groupChangeHandler, false);

	this.handleChange();
}

ItemGroupAction.prototype.handleChange = function()
{
	if (this.values[this.groups.selectedIndex] == 'new_group') {
		this.title.style.display = 'inline';
		this.title.style.marginLeft = '0.5em';
		this.title.focus();
	} else {
		this.title.style.display = 'none';
	}
}
