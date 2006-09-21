/**
 * Clones a catalogue on Veseys
 *
 * Cloning a catalogue involves cloning the following relations:
 *
 * catalogs
 * |
 * +- catalog_region
 * |
 * +- product
 *    |
 *    +- product_icon
 *    |
 *    +- item_group
 *    |
 *    +- category_featured_product
 *    |
 *    +- category_product
 *    |
 *    +- article_product
 *    |
 *    + relatedproducts_category
 *    |
 *    +- items
 *       |
 *       + item_region
 *
 *
 * @param_id INTEGER: the id of the catalogue to clone.
 *
 * Returns the new catalog id. If the catalogue cannot be cloned, -1 is returned.
 */
CREATE OR REPLACE FUNCTION cloneCatalog (INTEGER, VARCHAR(255)) RETURNS INTEGER AS $$
	DECLARE
		param_id ALIAS FOR $1;
		param_title ALIAS FOR $2;
		local_id INTEGER;
		local_clone_of INTEGER;
		local_new_product_id INTEGER;
		local_old_product_id INTEGER;
		local_new_item_id INTEGER;
		local_old_item_id INTEGER;
		local_new_quantity_discount_id INTEGER;
		local_old_quantity_discount_id INTEGER;
		record_product record;
		record_item record;
		record_quantity_discount record;
	BEGIN
		-- make sure we are not cloning a clone
		select into local_clone_of coalesce(clone_of, 0) from Catalog where id = param_id;

		if local_clone_of != 0 or not found then
			return -1;
		end if;

		-- clone catalogue
		insert into Catalog (title, clone_of)
		select param_title, id from Catalog where id = param_id;

		local_id := currval('catalog_id_seq');

		-- clone region binding
		-- cloned catalogues are unavailable by default
		--insert into CatalogRegionBinding (region, catalog, available)
		--select region, local_id, false from CatalogRegionBinding where catalog = param_id; 

		-- clone products
		for record_product in
			select id, title, bodytext, page_number, subtitle,
				shortname, image
			from Product
			where catalog = param_id
		loop
			local_old_product_id := record_product.id;

			insert into Product (catalog, title, bodytext, createdate, 
				page_number, subtitle,
				shortname, image)
			values (local_id, record_product.title, record_product.bodytext, LOCALTIMESTAMP,
				record_product.page_number, record_product.subtitle,
				record_product.shortname, record_product.image);

			local_new_product_id := currval('product_id_seq');

			-- clone category binding
			insert into CategoryProductBinding (category, product, displayorder)
			select category, local_new_product_id, displayorder from CategoryProductBinding where product = local_old_product_id;

			-- clone featured in category binding
			insert into CategoryFeaturedProductBinding (category, product, displayorder)
			select category, local_new_product_id, displayorder from CategoryFeaturedProductBinding where product = local_old_product_id;

			-- clone item_groups
			insert into ItemGroup (product, title, displayorder)
			select local_new_product_id, title, displayorder from ItemGroup where product = local_old_product_id;

			-- clone items
			for record_item in
				select id, sku, displayorder, description, status, item_group
				from Item
				where product = local_old_product_id
			loop
				local_old_item_id := record_item.id;

				insert into Item(sku, product, displayorder, description, status, item_group)
				values (record_item.sku, local_new_product_id, record_item.displayorder, record_item.description,
					record_item.status, record_item.item_group);

				local_new_item_id := currval('item_id_seq');

				-- clone region binding
				insert into ItemRegionBinding (item, region, price)
				select local_new_item_id, region, price from ItemRegionBinding where item = local_old_item_id;

			end loop;
			-- items
		end loop;
		-- products

		RETURN local_id;
	END;
$$ LANGUAGE 'plpgsql';
