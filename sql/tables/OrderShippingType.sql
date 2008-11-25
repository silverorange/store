create table OrderShippingType (
	id serial,
	shortname varchar(255),
	title varchar(255),
	surcharge numeric(11, 2),
	primary key (id)
);
