create table Item (
	id serial,
	sku varchar(20),
	product int not null references Product(id) on delete cascade,
	displayorder int not null default 0,
	description varchar(255),
	status int not null default 0,
	item_group int references ItemGroup(id) on delete set null,
	sale_discount int references SaleDiscount(id) on delete set null,
	part_unit varchar(100),
	part_count integer not null default 1,
	singular_unit varchar(100),
	plural_unit varchar(100),
	minimum_quantity int not null default 1,
	minimum_multiple boolean not null default false,
	minimum_quantity_group int references ItemMinimumQuantityGroup(id) on delete set null,
	primary key (id)
);

CREATE INDEX Item_sku_index ON Item(sku);
CREATE INDEX Item_status_index ON Item(status);
CREATE INDEX Item_product_index ON Item(product);
CREATE INDEX Item_item_group_index ON Item(item_group);
