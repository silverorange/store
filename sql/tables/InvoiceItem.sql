create table InvoiceItem (
	id serial,
	invoice integer not null references Invoice(id) on delete cascade,
	sku varchar(20),
	quantity integer not null,
	price numeric(11, 2) not null,
	description varchar(255),
	displayorder integer not null default 0,
	primary key (id)
);

CREATE INDEX InvoiceItem_invoice_index ON InvoiceItem(invoice);
