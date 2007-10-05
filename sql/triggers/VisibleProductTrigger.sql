/**
 * Triggers to update the visible product cache
 *
 * The visible product cache is used to make visible product queries
 * fast. Any updates to tables that would change the visibility of
 * products should trigger a call to the updateVisibleProduct()
 * function.
 *
 * The fields and tables considered are:
 *
 * - CatalogRegionBinding insert
 * - CatalogRegionBinding update
 *   - category field
 *   - region field
 *   - available
 * - CatalogRegionBinding delete
 *
 * - CategoryProductBinding insert
 * - CategoryProductBinding update
 *   - category field
 *   - product field
 * - CategoryProductBinding delete
 *
 * - Item update
 *   - catalog field
 *   - product field
 *   - status field
 *   - enabled field
 *
 * - ItemRegionBinding insert
 * - ItemRegionBinding update
 * - ItemRegionBinding delete
 */
CREATE OR REPLACE FUNCTION updateVisibleProduct () RETURNS INTEGER AS $$
	DECLARE
		locale_row record;
    BEGIN
		-- for all rows in view
		for local_row in select * from VisibleProductView where product is not null loop

			-- check if row in view exists in cache
			perform product from VisibleProductCache
			where product = locale_row.product and region = local_row.region;

			if not FOUND then
				-- doesn't exist, add the row
				insert into VisibleProductCache (product, region)
				values (local_row.product, local_row.region);
			end if;

		end loop;

		-- delete all rows in cache that are not in the view
		delete from VisibleProductCache
		where array[coalesce(product, 0), region] not in
			(select array[coalesce(product, 0), region] from VisibleProductView);

		-- set cache as clean
		update CacheFlag set dirty = false where shortname = 'VisibleProduct';
        RETURN NULL;
    END;
$$ LANGUAGE 'plpgsql';

CREATE OR REPLACE FUNCTION runUpdateVisibleProduct () RETURNS trigger AS $$
    BEGIN
		update CacheFlag set dirty = true where shortname = 'VisibleProduct';
        RETURN NULL;
    END;
$$ LANGUAGE 'plpgsql';

CREATE TRIGGER VisibleProductTrigger AFTER INSERT OR DELETE OR UPDATE ON CatalogRegionBinding
    FOR EACH STATEMENT EXECUTE PROCEDURE runUpdateVisibleProduct();

CREATE TRIGGER VisibleProductTrigger AFTER INSERT OR DELETE OR UPDATE ON CategoryProductBinding
    FOR EACH STATEMENT EXECUTE PROCEDURE runUpdateVisibleProduct();

CREATE TRIGGER VisibleProductTrigger AFTER UPDATE ON Item
    FOR EACH STATEMENT EXECUTE PROCEDURE runUpdateVisibleProduct();

CREATE TRIGGER VisibleProductTrigger AFTER INSERT OR DELETE OR UPDATE ON ItemRegionBinding
    FOR EACH STATEMENT EXECUTE PROCEDURE runUpdateVisibleProduct();
