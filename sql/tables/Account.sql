alter table Account add company varchar(255);
alter table Account add phone varchar(100);
alter table Account add available_credit decimal(11,2) not null default 0;

alter table Account add default_billing_address integer
	references AccountAddress(id) on delete set null;

alter table Account add default_shipping_address integer
	references AccountAddress(id) on delete set null;

alter table Account add default_payment_method integer
	references AccountPaymentMethod(id) on delete set null;

