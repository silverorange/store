create table ItemMinimumQuantityGroup (
	id serial,
	shortname varchar(50),
	title varchar(255),
	minimum_quantity integer not null default 1,
	primary key (id)
);
