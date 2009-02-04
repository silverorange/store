create table PaymentMethodTransaction (
	id serial,

	payment_method integer not null references OrderPaymentMethod(id),

	transaction_id varchar(255) not null,
	transaction_type integer not null,
	createdate timestamp,

	primary key (id)
);

create index PaymentMethodTransaction_transaction_id on
	PaymentMethodTransaction(transaction_id);
