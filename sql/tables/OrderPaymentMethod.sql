create table OrderPaymentMethod (
	id serial,

	ordernum integer references Orders(id) -- TODO: make not null

	payment_type integer not null references PaymentType(id),
	surcharge numeric(11, 2),

	card_type int null references CardType(id),
	card_fullname varchar(255),
	card_number_preview varchar(6),
	card_number text,
	card_expiry date,
	card_inception date,
	card_issue_number varchar(4),

	primary key (id)
);
