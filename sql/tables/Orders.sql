create table Orders (
	id serial,
	account integer default null references Account(id) on delete set null,
	email varchar(255),
	phone varchar(100),
	comments text,
	createdate timestamp not null,
	status integer not null default 1,
	cancelled boolean not null default false,

	billing_address integer not null references OrderAddress(id),
	shipping_address integer not null references OrderAddress(id),
	payment_method integer not null references OrderPaymentMethod(id),

	total numeric(11, 2) not null,
	item_total numeric(11, 2) not null,
	shipping_total numeric(11, 2) not null,
	tax_total numeric(11, 2) not null,

	ad integer null references Ad(id),
	locale char(5) not null references Locale(id),
	invoice integer null references Invoice(id),

	primary key (id)
);

CREATE INDEX Orders_ad_index ON Orders(ad);
CREATE INDEX Orders_payment_method_index ON Orders(payment_method);
CREATE INDEX Orders_account_index ON Orders(account);
CREATE INDEX Orders_createdate_index ON Orders(createdate);
CREATE INDEX Orders_billing_address_index ON Orders(billing_address);
CREATE INDEX Orders_shipping_address_index ON Orders(shipping_address);
