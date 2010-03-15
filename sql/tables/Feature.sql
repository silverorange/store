create table Feature (
	id serial,
	region integer references Region(id),
	shortname varchar(255),
	title varchar(255),
	link varchar(255),
	start_date timestamp,
	end_date timestamp,
	enabled boolean not null default true,
	display_slot int not null,
	priority int not null default 0,
	description varchar(500),
	primary key (id)
);
