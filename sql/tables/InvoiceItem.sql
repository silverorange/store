create table InvoiceItem (
	id serial,
	invoice int not null references Invoice(id) on delete cascade,
	sku varchar(20),
	quantity int not null,
	price numeric(11, 2) not null,
	description varchar(255),
	primary key (id)
);

CREATE INDEX InvoiceItem_invoice_index ON InvoiceItem(invoice);
