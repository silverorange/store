create view InvoiceItemCountView as
	select Invoice.id as invoice, count(InvoiceItem.id) as item_count from InvoiceItem
		inner join Invoice on InvoiceItem.invoice = Invoice.id
	group by Invoice.id;
