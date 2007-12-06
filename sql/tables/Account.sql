alter table Account add company varchar(255);
alter table Account add phone varchar(100);

alter table Account add default_billing_address integer
	references AccountAddress(id) on delete set null;

alter table Account add default_shipping_address integer
	references AccountAddress(id) on delete set null;

