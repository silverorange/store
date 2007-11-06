create table Country (
	-- this is the ISO-3611 two-letter country code for this country
	id char(2),
	title varchar(255),
	show boolean not null default true,
	primary key(id)
);
