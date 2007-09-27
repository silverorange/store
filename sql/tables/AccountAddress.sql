create table AccountAddress (
	id serial,
	account int not null references Account(id) on delete cascade,
	fullname varchar(255),
	company varchar(255),
	line1 varchar(255),
	line2 varchar(255),
	city varchar(255),
	provstate int references ProvState(id),
	provstate_other varchar(255),
	country char(2) not null references Country(id),
	postal_code varchar(50) not null,
	phone varchar(100),
	default_address boolean not null default false,
	createdate timestamp,
	primary key (id)
);

CREATE INDEX AccountAddress_account ON AccountAddress(account);
