create view InvoiceItemCountView as
	select Invoice.id, count(InvoiceItem.id) as item_count from InvoiceItem
		inner join Invoice on InvoiceItem.invoice = Invoice.id
	group by Invoice.id;
