/**
 * Clones a catalog
 *
 * Cloning a catalog involves cloning the following relations:
 *
 * Catalog
 * |
 * +- CatalogRegionBinding
 * |
 * +- Product
 *    |
 *    +- ProductAttributeBinding
 *    |
 *    +- ProductImageBinding [1]
 *    |
 *    +- ItemGroup [2]
 *    |
 *    +- CategoryProductBinding
 *    |
 *    +- CategoryFeaturedProductBinding
 *    |
 *    +- ProductRelatedProductBinding [3]
 *    |
 *    +- ProductPopularProductBinding [3] [4]
 *    |
 *    +- ProductPopularity [4]
 *    |
 *    +- ProductReview
 *    |
 *    +- Item
 *       |
 *       +- ItemRegionBinding
 *       |
 *       +- ItemAlias
 *       |
 *       +- QuantityDiscount
 *          |
 *          +- QuantityDiscountRegionBinding
 *
 *
 * [1] Images are not cloned; only the bindings to images are cloned.
 * [2] This relation is used by both cloned products and cloned items.
 * [3] These relations relates two products which could potentially both be
 *     clones. Care must be taken to relate the cloned products properly.
 * [4] These relations should only be cloned when a clone catalog is enabled. The
 *     reason is customers could purchase the parent product during the time the
 *     clone is disabled.
 *
 * @param_id    INTEGER:      the id of the catalog to clone.
 * @param_title VARCHAR(255): the title of the new catalog.
 *
 * Returns INTEGER the new catalog id. If the catalog cannot be cloned, -1 is returned.
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
		local_related_product_source INTEGER;
		local_related_product_related INTEGER;
		record_product record;
		record_item record;
		record_item_group record;
		record_cloned_item record;
		record_quantity_discount record;
		record_product_related_product record;
		record_product_review record;
		record_cloned_product_review record;
	BEGIN
		-- disable cache table triggers
		alter table CategoryProductBinding disable trigger VisibleProductTrigger;
		alter table Item disable trigger VisibleProductTrigger;
		alter table ItemRegionBinding disable trigger VisibleProductTrigger;

		-- make sure we are not cloning a clone
		select into local_clone_of coalesce(clone_of, 0) from Catalog where id = param_id;

		if local_clone_of != 0 or not found then
			return -1;
		end if;

		-- clone catalog
		insert into Catalog (title, clone_of, in_season)
		select param_title, id, in_season from Catalog where id = param_id;

		local_id := currval('catalog_id_seq');

		-- clone region binding
		-- cloned catalogs are disabled by default
		--insert into CatalogRegionBinding (region, catalog)
		--select region, local_id from CatalogRegionBinding where catalog = param_id;

		-- map of old and new products so we can clone ProductRelatedProductBinding and ProductPopularProductBinding
		create temporary table ClonedProductMap (
			old_id integer,
			new_id integer,
			primary key (old_id, new_id)
		);

		-- list of cloned items with item groups so we can update item_group relation on Item
		create temporary table ClonedItem (
			id integer,
			item_group integer,
			primary key (id)
		);

		-- map of old and new item groups so we can update item_group relation on Item
		create temporary table ClonedItemGroupMap (
			old_id integer,
			new_id integer,
			primary key (old_id, new_id)
		);

		-- map of old and new product reviews so we can update the parent relation on ProductReview
		create temporary table ClonedProductReviewMap (
			old_id integer,
			new_id integer,
			prmiary key (old_id, new_id)
		);

		-- clone products
		for record_product in
			select
				id,
				title,
				bodytext,
				shortname
			from Product
			where catalog = param_id
		loop
			local_old_product_id := record_product.id;

			insert into Product (
				catalog,
				title,
				bodytext,
				shortname,
				createdate)
			values (
				local_id,
				record_product.title,
				record_product.bodytext,
				record_product.shortname,
				LOCALTIMESTAMP);

			local_new_product_id := currval('product_id_seq');

			-- store cloned product in map
			insert into ClonedProductMap (old_id, new_id)
			values (local_old_product_id, local_new_product_id);

			-- clone attribute binding
			insert into ProductAttributeBinding (product, attribute)
			select local_new_product_id, attribute from ProductAttributeBinding where product = local_old_product_id;

			-- clone image binding (images are not cloned)
			insert into ProductImageBinding (product, image, displayorder)
			select local_new_product_id, image, displayorder from ProductImageBinding where product = local_old_product_id;

			-- clone category binding
			insert into CategoryProductBinding (category, product, minor, displayorder)
			select category, local_new_product_id, minor, displayorder from CategoryProductBinding where product = local_old_product_id;

			-- clone featured in category binding
			insert into CategoryFeaturedProductBinding (category, product, displayorder)
			select category, local_new_product_id, displayorder from CategoryFeaturedProductBinding where product = local_old_product_id;

			-- clone product reviews
			for record_product_review in
				select
					id,
					parent,
					instance,
					author,
					author_review,
					fullname,
					link,
					email,
					bodytext,
					status,
					spam,
					ip_address,
					user_agent,
					createdate
				from ProductReview
				where product = local_old_product_id
			loop
				insert into ProductReview
				(
					product,
					parent,
					instance
					author,
					author_review,
					fullname,
					link,
					email,
					bodytext,
					status,
					spam,
					ip_address,
					user_agent,
					createdate
				) values (
					local_new_product_id,
					record_product_review.parent,
					record_product_review.instance
					record_product_review.author,
					record_product_review.author_review,
					record_product_review.fullname,
					record_product_review.link,
					record_product_review.email,
					record_product_review.bodytext,
					record_product_review.status,
					record_product_review.spam,
					record_product_review.ip_address,
					record_product_review.user_agent,
					record_product_review.createdate
				);

				-- store cloned product-review in map
				insert into ClonedProductReview (new_id, old_id)
				values (currval('productreview_id_seq'),
					record_product_review.id);
			end loop;
			-- end clone product reviews

			-- update product review parent relations
			-- note: This doesn't handle the case when:
			--  product1 is cloned
			--  product1.review1 is cloned
			--  product2 is not cloned (different catalog)
			--  product2.review2.parent = product1.review1
			-- Fortunately, this should never happen.
			for record_cloned_product_review in
				select
					id,
					parent
				from ProductReview
					inner join ClonedProductReview on
						ProductReview.parent = ClonedProductReview.old_id
				where id in (select new_id from ClonedProductReview)
			loop
				update ProductReview set
					parent = record_cloned_product_review.new_id
				where id = record_cloned_product_review.id
			end loop;
			-- end update product review parent relations

			-- clone item groups
			for record_item_group in
				select id, title, displayorder
				from ItemGroup
				where product = local_old_product_id
			loop
				insert into ItemGroup (
					product,
					title,
					displayorder)
				values (
					local_new_product_id,
					record_item_group.title,
					record_item_group.displayorder);

				-- store cloned item-group in map
				insert into ClonedItemGroupMap (new_id, old_id)
				values (currval('itemgroup_id_seq'), record_item_group.id);

			end loop;
			-- end clone item groups

			-- clone items
			for record_item in
				select
					id,
					sku,
					displayorder,
					description,
					status,
					item_group,
					sale_discount,
					part_unit,
					part_count,
					singular_unit,
					plural_unit
				from Item
				where product = local_old_product_id
			loop
				local_old_item_id := record_item.id;

				insert into Item (
					sku,
					product,
					displayorder,
					description,
					status,
					item_group,
					sale_discount,
					part_unit,
					part_count,
					singular_unit,
					plural_unit)
				values (
					record_item.sku,
					local_new_product_id,
					record_item.displayorder,
					record_item.description,
					record_item.status,
					record_item.item_group,
					record_item.sale_discount,
					record_item.part_unit,
					record_item.part_count,
					record_item.singular_unit,
					record_item.plural_unit);

				local_new_item_id := currval('item_id_seq');

				-- store cloned item and item-group
				insert into ClonedItem (id, item_group)
				values (local_new_item_id, record_item.item_group);

				-- clone region binding
				insert into ItemRegionBinding (item, region, price)
				select local_new_item_id, region, price from ItemRegionBinding where item = local_old_item_id;

				-- clone aliases
				insert into ItemAlias (item, sku)
				select local_new_item_id, sku from ItemAlias where item = local_old_item_id;

				-- clone quantity_discounts
				for record_quantity_discount in
					select
						id,
						quantity
					from QuantityDiscount
					where item = local_old_item_id
				loop
					local_old_quantity_discount_id := record_quantity_discount.id;

					insert into QuantityDiscount (
						item,
						quantity)
					values (
						local_new_item_id,
						record_quantity_discount.quantity);

					local_new_quantity_discount_id := currval('quantitydiscount_id_seq');

					-- clone region binding
					insert into QuantityDiscountRegionBinding (quantity_discount, region, price)
					select local_new_quantity_discount_id, region, price from QuantityDiscountRegionBinding where quantity_discount = local_old_quantity_discount_id;

				end loop;
				-- quantity discounts
			end loop;
			-- items
		end loop;
		-- products

		-- update cloned item groups in cloned items
		for record_cloned_item in
			select
				id,
				new_id as item_group_new_id
			from ClonedItem
				inner join ClonedItemGroupMap on item_group = old_id
		loop
			update Item set
				item_group = record_cloned_item.item_group_new_id
			where id = record_cloned_item.id;
		end loop;
		-- item groups

		-- clone product related product binding
		for record_product_related_product in
			select source_product, related_product
			from ProductRelatedProductBinding
			where source_product in (select old_id from ClonedProductMap) or
				related_product in (select old_id from ClonedProductMap)
		loop
			-- variables will be assigned null if not found
			select into local_related_product_source new_id from ClonedProductMap
			where old_id = record_product_related_product.source_product;

			select into local_related_product_related new_id from ClonedProductMap
			where old_id = record_product_related_product.related_product;

			if local_related_product_source is not null and local_related_product_related is not null then
				-- handle clone-to-clone case
				insert into ProductRelatedProductBinding (source_product, related_product)
				values (local_related_product_source, local_related_product_related);
			elsif local_related_product_source is null and local_related_product_related is not null then
				-- handle not-clone-to-clone case
				insert into ProductRelatedProductBinding (source_product, related_product)
				values (record_product_related_product.source_product, local_related_product_related);
			elsif local_related_product_source is not null and local_related_product_related is null then
				-- handle clone-to-not-clone case
				insert into ProductRelatedProductBinding (source_product, related_product)
				values (local_related_product_source, record_product_related_product.related_product);
			else
				-- should never happen (not-clone-to-not-clone)
				raise exception 'could not clone ProductRelatedProductBinding';
			end if;
		end loop;
		-- product related product binding

		-- re-enable cache table triggers
		alter table CategoryProductBinding enable trigger VisibleProductTrigger;
		alter table Item enable trigger VisibleProductTrigger;
		alter table ItemRegionBinding enable trigger VisibleProductTrigger;

		-- update cache
		perform updateVisibleProduct();

		RETURN local_id;
	END;
$$ LANGUAGE 'plpgsql';
