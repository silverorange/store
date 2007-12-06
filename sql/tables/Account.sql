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
	default_billing_address integer,
	default_shipping_address integer,
	primary key (id)
);

alter table Account
	add constraint Accountfk foreign key (default_billing_address)
	references AccountAddress(id) match full on delete set null;

alter table Account
	add constraint Accountfk foreign key (default_shipping_address)
	references AccountAddress(id) match full on delete set null;

