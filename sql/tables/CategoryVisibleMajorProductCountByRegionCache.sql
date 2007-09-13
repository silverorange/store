/**
 * A cache of category major product counts by region
 *
 * The category product-count by region cache is used to make category
 * visiblility queries fast. Any updates to tables that would change the
 * visible product count of a category should trigger a call to the
 * updateCategoryVisibilityProductCountRegion() function.
 *
 * This table mirrors the CatalogVisibleMajorProductCountByRegionView view.
 *
 * This table records the count of all products in a category that are not
 * minor members. A parallel table, CatalogVisibleProductCountByRegionCache
 * records the count of all products, both minor and major which is useful for
 * displaying product counts.
 *
 * The contents of this table are updated automatically through triggers.
 * See CategoryVisibleProductCountByRegionTrigger.sql for details.
 */
create table CategoryVisibleMajorProductCountByRegionCache (
	category int not null references Category(id) on delete cascade,
	region int not null references Region(id) on delete cascade,
	product_count int not null default 0,
	primary key(category, region)
);
