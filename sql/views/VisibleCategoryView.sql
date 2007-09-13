/**
 * Gets visible categories with the regions they are visible in.
 *
 * Some categories have the always_visible flag set as true. If a category has
 * this flag set as true and is not bound to any regions the category is still
 * returned in the result set with a region of null. Site code should recognize
 * this and accept visible categories having null regions as valid in any
 * region.
 */
create or replace view VisibleCategoryView as
	select Category.id as category, region
		from Category
			left outer join CategoryVisibleMajorProductCountByRegionCache on
				Category.id = CategoryVisibleMajorProductCountByRegionCache.category
		where product_count > 0 or always_visible = true;
