create or replace view AccountOrderCountView as
	select account,
		count(id) as order_count
	from Orders
	group by account;
