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
CREATE OR REPLACE FUNCTION updateCategoryVisibleItemCountByRegion () RETURNS INTEGER AS $$
	DECLARE
		local_row record;
		local_item_count integer;
	BEGIN
		-- Count visible items in the category

		for local_row in select * from CategoryVisibleItemCountByRegionView where category is not null loop

			-- check if row in view exists in cache and get the product count
			select into local_item_count item_count from CategoryVisibleItemCountByRegionCache
			where category = local_row.category and region = local_row.region;

			if FOUND then
				-- exists, update the product count
				update CategoryVisibleItemCountByRegionCache
					set item_count = local_row.item_count
				where category = local_row.category and region = local_row.region;
			else
				-- doesn't exist, add the row
				insert into CategoryVisibleItemCountByRegionCache (category, region, item_count)
				values (local_row.category, local_row.region, local_row.item_count);
			end if;
		end loop;

		-- delete all rows in cache that are not in the view
		delete from CategoryVisibleItemCountByRegionCache
		where array[coalesce(category, 0), region] not in
			(select array[coalesce(category, 0), region] from CategoryVisibleItemCountByRegionView);

		-- set cache as clean
		update CacheFlag set dirty = false where shortname = 'CategoryVisibleItemCountByRegion';
		RETURN NULL;
	END;
$$ LANGUAGE 'plpgsql';

CREATE OR REPLACE FUNCTION runupdateCategoryVisibleItemCountByRegion () RETURNS trigger AS $$
	BEGIN
		update CacheFlag set dirty = true where shortname = 'CategoryVisibleItemCountByRegion';
		RETURN NULL;
	END;
$$ LANGUAGE 'plpgsql';

CREATE TRIGGER CategoryVisibleItemCountByRegionTrigger AFTER INSERT OR DELETE ON VisibleProductCache
	FOR EACH STATEMENT EXECUTE PROCEDURE runupdateCategoryVisibleItemCountByRegion();

CREATE TRIGGER CategoryVisibleItemCountByRegionTrigger AFTER INSERT OR UPDATE OR DELETE ON Category
	FOR EACH STATEMENT EXECUTE PROCEDURE runupdateCategoryVisibleItemCountByRegion();

CREATE TRIGGER CategoryVisibleItemCountByRegionTrigger AFTER INSERT OR UPDATE OR DELETE ON Item
	FOR EACH STATEMENT EXECUTE PROCEDURE runupdateCategoryVisibleItemCountByRegion();

CREATE TRIGGER CategoryVisibleItemCountByRegionTrigger AFTER INSERT OR UPDATE OR DELETE ON ItemRegionBinding
	FOR EACH STATEMENT EXECUTE PROCEDURE runupdateCategoryVisibleItemCountByRegion();
