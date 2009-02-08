alter table Account add company varchar(255);
alter table Account add phone varchar(100);

alter table Account add default_billing_address integer
	references AccountAddress(id) on delete set null;

alter table Account add default_shipping_address integer
	references AccountAddress(id) on delete set null;

-- AccountPaymentMethod doesn'ts exist yet, so the constraint can't be added
-- here. It's added in AccountPaymentMethod.sql
alter table Account add default_payment_method integer;
