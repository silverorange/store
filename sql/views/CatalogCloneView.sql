create or replace view CatalogCloneView as
		select clone_of as catalog, id as clone, true::boolean as is_parent
		from Catalog
		where clone_of is not null
	union
		select id as catalog, clone_of as clone, false::boolean as is_parent
		from Catalog
		where clone_of is not null;
