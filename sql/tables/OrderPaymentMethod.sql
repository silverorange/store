create table OrderPaymentMethod (
	id serial,
	payment_type int not null references PaymentType(id),
	credit_card_fullname varchar(255),
	credit_card_last4 varchar(6),
	credit_card_number text,
	credit_card_expiry date,
	primary key (id)
);
