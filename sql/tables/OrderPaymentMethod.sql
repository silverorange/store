create table OrderPaymentMethod (
	id serial,
	payment_type int not null references PaymentType(id),
	card_fullname varchar(255),
	card_lastdigits varchar(6),
	card_number text,
	card_expiry date,
	card_inception date,
	card_issue_number varchar(4),
	primary key (id)
);
