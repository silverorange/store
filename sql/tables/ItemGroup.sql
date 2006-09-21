create table ItemGroup (
	id serial,
	product int not null references Product(id) on delete cascade,
	title varchar(255),
	displayorder int not null default 0,
	primary key (id)
);
