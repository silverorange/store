create table PaymentTransaction (
	id serial,
	transaction_id varchar(255),
	security_key varchar(255),
	authorization_code varchar(255),
	createdate timestamp not null,
	address_status integer,
	postal_code_status integer,
	card_verification_value_status integer,
	three_domain_secure_status integer,
	request_type integer not null,
	ordernum integer not null references Orders(id) on delete cascade,
	primary key (id)
);
