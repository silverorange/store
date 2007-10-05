/**
 * Triggers to update the category product-count by region cache
 *
 * The category product-count by region cache is used to make category
 * visiblility queries fast. Any updates to tables that would change the
 * visible product count of a category should trigger a call to the
 * updateCategoryVisibilityProductCountByRegion() function.
 *
 * The fields and tables considered are:
 *
 * - VisibleProductCache insert
 * - VisibleProductCache update
 * - VisibleProductCache delete
 *
 * - CategoryProductBinding insert
 * - CategoryProductBinding update
 *   - category field
 *   - product field
 * - CategoryProductBinding delete
 *
 * - Category insert
 * - Category update
 *   - parent field
 *   - id field
 * - Category delete
 *
 * Of these relationships, the CategoryProductBinding is already included
 * in the VisibleProductCache triggers.
 */
CREATE OR REPLACE FUNCTION updateCategoryVisibleProductCountByRegion () RETURNS INTEGER AS $$
	DECLARE
		local_row record;
		local_product_count integer;
	BEGIN
		-- 1) Count all products in the category

		for local_row in select * from CategoryVisibleProductCountByRegionView where category is not null loop

			-- check if row in view exists in cache and get the product count
			select into local_product_count product_count from CategoryVisibleProductCountByRegionCache
			where category = local_row.category and region = local_row.region;

			if FOUND then
				-- exists, update the product count
				update CategoryVisibleProductCountByRegionCache
					set product_count = local_row.product_count
				where category = local_row.category and region = local_row.region;
			else
				-- doesn't exist, add the row
				insert into CategoryVisibleProductCountByRegionCache (category, region, product_count)
				values (local_row.category, local_row.region, local_row.product_count);
			end if;
		end loop;

		-- delete all rows in cache that are not in the view
		delete from CategoryVisibleProductCountByRegionCache
		where array[coalesce(category, 0), region] not in
			(select array[coalesce(category, 0), region] from CategoryVisibleProductCountByRegionView);

		-- 2) Count only major products in the category (CategoryProductBinding.minor = false)

		for local_row in select * from CategoryVisibleMajorProductCountByRegionView where category is not null loop

			-- check if row in view exists in cache and get the product count
			select into local_product_count product_count from CategoryVisibleMajorProductCountByRegionCache
			where category = local_row.category and region = local_row.region;

			if FOUND then
				-- exists, update the product count
				update CategoryVisibleMajorProductCountByRegionCache
					set product_count = local_row.product_count
				where category = local_row.category and region = local_row.region;
			else
				-- doesn't exist, add the row
				insert into CategoryVisibleMajorProductCountByRegionCache (category, region, product_count)
				values (local_row.category, local_row.region, local_row.product_count);
			end if;
		end loop;

		-- delete all rows in cache that are not in the view
		delete from CategoryVisibleMajorProductCountByRegionCache
		where array[coalesce(category, 0), region] not in
			(select array[coalesce(category, 0), region] from CategoryVisibleMajorProductCountByRegionView);

		-- set cache as clean
		update CacheFlag set dirty = false where shortname = 'CategoryVisibleProductCountByRegion';
		RETURN NULL;
	END;
$$ LANGUAGE 'plpgsql';

CREATE OR REPLACE FUNCTION runUpdateCategoryVisibleProductCountByRegion () RETURNS trigger AS $$
	BEGIN
		update CacheFlag set dirty = true where shortname = 'CategoryVisibleProductCountByRegion';
		RETURN NULL;
	END;
$$ LANGUAGE 'plpgsql';

CREATE TRIGGER CategoryVisibleProductCountByRegionTrigger AFTER INSERT OR DELETE ON VisibleProductCache
	FOR EACH STATEMENT EXECUTE PROCEDURE runUpdateCategoryVisibleProductCountByRegion();

CREATE TRIGGER CategoryVisibleProductCountByRegionTrigger AFTER INSERT OR UPDATE OR DELETE ON Category
	FOR EACH STATEMENT EXECUTE PROCEDURE runUpdateCategoryVisibleProductCountByRegion();
