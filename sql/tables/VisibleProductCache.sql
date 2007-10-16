/**
 * A cache of visible products by region
 *
 * The visible product cache is used to make visible product queries
 * fast. Any updates to tables that would change the visibility of
 * products should trigger a call to the updateVisibleProduct()
 * function.
 *
 * This table mirrors the VisibleProductView view.
 *
 * The contents of this table are updated automatically through triggers.
 * See VisibleProductTrigger.sql for details.
 */
create table VisibleProductCache (
	product int not null references Product(id) on delete cascade,
	region int not null references Region(id) on delete cascade,
	primary key (product, region)
);
