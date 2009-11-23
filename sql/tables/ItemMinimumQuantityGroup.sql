create table ItemMinimumQuantityGroup (
	id serial,
	shortname varchar(50),
	title varchar(255),
	minimum_quantity integer not null default 1,
	description text,
	part_unit varchar(50),
	part_unit_plural varchar(50),
	primary key (id)
);
