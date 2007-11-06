create table OrderAddress (
	id serial,
	fullname varchar(255) not null,
	company varchar(255),
	line1 varchar(255) not null,
	line2 varchar(255),
	city varchar(255) not null,
	provstate int references ProvState(id),
	provstate_other varchar(255),
	country char(2) not null references Country(id),
	postal_code varchar(50) not null,
	phone varchar(100),
	primary key (id)
);


CREATE INDEX OrderAddress_provstate_index ON OrderAddress(provstate);
CREATE INDEX OrderAddress_country_index ON OrderAddress(country);
