create or replace view CategoryChildCountView as
	select parent as category,
		count(id) as child_count
	from Category
	group by parent;
