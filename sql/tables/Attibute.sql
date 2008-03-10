create table Attribute (
	id serial,
	shortname varchar(255),
	title varchar(255),
	displayorder int not null default 0,
	attribute_type int not null references AttributeType(id),
	primary key (id)
);

