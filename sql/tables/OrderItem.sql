create table OrderItem (
	id serial,
	ordernum int not null references Orders(id) on delete cascade,
	sku varchar(20),
	alias_sku varchar(20),
	quantity int not null,
	price numeric(11, 2) not null,
	custom_price boolean not null default false,
	description varchar(255),
	extension numeric(11, 2) not null,
	item int null,
	product int null,
	product_title varchar(255) null,
	sale_discount int null,
	discount numeric(11, 2) not null default 0,
	discount_extension numeric(11, 2) not null default 0,
	catalog int,
	source integer,
	source_category integer,
	primary key (id)
);

CREATE INDEX OrderItem_extension_index ON OrderItem(extension);
CREATE INDEX OrderItem_catalog_index ON OrderItem(catalog);
CREATE INDEX OrderItem_ordernum_index ON OrderItem(ordernum);
