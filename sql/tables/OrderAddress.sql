create table OrderAddress (
	id serial,
	fullname varchar(255) not null,
	line1 varchar(255) not null,
	line2 varchar(255),
	city varchar(255) not null,
	provstate int not null references ProvState(id),
	country char(2) not null references Country(id),
	postal_code varchar(50) not null,
	primary key (id)
);


CREATE INDEX OrderAddress_provstate_index ON OrderAddress(provstate);
CREATE INDEX OrderAddress_country_index ON OrderAddress(country);
