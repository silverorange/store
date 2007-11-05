create table OrderItem (
	id serial,
	ordernum int not null references Orders(id) on delete cascade,
	sku varchar(20) not null,
	quantity int not null,
	price numeric(11, 2) not null,
	description varchar(255),
	extension numeric(11, 2) not null,
	item int null,
	product int null,
	product_title varchar(255) null,
	catalog int,
	quick_order boolean not null default false,
	primary key (id)
);

CREATE INDEX OrderItem_extension_index ON OrderItem(extension);
CREATE INDEX OrderItem_catalog_index ON OrderItem(catalog);
CREATE INDEX OrderItem_ordernum_index ON OrderItem(ordernum);
