create table Catalog (
	id serial,
	title varchar(255),
	clone_of int references Catalog(id) on delete set null,
	in_season boolean not null default true,
	primary key (id)
);
