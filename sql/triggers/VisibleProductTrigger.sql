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
 * - ItemRegionBinding delete
 */
CREATE OR REPLACE FUNCTION updateVisibleProduct () RETURNS trigger AS $$
    BEGIN
		delete from VisibleProductCache;
		insert into VisibleProductCache (product, region)
			select product, region from VisibleProductView;

        RETURN NULL;
    END;
$$ LANGUAGE 'plpgsql';

CREATE TRIGGER VisibleProductTrigger AFTER INSERT OR DELETE OR UPDATE ON CatalogRegionBinding 
    FOR EACH STATEMENT EXECUTE PROCEDURE updateVisibleProduct();

CREATE TRIGGER VisibleProductTrigger AFTER INSERT OR DELETE OR UPDATE ON CategoryProductBinding
    FOR EACH STATEMENT EXECUTE PROCEDURE updateVisibleProduct();

CREATE TRIGGER VisibleProductTrigger AFTER UPDATE ON Item
    FOR EACH STATEMENT EXECUTE PROCEDURE updateVisibleProduct();

CREATE TRIGGER VisibleProductTrigger AFTER INSERT OR DELETE OR UPDATE ON ItemRegionBinding
    FOR EACH STATEMENT EXECUTE PROCEDURE updateVisibleProduct();
