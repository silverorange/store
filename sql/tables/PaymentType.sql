create table PaymentType (
	id serial,
	shortname varchar(100),
	title varchar(255),
	note varchar(255),
	displayorder int not null default 0,
	surcharge numeric(11, 2),
	priority int not null default 0,
	primary key(id)
);
