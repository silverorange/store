-- Payment methods saved in an account for repeat usage
create table AccountPaymentMethod (
	id serial,
	account int not null references Account(id) on delete cascade,
	payment_type int not null references PaymentType(id),
	credit_card_fullname varchar(255),
	credit_card_last4 varchar(6),
	credit_card_number text,
	credit_card_expiry date,
	primary key (id)
);

CREATE INDEX AccountPaymentMethod_account ON AccountPaymentMethod(account);

-- This column belongs on Account but can only be added after
-- AccountPaymentMethod exists
ALTER TABLE Account ADD COLUMN default_payment_method int references AccountPaymentMethod(id);
