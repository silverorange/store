/**
 * A cache of category item counts by region
 *
 * The category item-count by region cache is used to make category
 * item count queries fast. Any updates to tables that would change
 * the visible item count of a category should trigger a call to the
 * updateCategoryVisibilityProductCountRegion() function.
 *
 * This table mirrors the CatalogVisibleItemCountByRegionView view.
 *
 * The contents of this table are updated automatically through triggers.
 * See CategoryVisibleProductCountByRegionTrigger.sql for details.
 */
create table CategoryVisibleItemCountByRegionCache (
	category int not null references Category(id) on delete cascade,
	region int not null references Region(id) on delete cascade,
	item_count int not null default 0,
	primary key(category, region)
);
