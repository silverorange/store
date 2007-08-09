-- Never store the payer authentication request or response for 3-D Secure
-- transactions. Doing so is equivalent to storing card numbers in the eyes of
-- card companies and requires security auditing. Storing the merchant data
-- is allowed.
create table PaymentTransaction (
	id serial,
	transaction_id varchar(255),
	security_key varchar(255),
	authorization_code varchar(255),
	merchant_data varchar(255), -- for 3-D Secure transactions
	createdate timestamp not null,
	address_status integer,
	postal_code_status integer,
	card_verification_value_status integer,
	three_domain_secure_status integer,
	cavv varchar(255), -- card authentication verification value
	request_type integer not null,
	ordernum integer not null references Orders(id) on delete cascade,
	primary key (id)
);

create index PaymentTransaction_merchant_data on
	PaymentTransaction(merchant_data);
