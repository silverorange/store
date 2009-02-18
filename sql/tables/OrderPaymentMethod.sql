create table OrderPaymentMethod (
	id serial,

	ordernum integer not null references Orders(id) on delete cascade,

	payment_type integer not null references PaymentType(id),
	surcharge numeric(11, 2),

	card_type int null references CardType(id),
	card_fullname varchar(255),
	card_number_preview varchar(6),
	card_number text,
	card_expiry date,
	card_inception date,
	card_issue_number varchar(4),

	payer_id varchar(255),
	payer_email varchar(255),

	primary key (id)
);

CREATE INDEX OrderPaymentMethod_ordernum ON OrderPaymentMethod(ordernum);
