/**
 * Triggers to update the category product-count by region cache
 *
 * The category product-count by region cache is used to make category
 * visiblility queries fast. Any updates to tables that would change the
 * visible product count of a category should trigger a call to the
 * updateCategoryVisibilityProductCountRegion() function.
 *
 * The fields and tables considered are:
 *
 * - VisibleProductCache insert
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
    BEGIN
		truncate CategoryVisibleProductCountByRegionCache;
		insert into CategoryVisibleProductCountByRegionCache (category, region, product_count)
			select category, region, product_count from CategoryVisibleProductCountByRegionView
			where category is not null;

        RETURN NULL;
    END;
$$ LANGUAGE 'plpgsql';

CREATE OR REPLACE FUNCTION runUpdateCategoryVisibleProductCountByRegion () RETURNS trigger AS $$ 
    BEGIN
		perform updateCategoryVisibleProductCountByRegion();
        RETURN NULL;
    END;
$$ LANGUAGE 'plpgsql';

CREATE TRIGGER CategoryVisibleProductCountByRegionTrigger AFTER INSERT ON VisibleProductCache
    FOR EACH STATEMENT EXECUTE PROCEDURE runUpdateCategoryVisibleProductCountByRegion();

CREATE TRIGGER CategoryVisibleProductCountByRegionTrigger AFTER INSERT OR UPDATE OR DELETE ON Category 
    FOR EACH STATEMENT EXECUTE PROCEDURE runUpdateCategoryVisibleProductCountByRegion();
