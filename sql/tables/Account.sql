-- renamed from customers
create table Account (
	id serial,
	fullname varchar(255),
	company varchar(255),
	email varchar(255),
	phone varchar(100),
	password varchar(255),
	password_salt varchar(50),
	password_tag varchar(255),
	createdate timestamp,
	last_login timestamp,
	default_billing_address integer references AccountAddress(id),
	default_shipping_address integer references AccountAddress(id),
	primary key (id)
);
