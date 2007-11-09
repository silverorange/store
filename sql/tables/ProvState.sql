create table ProvState (
	id serial,
	country char(2) not null references Country(id) on delete cascade,
	title varchar(100),
	abbreviation varchar(10),
	primary key (id)
);

CREATE INDEX ProvState_country_index ON ProvState(country);
