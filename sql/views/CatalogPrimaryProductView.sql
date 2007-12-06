create or replace view CatalogPrimaryProductView as

select max(id) as product, max(catalog) as catalog
	from Product group by shortname;
