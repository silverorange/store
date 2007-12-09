create table SaleDiscount (
	id serial,
	title varchar(255) not null,
	shortname varchar(255) not null,
	discount_percentage numeric(5, 2),
	start_date timestamp,
	end_date timestamp,

	primary key(id)
);
