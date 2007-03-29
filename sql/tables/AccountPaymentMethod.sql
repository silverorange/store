-- Payment methods saved in an account for repeat usage
create table AccountPaymentMethod (
	id serial,
	account int not null references Account(id) on delete cascade,
	payment_type int not null references PaymentType(id),
	card_fullname varchar(255),
	card_number_preview varchar(6),
	card_number text,
	card_expiry date,
	card_inception date,
	card_issue_number varchar(4),
	primary key (id)
);

CREATE INDEX AccountPaymentMethod_account ON AccountPaymentMethod(account);

-- This column belongs on Account but can only be added after
-- AccountPaymentMethod exists
ALTER TABLE Account ADD COLUMN default_payment_method int references AccountPaymentMethod(id);
