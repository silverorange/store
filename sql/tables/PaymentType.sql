create table PaymentType (
	id serial,
	shortname varchar(100),
	enabled boolean not null default true,
	title varchar(255),
	note varchar(255),
	displayorder int not null default 0,
	credit_card boolean not null default false,
	surcharge numeric(11, 2),
	primary key(id)
);
