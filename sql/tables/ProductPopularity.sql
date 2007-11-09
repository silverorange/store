create table ProductPopularity (
	product integer not null references Product(id) on delete cascade,
	order_count integer not null default 0,
	primary key(product)
);
