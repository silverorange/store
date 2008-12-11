create table ProductPopularity (
	product integer not null references Product(id) on delete cascade,
	order_count integer not null default 0,
	total_quantity integer not null default 0,
	total_sales numeric(11, 2) not null default 0,
	primary key(product)
);
