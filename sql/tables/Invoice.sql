create table Invoice (
	id serial,
	account integer not null references Account(id) on delete cascade,
	locale int not null references Locale(id) on delete cascade,
	comments text,
	createdate timestamp not null,

	-- optional fields, all are nullable
	total numeric(11, 2),
	item_total numeric(11, 2),
	shipping_total numeric(11, 2),
	tax_total numeric(11, 2),

	primary key (id)
);

CREATE INDEX Invoice_account_index ON Invoice(account);
CREATE INDEX Invoice_createdate_index ON Invoice(createdate);
