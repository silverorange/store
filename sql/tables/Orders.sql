create table Orders (
	id serial,

	account integer default null references Account(id) on delete set null,
	email varchar(255),
	cc_email varchar(255),
	phone varchar(100),
	company varchar(255),
	comments text,
	notes text,
	createdate timestamp not null,
	status integer not null default 1,
	cancelled boolean not null default false,
	failed_attempt boolean not null default false,

	billing_address integer not null references OrderAddress(id),
	shipping_address integer not null references OrderAddress(id),
	shipping_type integer null references ShippingType(id),

	total numeric(11, 2) not null,
	item_total numeric(11, 2) not null,
	shipping_total numeric(11, 2) null, -- null when shipping was not calculated
	surcharge_total numeric(11, 2) not null,
	tax_total numeric(11, 2) not null,

	ad integer default null references Ad(id),
	locale char(5) not null references Locale(id),
	invoice integer null references Invoice(id),
	instance integer default null references Instance(id),

	-- whether this order has been processed by the cron job that inserts popular products
	popular_products_processed boolean not null default false,

	primary key (id)
);

CREATE INDEX Orders_ad_index ON Orders(ad);
CREATE INDEX Orders_account_index ON Orders(account);
CREATE INDEX Orders_createdate_index ON Orders(createdate);
CREATE INDEX Orders_billing_address_index ON Orders(billing_address);
CREATE INDEX Orders_shipping_address_index ON Orders(shipping_address);
CREATE INDEX Orders_popular_products_processed_index ON Orders(popular_products_processed);
CREATE INDEX Orders_instance_index ON Orders(instance);
