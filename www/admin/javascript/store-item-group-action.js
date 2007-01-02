function ItemGroupAction(id, values)
{
	this.id = id;
	this.values = values;
	this.title = document.getElementById(this.id + '_title');
	this.title.style.display = 'none';
	this.groups = document.getElementById(this.id + '_groups');

	YAHOO.util.Event.addListener(this.groups, 'change',
		ItemGroupAction.handleGroupChange, this);

	this.init();
}

ItemGroupAction.handleGroupChange = function(event, item_group_action)
{
	item_group_action.init();
}

ItemGroupAction.prototype.init = function()
{
	if (this.values[this.groups.selectedIndex] == 'new_group') {
		this.title.style.display = 'inline';
		this.title.style.marginLeft = '0.5em';
		this.title.focus();
	} else {
		this.title.style.display = 'none';
	}
}
