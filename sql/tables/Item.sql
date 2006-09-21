create table Item (
	id serial,
	sku varchar(20),
	product int not null references Product(id) on delete cascade,
	displayorder int not null default 0,
	description varchar(255),
	status int not null default 0, -- TODO: push up to store (add to dataobject)
	item_group int references ItemGroup(id),
	primary key (id)
);

CREATE INDEX Item_sku_index ON Item(sku);
CREATE INDEX Item_product_index ON Item(product);
CREATE INDEX Item_item_group_index ON Item(item_group);
